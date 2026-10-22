<?php

namespace forum\system\thread;

use wcf\system\SingletonFactory;
use wcf\system\visitTracker\VisitTracker;
use wcf\system\WCF;

/**
 * Determines the unread state of threads, taking the visit times of the
 * thread, its board and the global "mark all as read" into account.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ThreadVisitHandler extends SingletonFactory
{
    public const THREAD_OBJECT_TYPE = 'com.tinsever.forum.thread';

    public const BOARD_OBJECT_TYPE = 'com.tinsever.forum.board';

    /**
     * @var array<int, int>
     */
    private array $boardVisitTimes;

    private int $globalVisitTime;

    #[\Override]
    protected function init()
    {
        $this->globalVisitTime = VisitTracker::getInstance()->getVisitTime(self::THREAD_OBJECT_TYPE);
    }

    /**
     * Returns the time after which content is considered unread, regardless of
     * any thread or board specific visit.
     */
    public function getGlobalVisitTime(): int
    {
        return $this->globalVisitTime;
    }

    public function getBoardVisitTime(int $boardID): int
    {
        if (!WCF::getUser()->userID) {
            return TIME_NOW;
        }

        if (!isset($this->boardVisitTimes)) {
            $sql = "SELECT  objectID, visitTime
                    FROM    wcf1_tracked_visit
                    WHERE   objectTypeID = ?
                        AND userID = ?";
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute([
                VisitTracker::getInstance()->getObjectTypeID(self::BOARD_OBJECT_TYPE),
                WCF::getUser()->userID,
            ]);
            $this->boardVisitTimes = $statement->fetchMap('objectID', 'visitTime');
        }

        return \max($this->globalVisitTime, $this->boardVisitTimes[$boardID] ?? 0);
    }

    /**
     * Returns the effective visit time for a thread.
     */
    public function getThreadVisitTime(int $boardID, ?int $threadVisitTime): int
    {
        if (!WCF::getUser()->userID) {
            return TIME_NOW;
        }

        return \max($this->getBoardVisitTime($boardID), $threadVisitTime ?? 0);
    }

    /**
     * Forgets cached board visit times after a board was marked as read.
     */
    public function reset(): void
    {
        unset($this->boardVisitTimes);
        $this->globalVisitTime = VisitTracker::getInstance()->getVisitTime(self::THREAD_OBJECT_TYPE);
    }
}
