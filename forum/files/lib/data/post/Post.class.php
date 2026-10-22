<?php

namespace forum\data\post;

use forum\data\thread\Thread;
use forum\page\ThreadPage;
use wcf\data\attachment\GroupedAttachmentList;
use wcf\data\CollectionDatabaseObject;
use wcf\data\IMessage;
use wcf\data\IPollContainer;
use wcf\data\IPopoverObject;
use wcf\data\TUserContent;
use wcf\data\user\UserProfile;
use wcf\system\html\output\HtmlOutputProcessor;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Represents a post.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @property-read int $postID
 * @property-read int $threadID
 * @property-read int|null $userID
 * @property-read string $username
 * @property-read string $subject
 * @property-read string $message
 * @property-read int $time
 * @property-read int $isDeleted
 * @property-read int $deleteTime
 * @property-read int $isDisabled
 * @property-read int $isClosed
 * @property-read int|null $editorID
 * @property-read string $editor
 * @property-read int $lastEditTime
 * @property-read int $editCount
 * @property-read string|null $editReason
 * @property-read int $attachments
 * @property-read int|null $pollID
 * @property-read int $enableHtml
 * @property-read string $ipAddress
 * @property-read int $cumulativeLikes
 * @property-read int $hasEmbeddedObjects
 *
 * @extends CollectionDatabaseObject<PostCollection>
 */
class Post extends CollectionDatabaseObject implements IMessage, IPopoverObject, IPollContainer
{
    use TUserContent;

    public const OBJECT_TYPE = 'com.tinsever.forum.post';

    /**
     * @inheritDoc
     */
    protected static $databaseTableName = 'post';

    /**
     * @inheritDoc
     */
    protected static $databaseTableIndexName = 'postID';

    #[\Override]
    public function getFormattedMessage(): string
    {
        $this->getCollection()->loadEmbeddedObjects();

        $processor = new HtmlOutputProcessor();
        $processor->process($this->message, self::OBJECT_TYPE, $this->postID);

        return $processor->getHtml();
    }

    /**
     * Returns a simplified version of the formatted message.
     */
    public function getSimplifiedFormattedMessage(): string
    {
        $this->getCollection()->loadEmbeddedObjects();

        $processor = new HtmlOutputProcessor();
        $processor->setOutputType('text/simplified-html');
        $processor->process($this->message, self::OBJECT_TYPE, $this->postID);

        return $processor->getHtml();
    }

    /**
     * Returns the message as plain text.
     */
    public function getPlainTextMessage(): string
    {
        $this->getCollection()->loadEmbeddedObjects();

        $processor = new HtmlOutputProcessor();
        $processor->setOutputType('text/plain');
        $processor->process($this->message, self::OBJECT_TYPE, $this->postID);

        return $processor->getHtml();
    }

    /**
     * Returns a version of this message optimized for use in emails.
     */
    public function getMailText(string $mimeType = 'text/plain'): string
    {
        return match ($mimeType) {
            'text/plain' => $this->getPlainTextMessage(),
            'text/html' => $this->getSimplifiedFormattedMessage(),
        };
    }

    #[\Override]
    public function getExcerpt($maxLength = 255): string
    {
        return StringUtil::truncateHTML($this->getSimplifiedFormattedMessage(), $maxLength);
    }

    public function getTeaser(int $length = 255): string
    {
        return StringUtil::truncate($this->getPlainTextMessage(), $length);
    }

    #[\Override]
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Returns the attachments of this post.
     */
    public function getAttachments(bool $ignoreCache = false): ?GroupedAttachmentList
    {
        if (!$this->attachments && !$ignoreCache) {
            return null;
        }

        $attachmentList = new GroupedAttachmentList(self::OBJECT_TYPE);
        $attachmentList->getConditionBuilder()->add('attachment.objectID IN (?)', [$this->postID]);
        $attachmentList->readObjects();
        $attachmentList->setPermissions([
            'canDownload' => $this->getThread()->getPermission('canDownloadAttachment'),
            'canViewPreview' => $this->getThread()->getPermission('canViewAttachmentPreview'),
        ]);

        if ($ignoreCache && !\count($attachmentList)) {
            return null;
        }

        return $attachmentList;
    }

    public function getThread(): Thread
    {
        return $this->getCollection()->getThread($this);
    }

    public function getUserProfile(): UserProfile
    {
        return $this->getCollection()->getUserProfile($this);
    }

    public function isFirstPost(): bool
    {
        return $this->getThread()->firstPostID == $this->postID;
    }

    public function isBestAnswer(): bool
    {
        return $this->getThread()->bestAnswerPostID == $this->postID;
    }

    #[\Override]
    public function getLink(): string
    {
        return LinkHandler::getInstance()->getControllerLink(ThreadPage::class, [
            'object' => $this->getThread(),
            'postID' => $this->postID,
            'forceFrontend' => true,
        ], '#post' . $this->postID);
    }

    #[\Override]
    public function getTitle(): string
    {
        if ($this->subject) {
            return $this->subject;
        }

        if ($this->isFirstPost()) {
            return $this->getThread()->topic;
        }

        return 'RE: ' . $this->getThread()->topic;
    }

    #[\Override]
    public function getPopoverLinkClass()
    {
        return 'forumPostLink';
    }

    #[\Override]
    public function isVisible(): bool
    {
        return $this->canRead();
    }

    public function isOwner(): bool
    {
        return WCF::getUser()->userID && $this->userID == WCF::getUser()->userID;
    }

    public function getPermission(string $permission): bool
    {
        return $this->getThread()->getPermission($permission);
    }

    /**
     * Returns true if the active user may read this post.
     */
    public function canRead(): bool
    {
        if (!$this->getThread()->canRead()) {
            return false;
        }

        if ($this->isDeleted && !$this->getPermission('canReadDeletedPost')) {
            return false;
        }

        if ($this->isDisabled && !$this->getPermission('canEnablePost') && !$this->isOwner()) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if the active user may edit this post.
     */
    public function canEdit(): bool
    {
        $thread = $this->getThread();
        if (!$thread->canRead()) {
            return false;
        }

        if ($this->getPermission('canEditPost')) {
            return true;
        }

        if (!$this->isOwner() || !$this->getPermission('canEditOwnPost')) {
            return false;
        }

        if ($this->isDeleted || $this->isClosed) {
            return false;
        }

        if (($thread->isClosed || $thread->getBoard()->isClosed) && !$this->getPermission('canReplyClosedThread')) {
            return false;
        }

        $timeLimit = \intval(WCF::getSession()->getPermission('user.forum.postEditTimeLimit'));
        if ($timeLimit > 0 && $this->time < TIME_NOW - $timeLimit * 60) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if the active user may soft-delete this post.
     */
    public function canTrash(): bool
    {
        if ($this->isDeleted) {
            return false;
        }

        if ($this->getPermission('canDeletePost')) {
            return true;
        }

        if (!$this->isOwner() || !$this->getPermission('canDeleteOwnPost') || $this->isClosed) {
            return false;
        }

        // the first post can only be removed together with the thread
        if ($this->isFirstPost()) {
            return false;
        }

        $thread = $this->getThread();
        if (($thread->isClosed || $thread->getBoard()->isClosed) && !$this->getPermission('canReplyClosedThread')) {
            return false;
        }

        $timeLimit = \intval(WCF::getSession()->getPermission('user.forum.postDeleteTimeLimit'));
        if ($timeLimit > 0 && $this->time < TIME_NOW - $timeLimit * 60) {
            return false;
        }

        return true;
    }

    public function canRestore(): bool
    {
        return $this->isDeleted && $this->getPermission('canRestorePost');
    }

    public function canDelete(): bool
    {
        return $this->getPermission('canDeletePostCompletely');
    }

    public function canEnable(): bool
    {
        return $this->getPermission('canEnablePost');
    }

    public function canClose(): bool
    {
        return $this->getPermission('canClosePost');
    }

    /**
     * Returns true if the active user can move, copy or split off this post.
     */
    public function canMove(): bool
    {
        return $this->getPermission('canMovePost');
    }

    public function canMerge(): bool
    {
        return $this->getPermission('canMergePost');
    }

    /**
     * Returns true if the active user can see the ip address of this post.
     */
    public function canViewIpAddress(): bool
    {
        return \LOG_IP_ADDRESS
            && $this->ipAddress
            && WCF::getSession()->getPermission('admin.user.canViewIpAddress');
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->getFormattedMessage();
    }

    #[\Override]
    public function getPollID()
    {
        return $this->pollID;
    }

    #[\Override]
    public function canVote()
    {
        $thread = $this->getThread();

        return $this->canRead()
            && !$this->isDeleted
            && !$thread->isClosed
            && $thread->getPermission('canVotePoll');
    }
}
