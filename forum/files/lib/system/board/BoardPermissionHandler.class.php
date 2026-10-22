<?php

namespace forum\system\board;

use forum\data\board\Board;
use forum\data\board\BoardCache;
use forum\system\cache\eager\BoardAclCache;
use forum\system\cache\eager\data\BoardAclCacheData;
use wcf\data\user\User;
use wcf\data\user\UserProfile;
use wcf\system\SingletonFactory;
use wcf\system\WCF;

/**
 * Resolves the board permissions of users.
 *
 * Board permissions are looked up in the following order, the first match wins:
 *  1. user specific ACL value of the board
 *  2. group ACL values of the board (a single granting group is sufficient)
 *  3. the resolved permissions of the parent board
 *  4. the global user group permission (`user.forum.*` or `mod.forum.*`)
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class BoardPermissionHandler extends SingletonFactory
{
    /**
     * Permissions that are granted to regular users.
     */
    public const USER_PERMISSIONS = [
        'canViewBoard',
        'canEnterBoard',
        'canReadThread',
        'canStartThread',
        'canReplyThread',
        'canReplyOwnThread',
        'canStartThreadWithoutModeration',
        'canReplyThreadWithoutModeration',
        'canEditOwnPost',
        'canDeleteOwnPost',
        'canUploadAttachment',
        'canDownloadAttachment',
        'canViewAttachmentPreview',
        'canStartPoll',
        'canVotePoll',
        'canSetTags',
        'canMarkOwnThreadAsDone',
        'canMarkOwnBestAnswer',
    ];

    /**
     * Moderative permissions.
     */
    public const MOD_PERMISSIONS = [
        'canEditPost',
        'canDeletePost',
        'canReadDeletedPost',
        'canRestorePost',
        'canDeletePostCompletely',
        'canEnablePost',
        'canClosePost',
        'canMovePost',
        'canMergePost',
        'canEditThread',
        'canCloseThread',
        'canPinThread',
        'canStartAnnouncement',
        'canMoveThread',
        'canMergeThread',
        'canDeleteThread',
        'canReadDeletedThread',
        'canRestoreThread',
        'canDeleteThreadCompletely',
        'canEnableThread',
        'canMarkAsDoneThread',
        'canMarkBestAnswer',
        'canReadPrivateThread',
        'canReplyClosedThread',
    ];

    private BoardAclCacheData $acl;

    /**
     * @var array<int, array<int, array<string, bool>>> user id => board id => permission => value
     */
    private array $permissions = [];

    /**
     * @var array<int, array<string, bool>> user id => permission => value
     */
    private array $globalPermissions = [];

    #[\Override]
    protected function init()
    {
        $this->acl = (new BoardAclCache())->getCache();
    }

    /**
     * Returns the value of a single board permission.
     */
    public function getPermission(Board $board, string $permission, ?User $user = null): bool
    {
        $permissions = $this->getPermissions($board->boardID, $user);

        return $permissions[$permission] ?? false;
    }

    /**
     * Returns all resolved permissions for the given board.
     *
     * @return array<string, bool>
     */
    public function getPermissions(int $boardID, ?User $user = null): array
    {
        $user ??= WCF::getUser();
        $userID = $user->userID ?: 0;

        if (!isset($this->permissions[$userID][$boardID])) {
            $board = BoardCache::getInstance()->getBoard($boardID);
            if ($board === null) {
                return [];
            }

            if ($board->parentID && BoardCache::getInstance()->getBoard($board->parentID) !== null) {
                $inherited = $this->getPermissions($board->parentID, $user);
            } else {
                $inherited = $this->getGlobalPermissions($user);
            }

            $permissions = $inherited;
            $groupValues = [];
            foreach ($user->getGroupIDs() as $groupID) {
                foreach ($this->acl->groups[$boardID][$groupID] ?? [] as $optionName => $value) {
                    $groupValues[$optionName] = ($groupValues[$optionName] ?? false) || $value;
                }
            }
            foreach ($groupValues as $optionName => $value) {
                $permissions[$optionName] = $value;
            }

            if ($userID) {
                foreach ($this->acl->users[$boardID][$userID] ?? [] as $optionName => $value) {
                    $permissions[$optionName] = (bool)$value;
                }
            }

            $this->permissions[$userID][$boardID] = $permissions;
        }

        return $this->permissions[$userID][$boardID];
    }

    /**
     * Returns the global user group permissions of the given user.
     *
     * @return array<string, bool>
     */
    private function getGlobalPermissions(User $user): array
    {
        $userID = $user->userID ?: 0;
        if (!isset($this->globalPermissions[$userID])) {
            $isActiveUser = $userID === (WCF::getUser()->userID ?: 0);
            $userProfile = $isActiveUser ? null : new UserProfile($user);

            $permissions = [];
            foreach (self::USER_PERMISSIONS as $permission) {
                $name = 'user.forum.' . $permission;
                $permissions[$permission] = (bool)($isActiveUser
                    ? WCF::getSession()->getPermission($name)
                    : $userProfile->getPermission($name));
            }
            foreach (self::MOD_PERMISSIONS as $permission) {
                $name = 'mod.forum.' . $permission;
                $permissions[$permission] = (bool)($isActiveUser
                    ? WCF::getSession()->getPermission($name)
                    : $userProfile->getPermission($name));
            }

            $this->globalPermissions[$userID] = $permissions;
        }

        return $this->globalPermissions[$userID];
    }

    /**
     * Returns true if the user has at least one moderative permission in the board.
     */
    public function isModerator(Board $board, ?User $user = null): bool
    {
        $permissions = $this->getPermissions($board->boardID, $user);
        foreach (self::MOD_PERMISSIONS as $permission) {
            if (!empty($permissions[$permission])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the ids of the boards in which the user has all of the given permissions.
     *
     * @param string[] $permissions
     * @return int[]
     */
    public function getBoardIDs(array $permissions, ?User $user = null): array
    {
        $boardIDs = [];
        foreach (BoardCache::getInstance()->getBoards() as $board) {
            $boardPermissions = $this->getPermissions($board->boardID, $user);
            foreach ($permissions as $permission) {
                if (empty($boardPermissions[$permission])) {
                    continue 2;
                }
            }

            $boardIDs[] = $board->boardID;
        }

        return $boardIDs;
    }

    /**
     * Returns the ids of all boards whose threads can be read by the user.
     *
     * @return int[]
     */
    public function getAccessibleBoardIDs(?User $user = null): array
    {
        $boardIDs = [];
        foreach ($this->getBoardIDs(['canViewBoard', 'canEnterBoard', 'canReadThread'], $user) as $boardID) {
            if (BoardCache::getInstance()->getBoard($boardID)->isBoard()) {
                $boardIDs[] = $boardID;
            }
        }

        return $boardIDs;
    }

    /**
     * Returns the users and groups that hold at least one moderative permission in the board.
     *
     * @return array{users: int[], groups: int[]}
     */
    public function getModerators(int $boardID): array
    {
        $users = $groups = [];
        foreach ($this->acl->users[$boardID] ?? [] as $userID => $options) {
            foreach (self::MOD_PERMISSIONS as $permission) {
                if (!empty($options[$permission])) {
                    $users[] = $userID;
                    break;
                }
            }
        }
        foreach ($this->acl->groups[$boardID] ?? [] as $groupID => $options) {
            foreach (self::MOD_PERMISSIONS as $permission) {
                if (!empty($options[$permission])) {
                    $groups[] = $groupID;
                    break;
                }
            }
        }

        return ['users' => $users, 'groups' => $groups];
    }

    /**
     * Rebuilds the ACL cache and discards the resolved permissions.
     */
    public static function resetCache(): void
    {
        (new BoardAclCache())->rebuild();

        if (self::isInitialized()) {
            $handler = self::getInstance();
            $handler->acl = (new BoardAclCache())->getFreshData();
            $handler->permissions = [];
        }
    }
}
