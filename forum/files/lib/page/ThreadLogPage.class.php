<?php

namespace forum\page;

use forum\system\board\BoardBreadcrumbs;
use forum\data\modification\log\ViewableForumModificationLog;
use forum\data\thread\Thread;
use forum\system\cache\runtime\ThreadRuntimeCache;
use wcf\data\modification\log\ModificationLogList;
use wcf\data\object\type\ObjectTypeCache;
use wcf\page\MultipleLinkPage;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\page\PageLocationManager;
use wcf\system\WCF;

/**
 * Shows the modification log of a thread and its posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends MultipleLinkPage<ModificationLogList>
 */
class ThreadLogPage extends MultipleLinkPage
{
    /**
     * @inheritDoc
     */
    public $objectListClassName = ModificationLogList::class;

    /**
     * @inheritDoc
     */
    public $sortField = 'modification_log.time';

    /**
     * @inheritDoc
     */
    public $sortOrder = 'DESC';

    /**
     * @inheritDoc
     */
    public $itemsPerPage = 50;

    public Thread $thread;

    /**
     * @var ViewableForumModificationLog[]
     */
    public array $logs = [];

    #[\Override]
    public function readParameters()
    {
        parent::readParameters();

        $thread = ThreadRuntimeCache::getInstance()->getObject(\intval($_REQUEST['id'] ?? 0));
        if ($thread === null) {
            throw new IllegalLinkException();
        }
        $this->thread = $thread;

        if (!$this->thread->canRead() || !$this->thread->getBoard()->isModerator()) {
            throw new PermissionDeniedException();
        }
    }

    #[\Override]
    protected function initObjectList()
    {
        parent::initObjectList();

        $threadTypeID = ObjectTypeCache::getInstance()->getObjectTypeIDByName(
            'com.woltlab.wcf.modifiableContent',
            'com.tinsever.forum.thread'
        );
        $postTypeID = ObjectTypeCache::getInstance()->getObjectTypeIDByName(
            'com.woltlab.wcf.modifiableContent',
            'com.tinsever.forum.post'
        );

        $this->objectList->getConditionBuilder()->add(
            '((modification_log.objectTypeID = ? AND modification_log.objectID = ?) OR (modification_log.objectTypeID = ? AND modification_log.parentObjectID = ?))',
            [$threadTypeID, $this->thread->threadID, $postTypeID, $this->thread->threadID]
        );
    }

    #[\Override]
    public function readData()
    {
        parent::readData();

        $userIDs = [];
        foreach ($this->objectList as $log) {
            $this->logs[] = new ViewableForumModificationLog($log);
            if ($log->userID) {
                $userIDs[] = $log->userID;
            }
        }
        UserProfileRuntimeCache::getInstance()->cacheObjectIDs(\array_unique($userIDs));

        BoardBreadcrumbs::addThread($this->thread);
    }

    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();

        WCF::getTPL()->assign([
            'thread' => $this->thread,
            'logs' => $this->logs,
        ]);
    }
}
