<?php

namespace forum\system\moderation\queue\report;

use forum\data\post\Post;
use forum\data\post\PostAction;
use forum\data\post\PostList;
use forum\data\thread\ThreadAction;
use forum\system\moderation\queue\TForumModerationQueueHandler;
use wcf\data\moderation\queue\ModerationQueue;
use wcf\data\moderation\queue\ViewableModerationQueue;
use wcf\system\moderation\queue\AbstractModerationQueueHandler;
use wcf\system\moderation\queue\report\IModerationQueueReportHandler;
use wcf\system\WCF;

/**
 * Handles reported posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostModerationQueueReportHandler extends AbstractModerationQueueHandler implements IModerationQueueReportHandler
{
    use TForumModerationQueueHandler;

    /**
     * @inheritDoc
     */
    protected $className = Post::class;

    /**
     * @inheritDoc
     */
    protected $definitionName = 'com.woltlab.wcf.moderation.report';

    /**
     * @inheritDoc
     */
    protected $objectType = 'com.tinsever.forum.post';

    #[\Override]
    protected function getBoardPermission(): string
    {
        return 'canEditPost';
    }

    #[\Override]
    public function canReport($objectID)
    {
        $post = $this->loadPost(\intval($objectID));

        return $post !== null && $post->canRead();
    }

    #[\Override]
    public function getContainerID($objectID)
    {
        $post = $this->loadPost(\intval($objectID));

        return $post === null ? 0 : $post->getThread()->boardID;
    }

    #[\Override]
    public function getReportedContent(ViewableModerationQueue $queue)
    {
        return WCF::getTPL()->render('forum', 'moderationPost', [
            'post' => $this->loadPost($queue->objectID),
        ]);
    }

    #[\Override]
    public function getReportedObject($objectID)
    {
        return $this->loadPost(\intval($objectID));
    }

    #[\Override]
    public function isValid($objectID)
    {
        return $this->loadPost(\intval($objectID)) !== null;
    }

    #[\Override]
    public function populate(array $queues)
    {
        $objectIDs = [];
        foreach ($queues as $queue) {
            $objectIDs[] = $queue->objectID;
        }

        $postList = new PostList();
        $postList->setObjectIDs($objectIDs);
        $postList->readObjects();
        $posts = $postList->getObjects();

        foreach ($queues as $queue) {
            if (isset($posts[$queue->objectID])) {
                $queue->setAffectedObject($posts[$queue->objectID]);
            } else {
                $queue->setIsOrphaned();
            }
        }
    }

    #[\Override]
    public function removeContent(ModerationQueue $queue, $message)
    {
        $post = $this->loadPost($queue->objectID);
        if ($post === null || $post->isDeleted) {
            return;
        }

        if ($post->isFirstPost()) {
            (new ThreadAction([$post->getThread()], 'trash', ['reason' => $message]))->executeAction();
        } else {
            (new PostAction([$post], 'trash', ['reason' => $message]))->executeAction();
        }
    }
}
