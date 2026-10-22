<?php

namespace forum\system\log\modification;

use forum\data\modification\log\ViewableForumModificationLog;
use forum\data\thread\Thread;
use forum\system\cache\runtime\ThreadRuntimeCache;
use wcf\system\log\modification\AbstractExtendedModificationLogHandler;

/**
 * Logs moderative actions on threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ThreadModificationLogHandler extends AbstractExtendedModificationLogHandler
{
    /**
     * @inheritDoc
     */
    protected $objectTypeName = 'com.tinsever.forum.thread';

    public function add(Thread $thread, string $action, array $additionalData = []): void
    {
        $additionalData['topic'] ??= $thread->topic;

        $this->createLog($action, $thread->threadID, null, $additionalData);
    }

    #[\Override]
    public function getAvailableActions()
    {
        return [
            'close',
            'open',
            'sticky',
            'unsticky',
            'announcement',
            'unannouncement',
            'move',
            'trash',
            'restore',
            'delete',
            'enable',
            'disable',
            'merge',
            'edit',
            'done',
            'undone',
            'bestAnswer',
        ];
    }

    #[\Override]
    public function processItems(array $items)
    {
        $threadIDs = [];
        foreach ($items as $item) {
            $threadIDs[] = $item->objectID;
        }
        ThreadRuntimeCache::getInstance()->cacheObjectIDs(\array_unique($threadIDs));

        $logs = [];
        foreach ($items as $item) {
            $log = new ViewableForumModificationLog($item);
            $thread = ThreadRuntimeCache::getInstance()->getObject($item->objectID);
            if ($thread !== null && $thread->canRead()) {
                $log->setAffectedObject($thread);
            }
            $logs[] = $log;
        }

        return $logs;
    }
}
