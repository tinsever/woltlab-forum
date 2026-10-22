<?php

namespace forum\system\thread;

use forum\data\board\BoardCache;
use forum\data\thread\Thread;
use wcf\data\user\User;
use forum\system\board\BoardPermissionHandler;
use forum\system\board\BoardStatusHandler;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;

/**
 * Builds the SQL conditions that restrict thread lists to the threads that are
 * visible to the active user.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ThreadVisibility
{
    /**
     * Adds the visibility conditions for threads.
     *
     * @param ?int[] $boardIDs restricts the result to these boards, `null` for all accessible boards
     */
    public static function addConditions(
        PreparedStatementConditionBuilder $conditions,
        ?array $boardIDs = null,
        string $alias = 'thread',
        bool $excludeIgnoredBoards = false,
        bool $filterLanguages = true,
    ): void {
        $handler = BoardPermissionHandler::getInstance();
        $accessibleBoardIDs = $handler->getAccessibleBoardIDs();
        if ($boardIDs !== null) {
            $accessibleBoardIDs = \array_values(\array_intersect($accessibleBoardIDs, $boardIDs));
        }
        if ($excludeIgnoredBoards) {
            $accessibleBoardIDs = \array_values(\array_diff(
                $accessibleBoardIDs,
                BoardStatusHandler::getInstance()->getIgnoredBoardIDs()
            ));
        }

        if ($accessibleBoardIDs === []) {
            $conditions->add('1 = 0');

            return;
        }

        $conditions->add("{$alias}.boardID IN (?)", [$accessibleBoardIDs]);

        $deletedBoardIDs = $disabledBoardIDs = $privateBoardIDs = [];
        foreach ($accessibleBoardIDs as $boardID) {
            $permissions = $handler->getPermissions($boardID);
            if (!empty($permissions['canReadDeletedThread'])) {
                $deletedBoardIDs[] = $boardID;
            }
            if (!empty($permissions['canEnableThread'])) {
                $disabledBoardIDs[] = $boardID;
            }

            $board = BoardCache::getInstance()->getBoard($boardID);
            if ($board->isPrivate && empty($permissions['canReadPrivateThread'])) {
                $privateBoardIDs[] = $boardID;
            }
        }

        $userID = WCF::getUser()->userID;

        if ($deletedBoardIDs === []) {
            $conditions->add("{$alias}.isDeleted = ?", [0]);
        } elseif (\count($deletedBoardIDs) !== \count($accessibleBoardIDs)) {
            $conditions->add("({$alias}.isDeleted = ? OR {$alias}.boardID IN (?))", [0, $deletedBoardIDs]);
        }

        if (\count($disabledBoardIDs) !== \count($accessibleBoardIDs)) {
            $sql = "{$alias}.isDisabled = ?";
            $parameters = [0];
            if ($disabledBoardIDs !== []) {
                $sql .= " OR {$alias}.boardID IN (?)";
                $parameters[] = $disabledBoardIDs;
            }
            if ($userID) {
                $sql .= " OR {$alias}.userID = ?";
                $parameters[] = $userID;
            }
            $conditions->add("({$sql})", $parameters);
        }

        if ($privateBoardIDs !== []) {
            if ($userID) {
                $conditions->add("({$alias}.boardID NOT IN (?) OR {$alias}.userID = ?)", [$privateBoardIDs, $userID]);
            } else {
                $conditions->add("{$alias}.boardID NOT IN (?)", [$privateBoardIDs]);
            }
        }

        if ($filterLanguages) {
            self::addLanguageCondition($conditions, $alias);
        }
    }

    /**
     * Restricts the threads to the content languages of the active user.
     */
    public static function addLanguageCondition(PreparedStatementConditionBuilder $conditions, string $alias = 'thread'): void
    {
        if (!LanguageFactory::getInstance()->multilingualismEnabled()) {
            return;
        }

        $languageIDs = WCF::getUser()->getLanguageIDs();
        if ($languageIDs === []) {
            return;
        }

        $conditions->add("({$alias}.languageID IN (?) OR {$alias}.languageID IS NULL)", [$languageIDs]);
    }

    /**
     * Returns true if the given user can read the thread, used to filter notification recipients.
     */
    public static function canUserRead(Thread $thread, User $user): bool
    {
        $board = BoardCache::getInstance()->getBoard($thread->boardID);
        if ($board === null || !$board->canReadThreads($user)) {
            return false;
        }

        $isOwner = $user->userID && $thread->userID == $user->userID;
        if ($thread->isDeleted && !$board->getPermission('canReadDeletedThread', $user)) {
            return false;
        }
        if ($thread->isDisabled && !$isOwner && !$board->getPermission('canEnableThread', $user)) {
            return false;
        }
        if ($board->isPrivate && !$isOwner && !$board->getPermission('canReadPrivateThread', $user)) {
            return false;
        }

        return true;
    }
}
