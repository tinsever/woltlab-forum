<?php

namespace forum\system\moderation\queue\activation;

use forum\data\post\Post;
use forum\data\post\PostAction;
use forum\data\post\PostList;
use forum\system\moderation\queue\TForumModerationQueueHandler;
use wcf\data\moderation\queue\ModerationQueue;
use wcf\data\moderation\queue\ViewableModerationQueue;
use wcf\system\moderation\queue\AbstractModerationQueueHandler;
use wcf\system\moderation\queue\activation\IModerationQueueActivationHandler;
use wcf\system\WCF;

/**
 * Handles posts that await approval.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostModerationQueueActivationHandler extends AbstractModerationQueueHandler implements IModerationQueueActivationHandler
{
    use TForumModerationQueueHandler;

    /**
     * @inheritDoc
     */
    protected $className = Post::class;

    /**
     * @inheritDoc
     */
    protected $definitionName = 'com.woltlab.wcf.moderation.activation';

    /**
     * @inheritDoc
     */
    protected $objectType = 'com.tinsever.forum.post';

    #[\Override]
    protected function getBoardPermission(): string
    {
        return 'canEnablePost';
    }

    #[\Override]
    public function enableContent(ModerationQueue $queue)
    {
        $post = $this->loadPost($queue->objectID);
        if ($post !== null && $post->isDisabled) {
            (new PostAction([$post], 'enable'))->executeAction();
        }
    }

    #[\Override]
    public function getDisabledContent(ViewableModerationQueue $queue)
    {
        return WCF::getTPL()->render('forum', 'moderationPost', [
            'post' => $this->loadPost($queue->objectID),
        ]);
    }

    #[\Override]
    public function getContainerID($objectID)
    {
        $post = $this->loadPost(\intval($objectID));

        return $post === null ? 0 : $post->getThread()->boardID;
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
        if ($post !== null) {
            (new PostAction([$post], 'delete'))->executeAction();
        }
    }
}
