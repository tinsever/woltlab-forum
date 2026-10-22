<?php

namespace forum\data\thread;

use forum\data\board\Board;
use forum\data\board\BoardCache;
use forum\data\post\Post;
use forum\page\ThreadPage;
use forum\system\cache\runtime\PostRuntimeCache;
use forum\system\thread\ThreadVisitHandler;
use wcf\data\CollectionDatabaseObject;
use wcf\data\IPopoverObject;
use wcf\data\label\Label;
use wcf\data\user\UserProfile;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;

/**
 * Represents a thread.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @property-read int $threadID
 * @property-read int $boardID
 * @property-read int|null $languageID
 * @property-read string $topic
 * @property-read int|null $firstPostID
 * @property-read int $time
 * @property-read int|null $userID
 * @property-read string $username
 * @property-read int|null $lastPostID
 * @property-read int $lastPostTime
 * @property-read int|null $lastPosterID
 * @property-read string $lastPoster
 * @property-read int $replies
 * @property-read int $views
 * @property-read int $attachments
 * @property-read int $polls
 * @property-read int $isAnnouncement
 * @property-read int $isSticky
 * @property-read int $isDisabled
 * @property-read int $isClosed
 * @property-read int $isDeleted
 * @property-read int $deleteTime
 * @property-read int|null $movedThreadID
 * @property-read int $movedTime
 * @property-read int $isDone
 * @property-read int|null $bestAnswerPostID
 * @property-read int $hasLabels
 * @property-read int $cumulativeLikes
 * @property-read int|null $visitTime available when read by `ViewableThreadList`
 *
 * @extends CollectionDatabaseObject<ThreadCollection>
 */
class Thread extends CollectionDatabaseObject implements IPopoverObject
{
    /**
     * @inheritDoc
     */
    protected static $databaseTableName = 'thread';

    /**
     * @inheritDoc
     */
    protected static $databaseTableIndexName = 'threadID';

    #[\Override]
    public function getTitle(): string
    {
        return $this->topic;
    }

    #[\Override]
    public function getLink(): string
    {
        return LinkHandler::getInstance()->getControllerLink(ThreadPage::class, [
            'object' => $this,
            'forceFrontend' => true,
        ]);
    }

    /**
     * Returns the link to the first unread post.
     */
    public function getFirstNewPostLink(): string
    {
        return LinkHandler::getInstance()->getControllerLink(ThreadPage::class, [
            'object' => $this,
            'action' => 'firstNew',
            'forceFrontend' => true,
        ]);
    }

    /**
     * Returns the link to the last post.
     */
    public function getLastPostLink(): string
    {
        return LinkHandler::getInstance()->getControllerLink(ThreadPage::class, [
            'object' => $this,
            'action' => 'lastPost',
            'forceFrontend' => true,
        ]);
    }

    #[\Override]
    public function getPopoverLinkClass()
    {
        return 'forumThreadLink';
    }

    public function getBoard(): Board
    {
        return BoardCache::getInstance()->getBoard($this->boardID);
    }

    public function isLink(): bool
    {
        return $this->movedThreadID !== null;
    }

    public function getUserProfile(): UserProfile
    {
        return $this->getCollection()->getUserProfile($this);
    }

    public function getLastPosterProfile(): UserProfile
    {
        return $this->getCollection()->getUserProfile($this, 'lastPosterID', 'lastPoster');
    }

    public function getFirstPost(): ?Post
    {
        if (!$this->firstPostID) {
            return null;
        }

        return PostRuntimeCache::getInstance()->getObject($this->firstPostID);
    }

    /**
     * @return Label[]
     */
    public function getLabels(): array
    {
        if (!$this->hasLabels) {
            return [];
        }

        return $this->getCollection()->getLabels($this);
    }

    public function isSubscribed(): bool
    {
        return $this->getCollection()->getUserStatus($this) === 'watching';
    }

    public function isIgnored(): bool
    {
        return $this->getCollection()->getUserStatus($this) === 'ignoring';
    }

    /**
     * Returns the time of the last visit of the active user.
     */
    public function getVisitTime(): int
    {
        return ThreadVisitHandler::getInstance()->getThreadVisitTime($this->boardID, $this->visitTime ?? null);
    }

    /**
     * Returns true if the thread contains posts that are unread by the active user.
     */
    public function isNew(): bool
    {
        if (!WCF::getUser()->userID || $this->isLink()) {
            return false;
        }

        return $this->lastPostTime > $this->getVisitTime();
    }

    /**
     * Returns the number of pages for the given number of posts per page.
     */
    public function getPages(?int $postsPerPage = null): int
    {
        $postsPerPage ??= $this->getBoard()->getPostsPerPage();

        return \intval(\ceil(($this->replies + 1) / $postsPerPage));
    }

    public function getPermission(string $permission): bool
    {
        return $this->getBoard()->getPermission($permission);
    }

    public function isOwner(): bool
    {
        return WCF::getUser()->userID && $this->userID == WCF::getUser()->userID;
    }

    /**
     * Returns true if the active user may read this thread.
     */
    public function canRead(): bool
    {
        $board = $this->getBoard();
        if (!$board->canReadThreads()) {
            return false;
        }

        if ($this->isDeleted && !$this->getPermission('canReadDeletedThread')) {
            return false;
        }

        if ($this->isDisabled && !$this->getPermission('canEnableThread') && !$this->isOwner()) {
            return false;
        }

        if ($board->isPrivate && !$board->canReadPrivateThreads() && !$this->isOwner()) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if the active user may write a reply.
     */
    public function canReply(): bool
    {
        if (!$this->canRead() || $this->isDeleted || $this->isLink()) {
            return false;
        }

        if ($this->isDisabled && !$this->getPermission('canEnableThread')) {
            return false;
        }

        if (($this->isClosed || $this->getBoard()->isClosed) && !$this->getPermission('canReplyClosedThread')) {
            return false;
        }

        if ($this->getPermission('canReplyThread')) {
            return true;
        }

        return $this->isOwner() && $this->getPermission('canReplyOwnThread');
    }

    /**
     * Returns true if the active user may edit the thread properties such as the topic.
     */
    public function canEdit(): bool
    {
        if ($this->getPermission('canEditThread')) {
            return true;
        }

        $firstPost = $this->getFirstPost();

        return $firstPost !== null && $firstPost->canEdit();
    }

    public function canClose(): bool
    {
        return $this->getPermission('canCloseThread');
    }

    public function canPin(): bool
    {
        return $this->getPermission('canPinThread');
    }

    public function canSetAnnouncement(): bool
    {
        return $this->getPermission('canStartAnnouncement');
    }

    public function canMove(): bool
    {
        return $this->getPermission('canMoveThread');
    }

    public function canMerge(): bool
    {
        return $this->getPermission('canMergeThread');
    }

    public function canTrash(): bool
    {
        return !$this->isDeleted && $this->getPermission('canDeleteThread');
    }

    public function canRestore(): bool
    {
        return $this->isDeleted && $this->getPermission('canRestoreThread');
    }

    public function canDelete(): bool
    {
        return $this->getPermission('canDeleteThreadCompletely');
    }

    public function canEnable(): bool
    {
        return $this->getPermission('canEnableThread');
    }

    /**
     * Returns true if the active user may mark the thread as done or undone.
     */
    public function canMarkAsDone(): bool
    {
        if (!$this->getBoard()->enableMarkingAsDone || $this->isLink()) {
            return false;
        }

        if ($this->getPermission('canMarkAsDoneThread')) {
            return true;
        }

        return $this->isOwner() && $this->getPermission('canMarkOwnThreadAsDone');
    }

    /**
     * Returns true if the active user may select the best answer.
     */
    public function canMarkBestAnswer(): bool
    {
        if (!$this->getBoard()->enableBestAnswer || $this->isLink()) {
            return false;
        }

        if ($this->getPermission('canMarkBestAnswer')) {
            return true;
        }

        return $this->isOwner() && $this->getPermission('canMarkOwnBestAnswer');
    }

    /**
     * Returns true if the active user may subscribe to this thread.
     */
    public function canSubscribe(): bool
    {
        return WCF::getUser()->userID && !$this->isLink();
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->getTitle();
    }
}
