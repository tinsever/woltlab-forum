<?php

namespace forum\system\moderation\queue;

use forum\data\board\BoardCache;
use forum\data\post\Post;
use forum\data\thread\Thread;
use forum\system\board\BoardPermissionHandler;
use wcf\data\moderation\queue\ModerationQueue;
use wcf\data\user\User;
use wcf\system\moderation\queue\ModerationQueueManager;
use wcf\system\WCF;

/**
 * Shared logic of the moderation queue handlers for threads and posts. The
 * container id of a queue is the id of the board.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
trait TForumModerationQueueHandler
{
    /**
     * Returns the moderative permission that is required to handle the queue.
     */
    abstract protected function getBoardPermission(): string;

    /**
     * @param ModerationQueue[] $queues
     */
    public function assignQueues(array $queues)
    {
        $assignments = [];
        foreach ($queues as $queue) {
            $board = BoardCache::getInstance()->getBoard($queue->containerID);
            $assignments[$queue->queueID] = $board !== null && $board->getPermission($this->getBoardPermission());
        }

        ModerationQueueManager::getInstance()->setAssignment($assignments);
    }

    public function isAffectedUser(ModerationQueue $queue, $userID)
    {
        $board = BoardCache::getInstance()->getBoard($queue->containerID);
        if ($board === null) {
            return false;
        }

        $user = new User($userID);

        return $user->userID
            && BoardPermissionHandler::getInstance()->getPermission($board, $this->getBoardPermission(), $user);
    }

    public function canRemoveContent(ModerationQueue $queue)
    {
        $board = BoardCache::getInstance()->getBoard($queue->containerID);

        return $board !== null && $board->getPermission('canDeletePost');
    }

    protected function loadPost(int $postID): ?Post
    {
        $post = new Post($postID);

        return $post->postID ? $post : null;
    }

    protected function loadThread(int $threadID): ?Thread
    {
        $thread = new Thread($threadID);

        return $thread->threadID ? $thread : null;
    }

    protected function getModerationUserID(): int
    {
        return WCF::getUser()->userID;
    }
}
