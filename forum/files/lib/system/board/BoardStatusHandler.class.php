<?php

namespace forum\system\board;

use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\SingletonFactory;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;
use wcf\util\JSON;

/**
 * Manages the subscription and ignore status of boards for users.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class BoardStatusHandler extends SingletonFactory
{
    public const STATUS_WATCHING = 'watching';

    public const STATUS_IGNORING = 'ignoring';

    /**
     * @var array<int, array{status: string, enableNotification: int}>
     */
    private array $status;

    #[\Override]
    protected function init()
    {
        $this->status = [];

        $userID = WCF::getUser()->userID;
        if (!$userID) {
            return;
        }

        $data = UserStorageHandler::getInstance()->getField('forumBoardStatus');
        if ($data === null) {
            $sql = "SELECT  boardID, status, enableNotification
                    FROM    forum1_board_user_status
                    WHERE   userID = ?";
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute([$userID]);
            $status = [];
            while ($row = $statement->fetchArray()) {
                $status[$row['boardID']] = [
                    'status' => $row['status'],
                    'enableNotification' => \intval($row['enableNotification']),
                ];
            }

            UserStorageHandler::getInstance()->update($userID, 'forumBoardStatus', JSON::encode($status));
            $this->status = $status;
        } else {
            $this->status = JSON::decode($data);
        }
    }

    public function getStatus(int $boardID): ?string
    {
        return $this->status[$boardID]['status'] ?? null;
    }

    public function isSubscribed(int $boardID): bool
    {
        return $this->getStatus($boardID) === self::STATUS_WATCHING;
    }

    public function isIgnored(int $boardID): bool
    {
        return $this->getStatus($boardID) === self::STATUS_IGNORING;
    }

    public function hasNotification(int $boardID): bool
    {
        return $this->isSubscribed($boardID) && !empty($this->status[$boardID]['enableNotification']);
    }

    /**
     * @return int[]
     */
    public function getIgnoredBoardIDs(): array
    {
        return \array_keys(\array_filter(
            $this->status,
            static fn($status) => $status['status'] === self::STATUS_IGNORING
        ));
    }

    /**
     * @return int[]
     */
    public function getSubscribedBoardIDs(): array
    {
        return \array_keys(\array_filter(
            $this->status,
            static fn($status) => $status['status'] === self::STATUS_WATCHING
        ));
    }

    /**
     * Saves the status of a board for the given user, `null` removes any status.
     */
    public static function saveStatus(int $boardID, ?string $status, bool $enableNotification = true, ?int $userID = null): void
    {
        $userID ??= WCF::getUser()->userID;
        if (!$userID) {
            return;
        }

        if ($status === null) {
            $sql = "DELETE FROM forum1_board_user_status
                    WHERE       boardID = ?
                            AND userID = ?";
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute([$boardID, $userID]);
        } else {
            $sql = "INSERT INTO             forum1_board_user_status
                                            (boardID, userID, status, enableNotification)
                    VALUES                  (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE status = VALUES(status),
                                            enableNotification = VALUES(enableNotification)";
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute([$boardID, $userID, $status, $enableNotification ? 1 : 0]);
        }

        UserStorageHandler::getInstance()->reset([$userID], 'forumBoardStatus');
        UserStorageHandler::getInstance()->reset([$userID], 'forumUnreadThreads');

        if ($userID === WCF::getUser()->userID && self::isInitialized()) {
            $handler = self::getInstance();
            if ($status === null) {
                unset($handler->status[$boardID]);
            } else {
                $handler->status[$boardID] = [
                    'status' => $status,
                    'enableNotification' => $enableNotification ? 1 : 0,
                ];
            }
        }
    }

    /**
     * Returns the ids of the users that subscribed to the board with notifications enabled.
     *
     * @return int[]
     */
    public static function getSubscribers(int $boardID): array
    {
        $sql = "SELECT  userID
                FROM    forum1_board_user_status
                WHERE   boardID = ?
                    AND status = ?
                    AND enableNotification = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$boardID, self::STATUS_WATCHING, 1]);

        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Removes the users that ignore the given board from the list of user ids.
     *
     * @param int[] $userIDs
     * @return int[]
     */
    public static function filterIgnoredUserIDs(array $userIDs, int $boardID): array
    {
        if ($userIDs === []) {
            return [];
        }

        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add('boardID = ?', [$boardID]);
        $conditions->add('status = ?', [self::STATUS_IGNORING]);
        $conditions->add('userID IN (?)', [$userIDs]);

        $sql = "SELECT  userID
                FROM    forum1_board_user_status
                " . $conditions;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditions->getParameters());
        $ignoringUserIDs = $statement->fetchAll(\PDO::FETCH_COLUMN);

        return \array_values(\array_diff($userIDs, $ignoringUserIDs));
    }
}
