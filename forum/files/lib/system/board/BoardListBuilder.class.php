<?php

namespace forum\system\board;

use forum\data\board\Board;
use forum\data\board\BoardCache;
use forum\data\board\BoardNode;
use forum\data\board\BoardNodeTree;
use forum\data\thread\Thread;
use forum\data\thread\ViewableThreadList;
use forum\system\thread\ThreadVisitHandler;
use wcf\data\user\group\UserGroup;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\language\LanguageFactory;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\visitTracker\VisitTracker;
use wcf\system\WCF;
use wcf\util\JSON;

/**
 * Builds the board hierarchy for display, including cumulated counters,
 * last posts and the number of unread threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class BoardListBuilder
{
    private BoardNodeTree $tree;

    public function __construct(int $parentID = 0, int $maxDepth = -1)
    {
        $this->tree = new BoardNodeTree(
            $parentID,
            static fn(Board $board) => $board->isVisible(),
            $maxDepth
        );
    }

    /**
     * @return BoardNode[]
     */
    public function getNodes(): array
    {
        $nodes = $this->tree->getNodes();
        $flat = $this->tree->getFlatList();
        if ($flat === []) {
            return $nodes;
        }

        $boardIDs = \array_map(static fn(BoardNode $node) => $node->board->boardID, $flat);
        $counters = $this->getCounters($boardIDs);
        $lastThreads = $this->getLastThreads($flat);
        $unread = self::getUnreadThreadCounts();

        if (\FORUM_BOARD_LIST_ENABLE_MODERATORS) {
            $this->loadModerators($flat);
        }

        foreach ($flat as $node) {
            $board = $node->board;
            if ($board->isBoard() && $board->canEnter()) {
                $node->threads = $counters[$board->boardID]['threads'] ?? 0;
                $node->posts = $counters[$board->boardID]['posts'] ?? 0;
                $node->unreadThreads = $unread[$board->boardID] ?? 0;
                $node->lastThreads = $lastThreads[$board->boardID] ?? [];
            }
        }

        // cumulate the values of the children, starting with the deepest nodes
        foreach (\array_reverse($flat) as $node) {
            if ($node->parent === null) {
                continue;
            }

            $parent = $node->parent;
            $parent->threads += $node->threads;
            $parent->posts += $node->posts;
            $parent->unreadThreads += $node->unreadThreads;
            if ($node->lastThreads !== []) {
                $threads = \array_merge($parent->lastThreads, $node->lastThreads);
                \usort($threads, static fn(Thread $a, Thread $b) => $b->lastPostTime <=> $a->lastPostTime);
                $parent->lastThreads = \array_slice($threads, 0, \max(1, \FORUM_BOARD_LIST_LAST_THREADS));
            }
        }

        return $nodes;
    }

    /**
     * Assigns the users and groups that moderate the boards.
     *
     * @param BoardNode[] $nodes
     */
    private function loadModerators(array $nodes): void
    {
        $moderators = [];
        $userIDs = [];
        foreach ($nodes as $node) {
            $moderators[$node->board->boardID] = $node->board->getModerators();
            $userIDs = \array_merge($userIDs, $moderators[$node->board->boardID]['users']);
        }

        if ($userIDs === []) {
            $users = [];
        } else {
            $users = UserProfileRuntimeCache::getInstance()->getObjects(\array_unique($userIDs));
        }

        foreach ($nodes as $node) {
            foreach ($moderators[$node->board->boardID]['users'] as $userID) {
                if (isset($users[$userID])) {
                    $node->moderatorUsers[] = $users[$userID];
                }
            }

            foreach ($moderators[$node->board->boardID]['groups'] as $groupID) {
                $group = UserGroup::getGroupByID($groupID);
                if ($group !== null) {
                    $node->moderatorGroups[] = $group;
                }
            }
        }
    }

    /**
     * @param int[] $boardIDs
     * @return array<int, array{threads: int, posts: int}>
     */
    private function getCounters(array $boardIDs): array
    {
        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add('boardID IN (?)', [$boardIDs]);
        $sql = "SELECT  boardID, threads, posts
                FROM    forum1_board
                " . $conditions;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditions->getParameters());

        $counters = [];
        while ($row = $statement->fetchArray()) {
            $counters[$row['boardID']] = [
                'threads' => \intval($row['threads']),
                'posts' => \intval($row['posts']),
            ];
        }

        return $counters;
    }

    /**
     * Returns the threads with the most recent activity for each board.
     *
     * @param BoardNode[] $nodes
     * @return array<int, list<Thread>>
     */
    private function getLastThreads(array $nodes): array
    {
        $limit = \FORUM_BOARD_LIST_LAST_THREADS;
        if ($limit < 1) {
            return [];
        }

        $boardIDs = [];
        foreach ($nodes as $node) {
            $board = $node->board;
            if ($board->isBoard() && $board->canReadThreads() && $board->canReadPrivateThreads()) {
                $boardIDs[] = $board->boardID;
            }
        }
        if ($boardIDs === []) {
            return [];
        }

        $languageCondition = '';
        $languageParameters = [];
        if (LanguageFactory::getInstance()->multilingualismEnabled() && WCF::getUser()->getLanguageIDs() !== []) {
            $languageIDs = WCF::getUser()->getLanguageIDs();
            $languageCondition = 'AND (languageID IN (?' . \str_repeat(', ?', \count($languageIDs) - 1) . ') OR languageID IS NULL)';
            $languageParameters = $languageIDs;
        }

        $sql = "SELECT      threadID
                FROM        forum1_thread
                WHERE       boardID = ?
                        AND isDeleted = ?
                        AND isDisabled = ?
                        AND movedThreadID IS NULL
                        {$languageCondition}
                ORDER BY    lastPostTime DESC";
        $statement = WCF::getDB()->prepare($sql, $limit);

        $threadIDsByBoard = [];
        $threadIDs = [];
        foreach ($boardIDs as $boardID) {
            $statement->execute([$boardID, 0, 0, ...$languageParameters]);
            $threadIDsByBoard[$boardID] = \array_map('intval', $statement->fetchAll(\PDO::FETCH_COLUMN));
            $threadIDs = \array_merge($threadIDs, $threadIDsByBoard[$boardID]);
        }
        if ($threadIDs === []) {
            return [];
        }

        $threadList = new ViewableThreadList(null, false, false, false);
        $threadList->setObjectIDs($threadIDs);
        $threadList->readObjects();
        $threads = $threadList->getObjects();

        $lastThreads = [];
        foreach ($threadIDsByBoard as $boardID => $ids) {
            foreach ($ids as $threadID) {
                if (isset($threads[$threadID])) {
                    $lastThreads[$boardID][] = $threads[$threadID];
                }
            }
        }

        return $lastThreads;
    }

    /**
     * Returns the number of unread threads per board for the active user.
     *
     * @return array<int, int>
     */
    public static function getUnreadThreadCounts(): array
    {
        $userID = WCF::getUser()->userID;
        if (!$userID) {
            return [];
        }

        $data = UserStorageHandler::getInstance()->getField('forumUnreadThreads');
        if ($data !== null) {
            return JSON::decode($data);
        }

        $boardIDs = BoardPermissionHandler::getInstance()->getAccessibleBoardIDs();
        $counts = [];
        if ($boardIDs !== []) {
            $visitTracker = VisitTracker::getInstance();
            $conditions = new PreparedStatementConditionBuilder();
            $conditions->add('thread.boardID IN (?)', [$boardIDs]);
            $conditions->add('thread.lastPostTime > ?', [ThreadVisitHandler::getInstance()->getGlobalVisitTime()]);
            $conditions->add('thread.isDeleted = ?', [0]);
            $conditions->add('thread.isDisabled = ?', [0]);
            $conditions->add('thread.movedThreadID IS NULL');
            $conditions->add('(thread_visit.visitTime IS NULL OR thread_visit.visitTime < thread.lastPostTime)');
            $conditions->add('(board_visit.visitTime IS NULL OR board_visit.visitTime < thread.lastPostTime)');
            $conditions->add(
                "thread.threadID NOT IN (
                    SELECT  threadID
                    FROM    forum1_thread_user_status
                    WHERE   userID = ?
                        AND status = ?
                )",
                [$userID, 'ignoring']
            );

            $privateBoardIDs = [];
            foreach ($boardIDs as $boardID) {
                $board = BoardCache::getInstance()->getBoard($boardID);
                if ($board->isPrivate && !$board->canReadPrivateThreads()) {
                    $privateBoardIDs[] = $boardID;
                }
            }
            if ($privateBoardIDs !== []) {
                $conditions->add('(thread.boardID NOT IN (?) OR thread.userID = ?)', [$privateBoardIDs, $userID]);
            }

            if (LanguageFactory::getInstance()->multilingualismEnabled() && WCF::getUser()->getLanguageIDs() !== []) {
                $conditions->add(
                    '(thread.languageID IN (?) OR thread.languageID IS NULL)',
                    [WCF::getUser()->getLanguageIDs()]
                );
            }

            $sql = "SELECT      thread.boardID, COUNT(*) AS threads
                    FROM        forum1_thread thread
                    LEFT JOIN   wcf1_tracked_visit thread_visit
                    ON          thread_visit.objectTypeID = ?
                            AND thread_visit.objectID = thread.threadID
                            AND thread_visit.userID = ?
                    LEFT JOIN   wcf1_tracked_visit board_visit
                    ON          board_visit.objectTypeID = ?
                            AND board_visit.objectID = thread.boardID
                            AND board_visit.userID = ?
                    " . $conditions . "
                    GROUP BY    thread.boardID";
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute(\array_merge(
                [
                    $visitTracker->getObjectTypeID(ThreadVisitHandler::THREAD_OBJECT_TYPE),
                    $userID,
                    $visitTracker->getObjectTypeID(ThreadVisitHandler::BOARD_OBJECT_TYPE),
                    $userID,
                ],
                $conditions->getParameters()
            ));
            $counts = \array_map('intval', $statement->fetchMap('boardID', 'threads'));
        }

        UserStorageHandler::getInstance()->update($userID, 'forumUnreadThreads', JSON::encode($counts));

        return $counts;
    }

    /**
     * Returns the total number of unread threads, excluding ignored boards.
     */
    public static function getUnreadThreadCount(): int
    {
        $counts = self::getUnreadThreadCounts();
        foreach (BoardStatusHandler::getInstance()->getIgnoredBoardIDs() as $boardID) {
            unset($counts[$boardID]);
        }

        return \array_sum($counts);
    }
}
