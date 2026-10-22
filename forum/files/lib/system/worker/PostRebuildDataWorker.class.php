<?php

namespace forum\system\worker;

use forum\data\post\Post;
use forum\data\post\PostList;
use wcf\data\object\type\ObjectTypeCache;
use wcf\system\bbcode\BBCodeHandler;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\message\embedded\object\MessageEmbeddedObjectManager;
use wcf\system\user\activity\point\UserActivityPointHandler;
use wcf\system\WCF;
use wcf\system\worker\AbstractLinearRebuildDataWorker;

/**
 * Re-parses the posts, recounts their attachments and rebuilds the activity points for posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractLinearRebuildDataWorker<PostList>
 */
final class PostRebuildDataWorker extends AbstractLinearRebuildDataWorker
{
    /**
     * @inheritDoc
     */
    protected $objectListClassName = PostList::class;

    /**
     * @inheritDoc
     */
    protected $limit = 250;

    private ?HtmlInputProcessor $htmlInputProcessor = null;

    #[\Override]
    public function execute()
    {
        parent::execute();

        if (!$this->loopCount) {
            UserActivityPointHandler::getInstance()->reset('com.tinsever.forum.activityPointEvent.post');
        }

        if (!\count($this->objectList)) {
            return;
        }

        $attachmentObjectTypeID = ObjectTypeCache::getInstance()->getObjectTypeIDByName(
            'com.woltlab.wcf.attachment.objectType',
            Post::OBJECT_TYPE
        );
        $sql = "SELECT  COUNT(*)
                FROM    wcf1_attachment
                WHERE   objectTypeID = ?
                    AND objectID = ?";
        $attachmentStatement = WCF::getDB()->prepare($sql);

        $userIDs = [];
        foreach ($this->objectList as $post) {
            $userIDs[] = $post->userID ?: 0;
        }
        $userPermissions = $this->getBulkUserPermissions($userIDs, ['user.message.disallowedBBCodes']);

        $sql = "UPDATE  forum1_post
                SET     attachments = ?,
                        message = ?,
                        enableHtml = ?,
                        hasEmbeddedObjects = ?
                WHERE   postID = ?";
        $updateStatement = WCF::getDB()->prepare($sql);

        // the visibility of the thread is required for the activity points
        $sql = "SELECT  isDeleted, isDisabled
                FROM    forum1_thread
                WHERE   threadID = ?";
        $threadStatement = WCF::getDB()->prepare($sql);
        $threadVisibility = [];

        $activityPoints = [];
        WCF::getDB()->beginTransaction();
        foreach ($this->objectList as $post) {
            $attachmentStatement->execute([$attachmentObjectTypeID, $post->postID]);
            $attachments = \intval($attachmentStatement->fetchSingleColumn());

            BBCodeHandler::getInstance()->setDisallowedBBCodes(\explode(
                ',',
                $this->getBulkUserPermissionValue($userPermissions, $post->userID, 'user.message.disallowedBBCodes')
            ));

            $processor = $this->getHtmlInputProcessor();
            if ($post->enableHtml) {
                $processor->reprocess($post->message, Post::OBJECT_TYPE, $post->postID);
            } else {
                $processor->process($post->message, Post::OBJECT_TYPE, $post->postID, true);
            }
            $hasEmbeddedObjects = MessageEmbeddedObjectManager::getInstance()->registerObjects($processor, true);

            $updateStatement->execute([
                $attachments,
                $processor->getHtml(),
                1,
                $hasEmbeddedObjects ? 1 : 0,
                $post->postID,
            ]);

            if (!isset($threadVisibility[$post->threadID])) {
                $threadStatement->execute([$post->threadID]);
                $row = $threadStatement->fetchArray();
                $threadVisibility[$post->threadID] = $row && !$row['isDeleted'] && !$row['isDisabled'];
            }

            if ($post->userID && !$post->isDeleted && !$post->isDisabled && $threadVisibility[$post->threadID]) {
                $activityPoints[$post->userID] = ($activityPoints[$post->userID] ?? 0) + 1;
            }
        }
        WCF::getDB()->commitTransaction();

        if ($activityPoints !== []) {
            UserActivityPointHandler::getInstance()->fireEvents(
                'com.tinsever.forum.activityPointEvent.post',
                $activityPoints,
                false
            );
        }
    }

    private function getHtmlInputProcessor(): HtmlInputProcessor
    {
        return $this->htmlInputProcessor ??= new HtmlInputProcessor();
    }
}
