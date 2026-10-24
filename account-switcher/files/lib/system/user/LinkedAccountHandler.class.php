<?php

namespace wcf\system\user;

use wcf\form\AccountSwitcherMultifactorForm;
use wcf\data\user\User;
use wcf\data\user\UserProfile;
use wcf\event\user\authentication\UserLoggedIn;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\conversation\ConversationHandler;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\event\EventHandler;
use wcf\system\exception\NamedUserException;
use wcf\system\request\LinkHandler;
use wcf\system\SingletonFactory;
use wcf\system\WCF;
use wcf\util\HtmlString;

/**
 * Manages linked accounts and switching between them.
 *
 * Linked accounts form a group: linking A with B and B with C puts all three
 * accounts into the same group, therefore A can switch to C (and vice versa)
 * without any further link. Linking two accounts that already belong to
 * different groups merges both groups.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class LinkedAccountHandler extends SingletonFactory
{
    /**
     * Session variable holding a switch or link that waits for the multi-factor
     * authentication of the other account.
     */
    private const PENDING_AUTHENTICATION_KEY = 'accountSwitcherPendingAuthentication';

    /**
     * Seconds a pending multi-factor authentication stays valid.
     */
    private const PENDING_AUTHENTICATION_LIFETIME = 10 * 60;

    /**
     * @var array<int, int[]>
     */
    private array $groupMembers = [];

    /**
     * Returns true if the switcher is enabled and the active user may use it.
     */
    public function isAvailable(): bool
    {
        return MODULE_ACCOUNT_SWITCHER
            && WCF::getUser()->userID
            && WCF::getSession()->getPermission('user.profile.canSwitchAccounts');
    }

    /**
     * Returns the ids of all accounts linked with the given user, including
     * the user themselves. Returns an empty list for unlinked accounts.
     *
     * @return int[]
     */
    public function getGroupUserIDs(int $userID): array
    {
        if (!isset($this->groupMembers[$userID])) {
            $sql = "SELECT      other.userID
                    FROM        wcf1_account_switcher_member self
                    INNER JOIN  wcf1_account_switcher_member other
                    ON          other.groupID = self.groupID
                    WHERE       self.userID = ?";
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute([$userID]);
            $userIDs = \array_map('intval', $statement->fetchAll(\PDO::FETCH_COLUMN));

            // A group with a single member is a leftover of deleted accounts.
            if (\count($userIDs) < 2) {
                $userIDs = [];
            }

            foreach ($userIDs ?: [$userID] as $memberID) {
                $this->groupMembers[$memberID] = $userIDs;
            }
        }

        return $this->groupMembers[$userID];
    }

    /**
     * Returns the accounts the given user can switch to, sorted by name.
     *
     * @return UserProfile[]
     */
    public function getLinkedAccounts(int $userID): array
    {
        $userIDs = \array_values(\array_diff($this->getGroupUserIDs($userID), [$userID]));
        if ($userIDs === []) {
            return [];
        }

        $accounts = \array_filter(UserProfileRuntimeCache::getInstance()->getObjects($userIDs));
        \usort(
            $accounts,
            static fn(UserProfile $a, UserProfile $b) => \strnatcasecmp($a->username, $b->username)
        );

        return $accounts;
    }

    /**
     * Returns true if both accounts belong to the same group.
     */
    public function isLinked(int $userID, int $otherUserID): bool
    {
        return $userID !== $otherUserID
            && \in_array($otherUserID, $this->getGroupUserIDs($userID), true);
    }

    /**
     * Returns the time the given account joined its group.
     *
     * @param int[] $userIDs
     * @return array<int, int>
     */
    public function getLinkTimes(array $userIDs): array
    {
        if ($userIDs === []) {
            return [];
        }

        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add('userID IN (?)', [$userIDs]);
        $sql = "SELECT  userID, time
                FROM    wcf1_account_switcher_member
                {$conditions}";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditions->getParameters());

        return \array_map('intval', $statement->fetchMap('userID', 'time'));
    }

    /**
     * Returns the reason why the given accounts cannot be linked or `null` if
     * they can. The reason is the suffix of the phrase
     * `wcf.accountSwitcher.link.error.*`.
     */
    public function getLinkError(User $user, User $target): ?string
    {
        if ($user->userID === $target->userID) {
            return 'self';
        }
        if ($this->isLinked($user->userID, $target->userID)) {
            return 'alreadyLinked';
        }
        if ($target->banned) {
            return 'banned';
        }
        if (!(new UserProfile($target))->getPermission('user.profile.canSwitchAccounts')) {
            return 'notPermitted';
        }

        $limit = ACCOUNT_SWITCHER_MAX_ACCOUNTS;
        if ($limit) {
            $size = \count($this->getGroupUserIDs($user->userID)) ?: 1;
            $otherSize = \count($this->getGroupUserIDs($target->userID)) ?: 1;
            if ($size + $otherSize > $limit) {
                return 'limit';
            }
        }

        return null;
    }

    /**
     * Links both accounts, merging their groups if necessary.
     */
    public function link(User $user, User $target): void
    {
        $error = $this->getLinkError($user, $target);
        if ($error !== null) {
            throw new NamedUserException(HtmlString::fromSafeHtml(
                WCF::getLanguage()->getDynamicVariable('wcf.accountSwitcher.link.error.' . $error, [
                    'limit' => ACCOUNT_SWITCHER_MAX_ACCOUNTS,
                ])
            ));
        }

        WCF::getDB()->beginTransaction();
        try {
            $groupIDs = $this->getGroupIDs([$user->userID, $target->userID]);
            $groupID = $groupIDs[$user->userID] ?? $groupIDs[$target->userID] ?? null;
            if ($groupID === null) {
                $sql = "INSERT INTO wcf1_account_switcher_group (time) VALUES (?)";
                $statement = WCF::getDB()->prepare($sql);
                $statement->execute([TIME_NOW]);
                $groupID = (int)WCF::getDB()->getInsertID('wcf1_account_switcher_group', 'groupID');
            }

            // Merge the other group into this one, keeping the original link times.
            foreach ($groupIDs as $otherGroupID) {
                if ($otherGroupID !== $groupID) {
                    $sql = "UPDATE  wcf1_account_switcher_member
                            SET     groupID = ?
                            WHERE   groupID = ?";
                    $statement = WCF::getDB()->prepare($sql);
                    $statement->execute([$groupID, $otherGroupID]);
                }
            }

            $sql = "INSERT IGNORE INTO  wcf1_account_switcher_member
                                        (userID, groupID, time)
                    VALUES              (?, ?, ?)";
            $statement = WCF::getDB()->prepare($sql);
            foreach ([$user->userID, $target->userID] as $userID) {
                if (!isset($groupIDs[$userID])) {
                    $statement->execute([$userID, $groupID, TIME_NOW]);
                }
            }

            $this->deleteEmptyGroups();

            WCF::getDB()->commitTransaction();
        } catch (\Throwable $e) {
            WCF::getDB()->rollBackTransaction();

            throw $e;
        }

        $this->groupMembers = [];
    }

    /**
     * Removes the given account from its group. The remaining accounts stay
     * linked with each other.
     */
    public function unlink(int $userID): void
    {
        $groupUserIDs = $this->getGroupUserIDs($userID);

        WCF::getDB()->beginTransaction();
        $sql = "DELETE FROM wcf1_account_switcher_member
                WHERE       userID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$userID]);

        // A single remaining account is not linked with anything anymore.
        $remaining = \array_values(\array_diff($groupUserIDs, [$userID]));
        if (\count($remaining) === 1) {
            $statement->execute([$remaining[0]]);
        }

        $this->deleteEmptyGroups();
        WCF::getDB()->commitTransaction();

        $this->groupMembers = [];
    }

    /**
     * Returns the reason why the active user cannot switch to the given account
     * or `null` if they can. The reason is the suffix of the phrase
     * `wcf.accountSwitcher.switch.error.*`.
     */
    public function getSwitchError(User $target): ?string
    {
        if (!$target->userID || !$this->isLinked(WCF::getUser()->userID, $target->userID)) {
            return 'notLinked';
        }
        if ($target->banned) {
            return 'banned';
        }

        return null;
    }

    /**
     * Logs the active user into the given account. Accounts with multi-factor
     * authentication require the authentication first, the url of the
     * authentication form is returned in this case.
     */
    public function switchTo(User $target, string $returnUrl): ?string
    {
        if ($target->multifactorActive) {
            return $this->startMultifactorAuthentication($target, false, $returnUrl);
        }

        $this->changeUser($target);

        return null;
    }

    /**
     * Changes the active user without any further checks.
     */
    public function changeUser(User $target): void
    {
        WCF::getSession()->changeUser($target);
        EventHandler::getInstance()->fire(new UserLoggedIn($target));
    }

    /**
     * Asks for the multi-factor authentication of the given account before
     * switching to it or linking it. The active user stays logged in until the
     * authentication succeeded. Returns the url of the authentication form.
     */
    public function startMultifactorAuthentication(User $target, bool $link, string $returnUrl): string
    {
        WCF::getSession()->register(self::PENDING_AUTHENTICATION_KEY, [
            'userID' => WCF::getUser()->userID,
            'targetUserID' => $target->userID,
            'link' => $link,
            'returnUrl' => $returnUrl,
            'expires' => TIME_NOW + self::PENDING_AUTHENTICATION_LIFETIME,
        ]);

        return LinkHandler::getInstance()->getControllerLink(AccountSwitcherMultifactorForm::class);
    }

    /**
     * Returns the pending multi-factor authentication of the active user.
     *
     * @return ?array{target: User, link: bool, returnUrl: string}
     */
    public function getPendingAuthentication(): ?array
    {
        $data = WCF::getSession()->getVar(self::PENDING_AUTHENTICATION_KEY);
        if (
            !\is_array($data)
            || $data['expires'] < TIME_NOW
            || $data['userID'] !== WCF::getUser()->userID
        ) {
            return null;
        }

        $target = new User($data['targetUserID']);
        if (!$target->userID) {
            return null;
        }

        return [
            'target' => $target,
            'link' => $data['link'],
            'returnUrl' => $data['returnUrl'],
        ];
    }

    public function clearPendingAuthentication(): void
    {
        WCF::getSession()->unregister(self::PENDING_AUTHENTICATION_KEY);
    }

    /**
     * Returns the number of unread notifications and conversations of each account.
     *
     * @param int[] $userIDs
     * @return array<int, array{notifications: int, conversations: int}>
     */
    public function getUnreadCounts(array $userIDs): array
    {
        $counts = [];
        foreach ($userIDs as $userID) {
            $counts[$userID] = ['notifications' => 0, 'conversations' => 0];
        }
        if ($userIDs === []) {
            return $counts;
        }

        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add('userID IN (?)', [$userIDs]);
        $conditions->add('confirmTime = ?', [0]);
        $sql = "SELECT      userID, COUNT(*) AS count
                FROM        wcf1_user_notification
                {$conditions}
                GROUP BY    userID";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditions->getParameters());
        foreach ($statement->fetchMap('userID', 'count') as $userID => $count) {
            $counts[$userID]['notifications'] = (int)$count;
        }

        if ($this->hasConversations()) {
            foreach ($userIDs as $userID) {
                $counts[$userID]['conversations'] = ConversationHandler::getInstance()
                    ->getUnreadConversationCount($userID);
            }
        }

        return $counts;
    }

    /**
     * Returns the total number of unread items of all accounts linked with the
     * active user, excluding the active account.
     */
    public function getUnreadCountOfOtherAccounts(): int
    {
        $userIDs = \array_values(\array_diff(
            $this->getGroupUserIDs(WCF::getUser()->userID),
            [WCF::getUser()->userID]
        ));

        $total = 0;
        foreach ($this->getUnreadCounts($userIDs) as $count) {
            $total += $count['notifications'] + $count['conversations'];
        }

        return $total;
    }

    private function hasConversations(): bool
    {
        return \defined('MODULE_CONVERSATION')
            && MODULE_CONVERSATION
            && \class_exists(ConversationHandler::class);
    }

    /**
     * @param int[] $userIDs
     * @return array<int, int>
     */
    private function getGroupIDs(array $userIDs): array
    {
        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add('userID IN (?)', [$userIDs]);
        $sql = "SELECT  userID, groupID
                FROM    wcf1_account_switcher_member
                {$conditions}
                FOR UPDATE";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditions->getParameters());

        return \array_map('intval', $statement->fetchMap('userID', 'groupID'));
    }

    private function deleteEmptyGroups(): void
    {
        $sql = "DELETE FROM wcf1_account_switcher_group
                WHERE       groupID NOT IN (
                                SELECT  groupID
                                FROM    wcf1_account_switcher_member
                            )";
        WCF::getDB()->prepare($sql)->execute();
    }
}
