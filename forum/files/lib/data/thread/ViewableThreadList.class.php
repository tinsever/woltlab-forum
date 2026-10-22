<?php

namespace forum\data\thread;

use forum\system\thread\ThreadVisibility;
use forum\system\thread\ThreadVisitHandler;
use wcf\system\visitTracker\VisitTracker;
use wcf\system\WCF;

/**
 * Represents a list of threads that are visible to the active user, including
 * their unread state.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class ViewableThreadList extends ThreadList
{
    /**
     * @param ?int[] $boardIDs
     */
    public function __construct(
        ?array $boardIDs = null,
        bool $excludeIgnoredBoards = false,
        bool $excludeIgnoredThreads = true,
        bool $applyVisibility = true,
    ) {
        parent::__construct();

        if ($applyVisibility) {
            ThreadVisibility::addConditions($this->getConditionBuilder(), $boardIDs, 'thread', $excludeIgnoredBoards);
        } elseif ($boardIDs !== null) {
            $this->getConditionBuilder()->add('thread.boardID IN (?)', [$boardIDs ?: [0]]);
        }

        $userID = WCF::getUser()->userID;
        if ($userID) {
            $this->sqlSelects = 'tracked_visit.visitTime';
            $this->sqlJoins = "
                LEFT JOIN   wcf1_tracked_visit tracked_visit
                ON          tracked_visit.objectTypeID = " . VisitTracker::getInstance()->getObjectTypeID(ThreadVisitHandler::THREAD_OBJECT_TYPE) . "
                        AND tracked_visit.objectID = thread.threadID
                        AND tracked_visit.userID = " . $userID;

            if ($excludeIgnoredThreads) {
                $this->getConditionBuilder()->add(
                    "thread.threadID NOT IN (
                        SELECT  threadID
                        FROM    forum1_thread_user_status
                        WHERE   userID = ?
                            AND status = ?
                    )",
                    [$userID, 'ignoring']
                );
            }
        }
    }

    #[\Override]
    public function readObjects()
    {
        if ($this->objectIDs === null) {
            $this->readObjectIDs();
        }

        parent::readObjects();
    }
}
