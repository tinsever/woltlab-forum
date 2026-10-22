<?php

namespace forum\data\board;

use forum\page\BoardPage;
use forum\system\board\BoardPermissionHandler;
use forum\system\board\BoardStatusHandler;
use wcf\data\DatabaseObject;
use wcf\data\ITitledLinkObject;
use wcf\data\user\User;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Represents a board, a category or an external link in the board hierarchy.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @property-read int $boardID
 * @property-read int|null $parentID
 * @property-read int $position
 * @property-read int $boardType
 * @property-read string $title
 * @property-read string|null $description
 * @property-read int $descriptionUseHtml
 * @property-read string $externalURL
 * @property-read string $iconName
 * @property-read string $metaDescription
 * @property-read int $time
 * @property-read int|null $styleID
 * @property-read int $isClosed
 * @property-read int $isInvisible
 * @property-read int $isPrivate
 * @property-read int $countUserPosts
 * @property-read int $searchable
 * @property-read int $ignorable
 * @property-read int $showSubBoards
 * @property-read int $enableMarkingAsDone
 * @property-read int $enableBestAnswer
 * @property-read int $requireLabels
 * @property-read int $daysPrune
 * @property-read string $sortField
 * @property-read string $sortOrder
 * @property-read string $postSortOrder
 * @property-read int $threadsPerPage
 * @property-read int $postsPerPage
 * @property-read int $clicks
 * @property-read int $threads
 * @property-read int $posts
 */
class Board extends DatabaseObject implements ITitledLinkObject
{
    public const TYPE_BOARD = 0;

    public const TYPE_CATEGORY = 1;

    public const TYPE_LINK = 2;

    /**
     * Valid sort fields for the thread list.
     */
    public const SORT_FIELDS = ['topic', 'username', 'time', 'replies', 'views', 'lastPostTime', 'cumulativeLikes'];

    /**
     * @inheritDoc
     */
    protected static $databaseTableName = 'board';

    /**
     * @inheritDoc
     */
    protected static $databaseTableIndexName = 'boardID';

    public function isBoard(): bool
    {
        return $this->boardType == self::TYPE_BOARD;
    }

    public function isCategory(): bool
    {
        return $this->boardType == self::TYPE_CATEGORY;
    }

    public function isExternalLink(): bool
    {
        return $this->boardType == self::TYPE_LINK;
    }

    #[\Override]
    public function getTitle(): string
    {
        return WCF::getLanguage()->get($this->title);
    }

    /**
     * Returns the formatted description.
     */
    public function getFormattedDescription(): string
    {
        $description = WCF::getLanguage()->get((string)$this->description);
        if ($this->descriptionUseHtml) {
            return $description;
        }

        return \nl2br(StringUtil::encodeHTML($description), false);
    }

    /**
     * Returns the plain text description.
     */
    public function getPlainDescription(): string
    {
        $description = WCF::getLanguage()->get((string)$this->description);
        if ($this->descriptionUseHtml) {
            $description = StringUtil::stripHTML($description);
        }

        return $description;
    }

    #[\Override]
    public function getLink(): string
    {
        return LinkHandler::getInstance()->getControllerLink(BoardPage::class, [
            'object' => $this,
            'forceFrontend' => true,
        ]);
    }

    /**
     * Returns the icon that represents this board.
     */
    public function getIconName(): string
    {
        if ($this->iconName) {
            return $this->iconName;
        }

        return match (\intval($this->boardType)) {
            self::TYPE_CATEGORY => 'folder',
            self::TYPE_LINK => 'up-right-from-square',
            default => $this->isClosed ? 'lock' : 'folder',
        };
    }

    public function getParentBoard(): ?self
    {
        if (!$this->parentID) {
            return null;
        }

        return BoardCache::getInstance()->getBoard($this->parentID);
    }

    /**
     * Returns the list of parent boards, starting with the top-most board.
     *
     * @return self[]
     */
    public function getParentBoards(): array
    {
        $parents = [];
        $board = $this;
        while (($board = $board->getParentBoard()) !== null) {
            \array_unshift($parents, $board);
        }

        return $parents;
    }

    /**
     * Returns the ids of this board and all of its descendants.
     *
     * @return int[]
     */
    public function getAllChildIDs(): array
    {
        return BoardCache::getInstance()->getAllChildIDs($this->boardID);
    }

    /**
     * Returns the direct child boards.
     *
     * @return self[]
     */
    public function getChildBoards(): array
    {
        return BoardCache::getInstance()->getChildren($this->boardID);
    }

    /**
     * Returns the value of the given board permission for the active user or the given user.
     */
    public function getPermission(string $permission, ?User $user = null): bool
    {
        return BoardPermissionHandler::getInstance()->getPermission($this, $permission, $user);
    }

    /**
     * Returns true if the board is visible in the board list.
     */
    public function isVisible(?User $user = null): bool
    {
        if ($this->isInvisible && !$this->getPermission('canEnterBoard', $user)) {
            return false;
        }

        return $this->getPermission('canViewBoard', $user);
    }

    /**
     * Returns true if the board can be entered.
     */
    public function canEnter(?User $user = null): bool
    {
        return $this->getPermission('canViewBoard', $user) && $this->getPermission('canEnterBoard', $user);
    }

    /**
     * Returns true if threads in this board can be read.
     */
    public function canReadThreads(?User $user = null): bool
    {
        return $this->isBoard() && $this->canEnter($user) && $this->getPermission('canReadThread', $user);
    }

    /**
     * Returns true if the active user may start a new thread in this board.
     */
    public function canStartThread(): bool
    {
        if (!$this->isBoard() || !$this->canEnter()) {
            return false;
        }

        if ($this->isClosed && !$this->getPermission('canReplyClosedThread')) {
            return false;
        }

        return $this->getPermission('canStartThread');
    }

    /**
     * Returns true if the active user has any moderative permission in this board.
     */
    public function isModerator(): bool
    {
        return BoardPermissionHandler::getInstance()->isModerator($this);
    }

    /**
     * Returns true if the active user may see threads of other users in a private board.
     */
    public function canReadPrivateThreads(): bool
    {
        return !$this->isPrivate || $this->getPermission('canReadPrivateThread');
    }

    public function isIgnored(): bool
    {
        return BoardStatusHandler::getInstance()->isIgnored($this->boardID);
    }

    public function isSubscribed(): bool
    {
        return BoardStatusHandler::getInstance()->isSubscribed($this->boardID);
    }

    /**
     * Returns true if the active user may ignore this board.
     */
    public function canIgnore(): bool
    {
        return WCF::getUser()->userID && $this->ignorable && !$this->isCategory() && !$this->isExternalLink();
    }

    public function getThreadsPerPage(): int
    {
        if ($this->threadsPerPage) {
            return $this->threadsPerPage;
        }

        // @phpstan-ignore property.notFound
        if (WCF::getUser()->forumThreadsPerPage) {
            // @phpstan-ignore property.notFound
            return \intval(WCF::getUser()->forumThreadsPerPage);
        }

        return \FORUM_THREADS_PER_PAGE;
    }

    public function getPostsPerPage(): int
    {
        if ($this->postsPerPage) {
            return $this->postsPerPage;
        }

        // @phpstan-ignore property.notFound
        if (WCF::getUser()->forumPostsPerPage) {
            // @phpstan-ignore property.notFound
            return \intval(WCF::getUser()->forumPostsPerPage);
        }

        return \FORUM_POSTS_PER_PAGE;
    }

    public function getSortField(): string
    {
        if ($this->sortField && \in_array($this->sortField, self::SORT_FIELDS)) {
            return $this->sortField;
        }

        return \FORUM_THREAD_DEFAULT_SORT_FIELD;
    }

    public function getSortOrder(): string
    {
        if ($this->sortOrder === 'ASC' || $this->sortOrder === 'DESC') {
            return $this->sortOrder;
        }

        return \FORUM_THREAD_DEFAULT_SORT_ORDER;
    }

    public function getPostSortOrder(): string
    {
        if ($this->postSortOrder === 'ASC' || $this->postSortOrder === 'DESC') {
            return $this->postSortOrder;
        }

        return \FORUM_POST_DEFAULT_SORT_ORDER;
    }

    /**
     * Returns the users and groups that are moderators in this board.
     *
     * @return array{users: int[], groups: int[]}
     */
    public function getModerators(): array
    {
        return BoardCache::getInstance()->getModerators($this->boardID);
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->getTitle();
    }
}
