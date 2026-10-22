<?php

namespace forum\system\moderation\queue\activation;

use forum\data\thread\Thread;
use forum\data\thread\ThreadAction;
use forum\data\thread\ThreadList;
use forum\system\moderation\queue\TForumModerationQueueHandler;
use wcf\data\moderation\queue\ModerationQueue;
use wcf\data\moderation\queue\ViewableModerationQueue;
use wcf\system\moderation\queue\AbstractModerationQueueHandler;
use wcf\system\moderation\queue\activation\IModerationQueueActivationHandler;
use wcf\system\WCF;

/**
 * Handles threads that await approval.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ThreadModerationQueueActivationHandler extends AbstractModerationQueueHandler implements IModerationQueueActivationHandler
{
    use TForumModerationQueueHandler;

    /**
     * @inheritDoc
     */
    protected $className = Thread::class;

    /**
     * @inheritDoc
     */
    protected $definitionName = 'com.woltlab.wcf.moderation.activation';

    /**
     * @inheritDoc
     */
    protected $objectType = 'com.tinsever.forum.thread';

    #[\Override]
    protected function getBoardPermission(): string
    {
        return 'canEnableThread';
    }

    #[\Override]
    public function canRemoveContent(ModerationQueue $queue)
    {
        $board = \forum\data\board\BoardCache::getInstance()->getBoard($queue->containerID);

        return $board !== null && $board->getPermission('canDeleteThread');
    }

    #[\Override]
    public function enableContent(ModerationQueue $queue)
    {
        $thread = $this->loadThread($queue->objectID);
        if ($thread !== null && $thread->isDisabled) {
            (new ThreadAction([$thread], 'enable'))->executeAction();
        }
    }

    #[\Override]
    public function getDisabledContent(ViewableModerationQueue $queue)
    {
        $thread = $this->loadThread($queue->objectID);

        return WCF::getTPL()->render('forum', 'moderationPost', [
            'post' => $thread?->getFirstPost(),
        ]);
    }

    #[\Override]
    public function getContainerID($objectID)
    {
        return $this->loadThread(\intval($objectID))?->boardID ?? 0;
    }

    #[\Override]
    public function isValid($objectID)
    {
        return $this->loadThread(\intval($objectID)) !== null;
    }

    #[\Override]
    public function populate(array $queues)
    {
        $objectIDs = [];
        foreach ($queues as $queue) {
            $objectIDs[] = $queue->objectID;
        }

        $threadList = new ThreadList();
        $threadList->setObjectIDs($objectIDs);
        $threadList->readObjects();
        $threads = $threadList->getObjects();

        foreach ($queues as $queue) {
            if (isset($threads[$queue->objectID])) {
                $queue->setAffectedObject($threads[$queue->objectID]);
            } else {
                $queue->setIsOrphaned();
            }
        }
    }

    #[\Override]
    public function removeContent(ModerationQueue $queue, $message)
    {
        $thread = $this->loadThread($queue->objectID);
        if ($thread !== null) {
            (new ThreadAction([$thread], 'delete'))->executeAction();
        }
    }
}
