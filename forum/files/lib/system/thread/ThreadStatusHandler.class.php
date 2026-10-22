<?php

namespace forum\system\thread;

use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;

/**
 * Manages the subscription and ignore status of threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ThreadStatusHandler
{
    public const STATUS_WATCHING = 'watching';

    public const STATUS_IGNORING = 'ignoring';

    /**
     * Saves the status of a thread for a user, `null` removes any status.
     */
    public static function saveStatus(int $threadID, ?string $status, bool $enableNotification = true, ?int $userID = null): void
    {
        $userID ??= WCF::getUser()->userID;
        if (!$userID) {
            return;
        }

        if ($status === null) {
            $sql = "DELETE FROM forum1_thread_user_status
                    WHERE       threadID = ?
                            AND userID = ?";
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute([$threadID, $userID]);
        } else {
            $sql = "INSERT INTO             forum1_thread_user_status
                                            (threadID, userID, status, enableNotification)
                    VALUES                  (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE status = VALUES(status),
                                            enableNotification = VALUES(enableNotification)";
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute([$threadID, $userID, $status, $enableNotification ? 1 : 0]);
        }

        UserStorageHandler::getInstance()->reset([$userID], 'forumWatchedThreads');
        UserStorageHandler::getInstance()->reset([$userID], 'forumUnreadThreads');
    }

    /**
     * Returns the status of the thread for the given user.
     *
     * @return array{status: string, enableNotification: int}|null
     */
    public static function getStatus(int $threadID, ?int $userID = null): ?array
    {
        $userID ??= WCF::getUser()->userID;
        if (!$userID) {
            return null;
        }

        $sql = "SELECT  status, enableNotification
                FROM    forum1_thread_user_status
                WHERE   threadID = ?
                    AND userID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$threadID, $userID]);

        return $statement->fetchArray() ?: null;
    }

    /**
     * Returns the ids of the users that subscribed the thread with notifications enabled.
     *
     * @return int[]
     */
    public static function getSubscribers(int $threadID): array
    {
        $sql = "SELECT  userID
                FROM    forum1_thread_user_status
                WHERE   threadID = ?
                    AND status = ?
                    AND enableNotification = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$threadID, self::STATUS_WATCHING, 1]);

        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Removes the users that ignore the thread from the given list of user ids.
     *
     * @param int[] $userIDs
     * @return int[]
     */
    public static function filterIgnoredUserIDs(array $userIDs, int $threadID): array
    {
        if ($userIDs === []) {
            return [];
        }

        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add('threadID = ?', [$threadID]);
        $conditions->add('status = ?', [self::STATUS_IGNORING]);
        $conditions->add('userID IN (?)', [$userIDs]);
        $sql = "SELECT  userID
                FROM    forum1_thread_user_status
                " . $conditions;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditions->getParameters());

        return \array_values(\array_diff($userIDs, $statement->fetchAll(\PDO::FETCH_COLUMN)));
    }

    /**
     * Returns the number of threads the active user is watching.
     */
    public static function countWatchedThreads(): int
    {
        $userID = WCF::getUser()->userID;
        if (!$userID) {
            return 0;
        }

        $count = UserStorageHandler::getInstance()->getField('forumWatchedThreads');
        if ($count === null) {
            $sql = "SELECT  COUNT(*)
                    FROM    forum1_thread_user_status
                    WHERE   userID = ?
                        AND status = ?";
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute([$userID, self::STATUS_WATCHING]);
            $count = $statement->fetchSingleColumn();

            UserStorageHandler::getInstance()->update($userID, 'forumWatchedThreads', (string)$count);
        }

        return \intval($count);
    }
}
