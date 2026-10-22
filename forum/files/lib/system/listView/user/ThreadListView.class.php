<?php

namespace forum\system\listView\user;

use forum\data\board\Board;
use forum\data\board\BoardCache;
use forum\data\thread\Thread;
use forum\data\thread\ViewableThreadList;
use forum\system\board\BoardPermissionHandler;
use forum\system\interaction\bulk\user\ThreadBulkInteractions;
use forum\system\interaction\user\ThreadInteractions;
use forum\system\label\object\ThreadLabelObjectHandler;
use forum\system\thread\ThreadVisitHandler;
use wcf\data\DatabaseObjectList;
use wcf\system\listView\AbstractListView;
use wcf\system\listView\ListViewSortField;
use wcf\system\view\filter\LabelFilter;
use wcf\system\view\filter\SelectFilter;
use wcf\system\view\filter\TextFilter;
use wcf\system\view\filter\TimeFilter;
use wcf\system\visitTracker\VisitTracker;
use wcf\system\WCF;

/**
 * List of threads, either of a single board or a filtered list across all boards.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractListView<Thread, ViewableThreadList>
 */
final class ThreadListView extends AbstractListView
{
    /**
     * Threads count as hot if they received replies within this period.
     */
    public const HOT_THREAD_PERIOD = 30 * 86400;

    public const FILTERS = ['', 'unread', 'unanswered', 'undone', 'watched', 'participated', 'user', 'latest', 'deleted', 'hot'];

    public readonly int $boardID;

    public readonly string $filter;

    public readonly int $userID;

    private ?Board $board = null;

    public function __construct(int $boardID = 0, string $filter = '', int $userID = 0)
    {
        $this->boardID = $boardID;
        $this->filter = \in_array($filter, self::FILTERS, true) ? $filter : '';
        $this->userID = $userID;

        if ($this->boardID) {
            $this->board = BoardCache::getInstance()->getBoard($this->boardID);
        }

        $this->addAvailableSortFields([
            new ListViewSortField('lastPostTime', 'forum.thread.lastPostTime'),
            new ListViewSortField('time', 'forum.thread.time'),
            new ListViewSortField('topic', 'forum.thread.topic'),
            new ListViewSortField('username', 'forum.thread.author'),
            new ListViewSortField('replies', 'forum.thread.replies'),
            new ListViewSortField('views', 'forum.thread.views'),
            new ListViewSortField('cumulativeLikes', 'forum.thread.reactions'),
        ]);

        $this->addAvailableFilters([
            new TextFilter('topic', 'forum.thread.topic'),
            new TimeFilter('lastPostTime', 'forum.thread.lastPostTime'),
        ]);

        if ($this->board !== null) {
            $objectTypeID = ThreadLabelObjectHandler::getInstance()->getObjectTypeID();
            foreach (ThreadLabelObjectHandler::getLabelGroupsForBoard($this->board->boardID, 'canViewLabel') as $group) {
                $this->addAvailableFilter(new LabelFilter($group, $objectTypeID, 'labelIDs' . $group->groupID));
            }

            if ($this->board->enableMarkingAsDone) {
                $this->addAvailableFilter(new SelectFilter(
                    [
                        0 => 'forum.thread.status.undone',
                        1 => 'forum.thread.status.done',
                    ],
                    'isDone',
                    'forum.thread.status'
                ));
            }

            $this->setItemsPerPage($this->board->getThreadsPerPage());
            $this->setDefaultSortField($this->board->getSortField());
            $this->setDefaultSortOrder($this->board->getSortOrder());
        } else {
            $this->setItemsPerPage(\FORUM_THREADS_PER_PAGE);
            $this->setDefaultSortField('lastPostTime');
            $this->setDefaultSortOrder('DESC');
        }

        $this->setInteractionProvider(new ThreadInteractions());
        if ($this->canModerate()) {
            $this->setBulkInteractionProvider(new ThreadBulkInteractions());
        }

        $this->setCssClassName('forumThreadList');
        $this->setContainerCssClassName('forumThreadList__container');
        $this->setMarkAsReadEndpoint('forum/threads/%s/mark-as-read');
    }

    #[\Override]
    protected function createObjectList(): ViewableThreadList
    {
        $list = new ViewableThreadList(
            $this->boardID ? [$this->boardID] : null,
            !$this->boardID,
            true
        );
        $conditions = $list->getConditionBuilder();

        if ($this->boardID) {
            // announcements are displayed separately
            $conditions->add('thread.isAnnouncement = ?', [0]);

            if ($this->board !== null && $this->board->daysPrune) {
                $conditions->add(
                    '(thread.isSticky = ? OR thread.lastPostTime >= ?)',
                    [1, TIME_NOW - $this->board->daysPrune * 86400]
                );
            }
        } else {
            $conditions->add('thread.movedThreadID IS NULL');
        }

        $userID = WCF::getUser()->userID;
        switch ($this->filter) {
            case 'unread':
                $visitTracker = VisitTracker::getInstance();
                $conditions->add('thread.lastPostTime > ?', [ThreadVisitHandler::getInstance()->getGlobalVisitTime()]);
                $conditions->add(
                    "NOT EXISTS (
                        SELECT  1
                        FROM    wcf1_tracked_visit thread_visit
                        WHERE   thread_visit.objectTypeID = ?
                            AND thread_visit.objectID = thread.threadID
                            AND thread_visit.userID = ?
                            AND thread_visit.visitTime >= thread.lastPostTime
                    )",
                    [$visitTracker->getObjectTypeID(ThreadVisitHandler::THREAD_OBJECT_TYPE), $userID]
                );
                $conditions->add(
                    "NOT EXISTS (
                        SELECT  1
                        FROM    wcf1_tracked_visit board_visit
                        WHERE   board_visit.objectTypeID = ?
                            AND board_visit.objectID = thread.boardID
                            AND board_visit.userID = ?
                            AND board_visit.visitTime >= thread.lastPostTime
                    )",
                    [$visitTracker->getObjectTypeID(ThreadVisitHandler::BOARD_OBJECT_TYPE), $userID]
                );
                $conditions->add('thread.isDeleted = ?', [0]);
                $conditions->add('thread.isDisabled = ?', [0]);
                break;

            case 'unanswered':
                $conditions->add('thread.replies = ?', [0]);
                $conditions->add('thread.isClosed = ?', [0]);
                $conditions->add('thread.isDeleted = ?', [0]);
                break;

            case 'undone':
                $boardIDs = [];
                foreach (BoardCache::getInstance()->getBoards() as $board) {
                    if ($board->enableMarkingAsDone) {
                        $boardIDs[] = $board->boardID;
                    }
                }
                $conditions->add('thread.isDone = ?', [0]);
                $conditions->add('thread.boardID IN (?)', [$boardIDs ?: [0]]);
                $conditions->add('thread.isDeleted = ?', [0]);
                break;

            case 'watched':
                $conditions->add(
                    "thread.threadID IN (
                        SELECT  threadID
                        FROM    forum1_thread_user_status
                        WHERE   userID = ?
                            AND status = ?
                    )",
                    [$userID, 'watching']
                );
                break;

            case 'participated':
                $conditions->add(
                    "thread.threadID IN (
                        SELECT  DISTINCT threadID
                        FROM    forum1_post
                        WHERE   userID = ?
                    )",
                    [$userID]
                );
                break;

            case 'user':
                $conditions->add('thread.userID = ?', [$this->userID]);
                break;

            case 'deleted':
                $conditions->add('thread.isDeleted = ?', [1]);
                break;

            case 'hot':
                $conditions->add('thread.lastPostTime >= ?', [TIME_NOW - self::HOT_THREAD_PERIOD]);
                $conditions->add('thread.replies > ?', [0]);
                $conditions->add('thread.isDeleted = ?', [0]);
                $conditions->add('thread.isDisabled = ?', [0]);
                break;
        }

        return $list;
    }

    #[\Override]
    protected function getSqlOrderBy(): string
    {
        $sqlOrderBy = parent::getSqlOrderBy();
        if ($this->boardID) {
            $sqlOrderBy = 'thread.isSticky DESC, ' . $sqlOrderBy;
        }

        return $sqlOrderBy;
    }

    #[\Override]
    public function renderItems(): string
    {
        return WCF::getTPL()->render('forum', 'threadListItems', [
            'view' => $this,
            'threads' => $this->getItems(),
            'showBoard' => !$this->boardID,
        ]);
    }

    #[\Override]
    public function isAccessible(): bool
    {
        if ($this->boardID) {
            return $this->board !== null && $this->board->canReadThreads();
        }

        if (\in_array($this->filter, ['unread', 'watched', 'participated'], true)) {
            return WCF::getUser()->userID !== 0;
        }

        if ($this->filter === 'deleted') {
            return BoardPermissionHandler::getInstance()->getBoardIDs(['canReadDeletedThread']) !== [];
        }

        if ($this->filter === 'user') {
            return $this->userID !== 0;
        }

        return BoardPermissionHandler::getInstance()->getAccessibleBoardIDs() !== [];
    }

    #[\Override]
    public function getParameters(): array
    {
        return [
            'boardID' => $this->boardID,
            'filter' => $this->filter,
            'userID' => $this->userID,
        ];
    }

    public function getBoard(): ?Board
    {
        return $this->board;
    }

    /**
     * Returns true if the active user has moderative permissions in the listed boards.
     */
    private function canModerate(): bool
    {
        if ($this->board !== null) {
            return $this->board->isModerator();
        }

        foreach (BoardCache::getInstance()->getBoards() as $board) {
            if ($board->isBoard() && $board->isModerator()) {
                return true;
            }
        }

        return false;
    }
}
