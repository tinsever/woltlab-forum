<?php

namespace forum\data\post;

use forum\data\board\BoardCache;
use forum\data\board\BoardEditor;
use forum\data\thread\Thread;
use forum\data\thread\ThreadAction;
use forum\data\thread\ThreadEditor;
use forum\system\cache\runtime\PostRuntimeCache;
use forum\system\cache\runtime\ThreadRuntimeCache;
use forum\system\log\modification\PostModificationLogHandler;
use forum\system\interaction\user\PostInteractions;
use forum\system\post\PostNotificationHandler;
use forum\system\thread\ThreadStatusHandler;
use forum\system\thread\ThreadVisitHandler;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\DatabaseObject;
use wcf\data\IAttachmentMessageQuickReplyAction;
use wcf\data\IMessageInlineEditorAction;
use wcf\data\TMessageQuickReplyGuestDialogAction;
use wcf\data\smiley\SmileyCache;
use wcf\event\message\MessageSpamChecking;
use wcf\system\attachment\AttachmentHandler;
use wcf\system\bbcode\BBCodeHandler;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\edit\EditHistoryManager;
use wcf\system\event\EventHandler;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\flood\FloodControl;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\html\upcast\HtmlUpcastProcessor;
use wcf\system\interaction\InteractionContextMenuComponent;
use wcf\system\message\censorship\Censorship;
use wcf\system\message\embedded\object\MessageEmbeddedObjectManager;
use wcf\system\message\QuickReplyManager;
use wcf\system\message\quote\MessageQuoteManager;
use wcf\system\moderation\queue\ModerationQueueActivationManager;
use wcf\system\moderation\queue\ModerationQueueManager;
use wcf\system\poll\PollManager;
use wcf\system\search\SearchIndexManager;
use wcf\system\user\activity\event\UserActivityEventHandler;
use wcf\system\user\activity\point\UserActivityPointHandler;
use wcf\system\reaction\ReactionHandler;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\system\visitTracker\VisitTracker;
use wcf\system\WCF;
use wcf\util\StringUtil;
use wcf\util\UserUtil;

/**
 * Executes post-related actions.
 *
 * Permission checks for moderative actions are performed by the RPC endpoints,
 * the methods of this class only implement the state transitions.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractDatabaseObjectAction<Post, PostEditor>
 * @implements IAttachmentMessageQuickReplyAction<Thread, Post, ViewablePostList>
 */
class PostAction extends AbstractDatabaseObjectAction implements
    IAttachmentMessageQuickReplyAction,
    IMessageInlineEditorAction
{
    use TMessageQuickReplyGuestDialogAction;

    private const QUICK_REPLY_GUEST_DIALOG_ID = 'forumQuickReplyGuestDialog';

    /**
     * @inheritDoc
     */
    protected $className = PostEditor::class;

    /**
     * @inheritDoc
     */
    protected $allowGuestAccess = ['quickReply', 'beginEdit', 'save'];

    public ?HtmlInputProcessor $htmlInputProcessor = null;

    public ?Thread $thread = null;

    public ?Post $post = null;

    /**
     * true if the last `create` call appended the message to the previous post of the author
     */
    public bool $mergedIntoPreviousPost = false;

    /**
     * @inheritDoc
     */
    public function create()
    {
        $data = &$this->parameters['data'];
        $data['enableHtml'] ??= 1;

        if (isset($this->parameters['attachmentHandler'])) {
            $data['attachments'] = \count($this->parameters['attachmentHandler']);
        }

        if (\LOG_IP_ADDRESS) {
            $data['ipAddress'] ??= UserUtil::getIpAddress();
        } else {
            unset($data['ipAddress']);
        }

        if (!empty($this->parameters['htmlInputProcessor'])) {
            $data['message'] = $this->parameters['htmlInputProcessor']->getHtml();
        }

        $thread = $this->parameters['thread'] ?? ThreadRuntimeCache::getInstance()->getObject($data['threadID']);

        $previousPost = $this->getMergeablePreviousPost($thread);
        if ($previousPost !== null) {
            unset($data);

            return $this->appendToPost($previousPost);
        }

        if (!isset($data['isDisabled'])) {
            $data['isDisabled'] = $this->requiresModeration($thread) ? 1 : 0;
        }
        unset($data);

        /** @var Post $post */
        $post = parent::create();
        $postEditor = new PostEditor($post);
        $isFirstPost = !empty($this->parameters['isFirstPost']);

        if (isset($this->parameters['attachmentHandler'])) {
            $this->parameters['attachmentHandler']->updateObjectID($post->postID);
        }

        if (!empty($this->parameters['htmlInputProcessor'])) {
            $this->parameters['htmlInputProcessor']->setObjectID($post->postID);
            if (MessageEmbeddedObjectManager::getInstance()->registerObjects($this->parameters['htmlInputProcessor'])) {
                $postEditor->update(['hasEmbeddedObjects' => 1]);
            }
        }

        if (!empty($this->parameters['pollData'])) {
            PollManager::getInstance()->setObject(Post::OBJECT_TYPE, $post->postID);
            $pollID = PollManager::getInstance()->savePoll($post, $this->parameters['pollData']);
            if ($pollID) {
                $postEditor->update(['pollID' => $pollID]);
            }
        }

        if (!empty($this->parameters['removeQuoteIDs'])) {
            MessageQuoteManager::getInstance()->markQuotesForRemoval($this->parameters['removeQuoteIDs']);
        }
        MessageQuoteManager::getInstance()->removeMarkedQuotes();

        $post = new Post($post->postID);
        $board = BoardCache::getInstance()->getBoard($thread->boardID);

        SearchIndexManager::getInstance()->set(
            Post::OBJECT_TYPE,
            $post->postID,
            $post->message,
            $isFirstPost ? $thread->topic : $post->subject,
            $post->time,
            $post->userID,
            $post->username,
            $thread->languageID
        );

        if ($post->isDisabled) {
            if (!$isFirstPost) {
                ModerationQueueActivationManager::getInstance()->addModeratedContent(Post::OBJECT_TYPE, $post->postID);
            }
        } elseif (!$isFirstPost) {
            // the thread action takes care of the first post
            $threadEditor = new ThreadEditor($thread);
            $threadEditor->addPost($post);

            $boardEditor = new BoardEditor($board);
            $boardEditor->updateCounters(['posts' => 1]);
            $boardEditor->updateLastPost();

            self::addPostCounters([$post]);

            if ($post->userID) {
                UserActivityEventHandler::getInstance()->fireEvent(
                    'com.tinsever.forum.recentActivityEvent.post',
                    $post->postID,
                    $thread->languageID,
                    $post->userID,
                    $post->time
                );
            }

            PostNotificationHandler::getInstance()->firePostCreated($post, $thread, $this->parameters['htmlInputProcessor'] ?? null);
        }

        if ($post->userID) {
            VisitTracker::getInstance()->trackObjectVisitByUserIDs(
                ThreadVisitHandler::THREAD_OBJECT_TYPE,
                $thread->threadID,
                [$post->userID],
                $post->time
            );

            if (!empty($this->parameters['subscribeThread']) && !$thread->isLink()) {
                ThreadStatusHandler::saveStatus($thread->threadID, ThreadStatusHandler::STATUS_WATCHING, true, $post->userID);
            }
        }

        ThreadEditor::resetUnreadCounters();

        return $post;
    }

    /**
     * Returns the previous post of the author if the new reply should be appended to it.
     */
    protected function getMergeablePreviousPost(Thread $thread): ?Post
    {
        if (!\FORUM_POST_MERGE_DOUBLE_POSTS || !empty($this->parameters['isFirstPost'])) {
            return null;
        }

        $userID = $this->parameters['data']['userID'] ?? null;
        if (!$userID || empty($this->parameters['htmlInputProcessor']) || !empty($this->parameters['pollData']['question'])) {
            return null;
        }
        if (isset($this->parameters['attachmentHandler']) && \count($this->parameters['attachmentHandler'])) {
            return null;
        }
        if (!$thread->lastPostID) {
            return null;
        }

        $post = new Post($thread->lastPostID);
        if (
            !$post->postID
            || $post->userID != $userID
            || $post->isDeleted
            || $post->isDisabled
            || $post->isClosed
            || $post->time < TIME_NOW - \FORUM_POST_MERGE_DOUBLE_POSTS * 60
        ) {
            return null;
        }

        return $post;
    }

    /**
     * Appends the new message to an existing post.
     */
    protected function appendToPost(Post $post): Post
    {
        $htmlInputProcessor = new HtmlInputProcessor();
        $htmlInputProcessor->processIntermediate(
            $post->message . '<p><br></p>' . $this->parameters['htmlInputProcessor']->getHtml(),
            Post::OBJECT_TYPE,
            $post->postID
        );

        (new self([$post], 'update', [
            'htmlInputProcessor' => $htmlInputProcessor,
        ]))->executeAction();

        if (!empty($this->parameters['removeQuoteIDs'])) {
            MessageQuoteManager::getInstance()->markQuotesForRemoval($this->parameters['removeQuoteIDs']);
        }
        MessageQuoteManager::getInstance()->removeMarkedQuotes();

        $this->mergedIntoPreviousPost = true;
        ThreadEditor::resetUnreadCounters();

        return new Post($post->postID);
    }

    /**
     * Returns true if a new post by the active user must be approved by a moderator.
     */
    protected function requiresModeration(Thread $thread): bool
    {
        if (!empty($this->parameters['isFirstPost'])) {
            return !empty($this->parameters['threadIsDisabled']);
        }

        return !$thread->getPermission('canReplyThreadWithoutModeration')
            && !$thread->getPermission('canEnablePost');
    }

    /**
     * Increments the post counter of the authors.
     *
     * @param Post[] $posts
     */
    public static function addPostCounters(array $posts): void
    {
        $counters = [];
        $points = [];
        foreach ($posts as $post) {
            if (!$post->userID) {
                continue;
            }

            $thread = ThreadRuntimeCache::getInstance()->getObject($post->threadID)
                ?? new Thread($post->threadID);
            $board = BoardCache::getInstance()->getBoard($thread->boardID);
            if ($board !== null && $board->countUserPosts) {
                $counters[$post->userID] = ($counters[$post->userID] ?? 0) + 1;
            }

            $points[$post->userID][] = $post->postID;
        }

        if ($counters !== []) {
            $sql = "UPDATE  wcf1_user
                    SET     forumPosts = forumPosts + ?
                    WHERE   userID = ?";
            $statement = WCF::getDB()->prepare($sql);
            WCF::getDB()->beginTransaction();
            foreach ($counters as $userID => $count) {
                $statement->execute([$count, $userID]);
            }
            WCF::getDB()->commitTransaction();
        }

        if ($points !== []) {
            UserActivityPointHandler::getInstance()->fireEvents(
                'com.tinsever.forum.activityPointEvent.post',
                \array_map('count', $points)
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function update()
    {
        if (isset($this->parameters['attachmentHandler'])) {
            $this->parameters['data']['attachments'] = \count($this->parameters['attachmentHandler']);
        }

        if (!empty($this->parameters['htmlInputProcessor'])) {
            $this->parameters['data']['message'] = $this->parameters['htmlInputProcessor']->getHtml();
        }

        if (empty($this->objects)) {
            $this->readObjects();
        }

        // edit history
        if (isset($this->parameters['data']['message']) && \MODULE_EDIT_HISTORY && !empty($this->parameters['isEdit'])) {
            foreach ($this->getObjects() as $post) {
                if ($post->message === $this->parameters['data']['message']) {
                    continue;
                }

                EditHistoryManager::getInstance()->add(
                    Post::OBJECT_TYPE,
                    $post->postID,
                    $post->message,
                    $post->lastEditTime ?: $post->time,
                    $post->editorID ?: $post->userID,
                    $post->editor ?: $post->username,
                    $post->editReason ?? '',
                    WCF::getUser()->userID
                );
            }
        }

        parent::update();

        foreach ($this->getObjects() as $post) {
            if (isset($this->parameters['data']['message']) || isset($this->parameters['data']['subject'])) {
                $updatedPost = new Post($post->postID);
                $thread = $updatedPost->getThread();

                SearchIndexManager::getInstance()->set(
                    Post::OBJECT_TYPE,
                    $updatedPost->postID,
                    $updatedPost->message,
                    $thread->firstPostID == $updatedPost->postID ? $thread->topic : $updatedPost->subject,
                    $updatedPost->time,
                    $updatedPost->userID,
                    $updatedPost->username,
                    $thread->languageID
                );
            }

            if (isset($this->parameters['pollData'])) {
                PollManager::getInstance()->setObject(Post::OBJECT_TYPE, $post->postID, $post->pollID ?: 0);
                $pollID = PollManager::getInstance()->savePoll($post->getDecoratedObject(), $this->parameters['pollData']);
                if ($pollID != $post->pollID) {
                    $post->update(['pollID' => $pollID]);
                }
            }

            if (!empty($this->parameters['htmlInputProcessor'])) {
                $this->parameters['htmlInputProcessor']->setObjectID($post->postID);
                $hasEmbeddedObjects = MessageEmbeddedObjectManager::getInstance()->registerObjects($this->parameters['htmlInputProcessor']);
                if ($post->hasEmbeddedObjects != $hasEmbeddedObjects) {
                    $post->update(['hasEmbeddedObjects' => $hasEmbeddedObjects ? 1 : 0]);
                }

                if (!empty($this->parameters['isEdit']) && $post->userID != WCF::getUser()->userID) {
                    PostModificationLogHandler::getInstance()->add($post->getDecoratedObject(), 'edit', [
                        'reason' => $this->parameters['data']['editReason'] ?? '',
                    ]);
                }
            }
        }
    }

    /**
     * Permanently deletes posts. Threads without any remaining post are deleted as well.
     *
     * @inheritDoc
     */
    public function delete()
    {
        if (empty($this->objects)) {
            $this->readObjects();
        }

        $postIDs = $threadIDs = $userIDs = $attachmentPostIDs = $pollIDs = [];
        $visiblePostsByUser = [];
        foreach ($this->getObjects() as $post) {
            $postIDs[] = $post->postID;
            $threadIDs[$post->threadID] = $post->threadID;
            if ($post->userID) {
                $userIDs[] = $post->userID;
                $visiblePostsByUser[$post->userID] = ($visiblePostsByUser[$post->userID] ?? 0) + 1;
            }
            if ($post->attachments) {
                $attachmentPostIDs[] = $post->postID;
            }
            if ($post->pollID) {
                $pollIDs[] = $post->pollID;
            }

            if (empty($this->parameters['skipLog'])) {
                PostModificationLogHandler::getInstance()->add($post->getDecoratedObject(), 'delete');
            }
        }

        if ($postIDs === []) {
            return 0;
        }

        $boardIDs = $this->getBoardIDsForThreads($threadIDs);

        $count = parent::delete();

        SearchIndexManager::getInstance()->delete(Post::OBJECT_TYPE, $postIDs);
        MessageEmbeddedObjectManager::getInstance()->removeObjects(Post::OBJECT_TYPE, $postIDs);
        ModerationQueueManager::getInstance()->removeQueues(Post::OBJECT_TYPE, $postIDs);
        UserNotificationHandler::getInstance()->removeNotifications('com.tinsever.forum.post.notification', $postIDs);
        UserActivityEventHandler::getInstance()->removeEvents('com.tinsever.forum.recentActivityEvent.post', $postIDs);
        UserActivityPointHandler::getInstance()->removeEvents(
            'com.tinsever.forum.activityPointEvent.post',
            $visiblePostsByUser
        );
        EditHistoryManager::getInstance()->delete(Post::OBJECT_TYPE, $postIDs);
        ReactionHandler::getInstance()->removeReactions('com.tinsever.forum.likeablePost', $postIDs);
        if ($attachmentPostIDs !== []) {
            AttachmentHandler::removeAttachments(Post::OBJECT_TYPE, $attachmentPostIDs);
        }
        if ($pollIDs !== []) {
            PollManager::getInstance()->removePolls($pollIDs);
        }

        if (empty($this->parameters['skipThreadUpdate'])) {
            $this->rebuildThreads($threadIDs);
            self::rebuildBoards($boardIDs);
        }

        PostEditor::updateUserPostCounters($userIDs);
        ThreadEditor::resetUnreadCounters();

        return $count;
    }

    /**
     * Rebuilds the given threads, deleting threads that no longer contain any posts.
     *
     * @param int[] $threadIDs
     */
    protected function rebuildThreads(array $threadIDs): void
    {
        $emptyThreadIDs = [];
        foreach ($threadIDs as $threadID) {
            $thread = new Thread($threadID);
            if (!$thread->threadID) {
                continue;
            }

            if (!(new ThreadEditor($thread))->rebuild()) {
                $emptyThreadIDs[] = $threadID;
            }
        }

        if ($emptyThreadIDs !== []) {
            (new ThreadAction($emptyThreadIDs, 'delete', ['skipBoardUpdate' => true]))->executeAction();
        }
    }

    /**
     * @param int[] $threadIDs
     * @return int[]
     */
    protected function getBoardIDsForThreads(array $threadIDs): array
    {
        if ($threadIDs === []) {
            return [];
        }

        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add('threadID IN (?)', [\array_values($threadIDs)]);
        $sql = "SELECT  DISTINCT boardID
                FROM    forum1_thread
                " . $conditions;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditions->getParameters());

        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Rebuilds the counters and last posts of the given boards.
     *
     * @param int[] $boardIDs
     */
    public static function rebuildBoards(array $boardIDs): void
    {
        foreach (\array_unique($boardIDs) as $boardID) {
            $board = BoardCache::getInstance()->getBoard($boardID);
            if ($board !== null) {
                (new BoardEditor($board))->rebuildStats();
            }
        }
    }

    /**
     * Updates threads, boards and counters after the visibility of posts has changed.
     */
    protected function afterVisibilityChange(): void
    {
        $threadIDs = $userIDs = [];
        foreach ($this->getObjects() as $post) {
            $threadIDs[$post->threadID] = $post->threadID;
            $userIDs[] = $post->userID;
        }

        $this->rebuildThreads($threadIDs);
        self::rebuildBoards($this->getBoardIDsForThreads($threadIDs));
        PostEditor::updateUserPostCounters($userIDs);
        ThreadEditor::resetUnreadCounters();
        PostRuntimeCache::getInstance()->removeObjects(\array_keys($this->getObjects()));
    }

    /**
     * Soft-deletes posts.
     */
    public function trash(): void
    {
        if (empty($this->objects)) {
            $this->readObjects();
        }

        $reason = StringUtil::trim($this->parameters['reason'] ?? '');
        foreach ($this->getObjects() as $post) {
            if ($post->isDeleted) {
                continue;
            }

            $post->update([
                'isDeleted' => 1,
                'deleteTime' => TIME_NOW,
            ]);
            PostModificationLogHandler::getInstance()->add($post->getDecoratedObject(), 'trash', ['reason' => $reason]);
        }

        $this->afterVisibilityChange();
    }

    /**
     * Restores soft-deleted posts.
     */
    public function restore(): void
    {
        if (empty($this->objects)) {
            $this->readObjects();
        }

        foreach ($this->getObjects() as $post) {
            if (!$post->isDeleted) {
                continue;
            }

            $post->update([
                'isDeleted' => 0,
                'deleteTime' => 0,
            ]);
            PostModificationLogHandler::getInstance()->add($post->getDecoratedObject(), 'restore');
        }

        $this->afterVisibilityChange();
    }

    /**
     * Approves disabled posts.
     */
    public function enable(): void
    {
        if (empty($this->objects)) {
            $this->readObjects();
        }

        $postIDs = [];
        $enabledPosts = [];
        foreach ($this->getObjects() as $post) {
            if (!$post->isDisabled) {
                continue;
            }

            $post->update(['isDisabled' => 0]);
            $postIDs[] = $post->postID;
            $enabledPosts[] = new Post($post->postID);
            PostModificationLogHandler::getInstance()->add($post->getDecoratedObject(), 'enable');
        }

        if ($postIDs !== []) {
            ModerationQueueActivationManager::getInstance()->removeModeratedContent(Post::OBJECT_TYPE, $postIDs);
        }

        $this->afterVisibilityChange();

        foreach ($enabledPosts as $post) {
            $thread = new Thread($post->threadID);
            if ($thread->firstPostID == $post->postID) {
                continue;
            }

            if ($post->userID) {
                UserActivityEventHandler::getInstance()->fireEvent(
                    'com.tinsever.forum.recentActivityEvent.post',
                    $post->postID,
                    $thread->languageID,
                    $post->userID,
                    $post->time
                );
            }

            PostNotificationHandler::getInstance()->firePostCreated($post, $thread, null);
        }
    }

    /**
     * Disables posts, they need to be approved by a moderator.
     */
    public function disable(): void
    {
        if (empty($this->objects)) {
            $this->readObjects();
        }

        $postIDs = [];
        foreach ($this->getObjects() as $post) {
            if ($post->isDisabled) {
                continue;
            }

            $post->update(['isDisabled' => 1]);
            $postIDs[] = $post->postID;
            PostModificationLogHandler::getInstance()->add($post->getDecoratedObject(), 'disable');
            ModerationQueueActivationManager::getInstance()->addModeratedContent(Post::OBJECT_TYPE, $post->postID);
        }

        if ($postIDs !== []) {
            UserActivityEventHandler::getInstance()->removeEvents('com.tinsever.forum.recentActivityEvent.post', $postIDs);
        }

        $this->afterVisibilityChange();
    }

    /**
     * Closes posts, preventing further edits by their authors.
     */
    public function close(): void
    {
        $this->setClosed(true);
    }

    /**
     * Reopens closed posts.
     */
    public function open(): void
    {
        $this->setClosed(false);
    }

    private function setClosed(bool $isClosed): void
    {
        if (empty($this->objects)) {
            $this->readObjects();
        }

        foreach ($this->getObjects() as $post) {
            if ((bool)$post->isClosed === $isClosed) {
                continue;
            }

            $post->update(['isClosed' => $isClosed ? 1 : 0]);
            PostModificationLogHandler::getInstance()->add($post->getDecoratedObject(), $isClosed ? 'close' : 'open');
        }
    }

    /**
     * Moves posts into another thread.
     */
    public function move(): void
    {
        if (empty($this->objects)) {
            $this->readObjects();
        }

        $targetThread = $this->parameters['thread'];
        \assert($targetThread instanceof Thread);

        $sourceThreadIDs = [];
        $postIDs = [];
        foreach ($this->getObjects() as $post) {
            if ($post->threadID == $targetThread->threadID) {
                continue;
            }

            $sourceThreadIDs[$post->threadID] = $post->threadID;
            $postIDs[] = $post->postID;
            PostModificationLogHandler::getInstance()->add($post->getDecoratedObject(), 'move', [
                'oldThreadID' => $post->threadID,
                'newThreadID' => $targetThread->threadID,
                'newThreadTopic' => $targetThread->topic,
            ]);
        }

        if ($postIDs === []) {
            return;
        }

        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add('postID IN (?)', [$postIDs]);
        $sql = "UPDATE  forum1_post
                SET     threadID = ?
                " . $conditions;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute(\array_merge([$targetThread->threadID], $conditions->getParameters()));

        PostModificationLogHandler::getInstance()->updateParentObjectID($postIDs, $targetThread->threadID);

        $threadIDs = $sourceThreadIDs;
        $threadIDs[$targetThread->threadID] = $targetThread->threadID;
        $boardIDs = $this->getBoardIDsForThreads($threadIDs);

        $this->rebuildThreads($threadIDs);
        self::rebuildBoards($boardIDs);
        PostEditor::updateUserPostCounters(\array_map(static fn($post) => $post->userID, $this->getObjects()));
        ThreadEditor::resetUnreadCounters();

        // update the search index, the first post of a thread uses the topic as subject
        foreach ($threadIDs as $threadID) {
            $thread = new Thread($threadID);
            if (!$thread->threadID) {
                continue;
            }

            $postList = new PostList();
            $postList->getConditionBuilder()->add('post.postID IN (?)', [$postIDs]);
            $postList->getConditionBuilder()->add('post.threadID = ?', [$threadID]);
            $postList->readObjects();
            foreach ($postList as $post) {
                SearchIndexManager::getInstance()->set(
                    Post::OBJECT_TYPE,
                    $post->postID,
                    $post->message,
                    $thread->firstPostID == $post->postID ? $thread->topic : $post->subject,
                    $post->time,
                    $post->userID,
                    $post->username,
                    $thread->languageID
                );
            }
        }
    }

    /**
     * Copies posts into another thread.
     */
    public function copy(): void
    {
        if (empty($this->objects)) {
            $this->readObjects();
        }

        $targetThread = $this->parameters['thread'];
        \assert($targetThread instanceof Thread);

        foreach ($this->getObjects() as $post) {
            $data = $post->getDecoratedObject()->getData();
            unset($data['postID'], $data['pollID']);
            $data['threadID'] = $targetThread->threadID;
            $data['attachments'] = 0;
            $data['cumulativeLikes'] = 0;
            $data['hasEmbeddedObjects'] = 0;

            $htmlInputProcessor = new HtmlInputProcessor();
            $htmlInputProcessor->processIntermediate($post->message);

            (new self([], 'create', [
                'data' => $data,
                'thread' => $targetThread,
                'htmlInputProcessor' => $htmlInputProcessor,
            ]))->executeAction();
        }

        (new ThreadEditor(new Thread($targetThread->threadID)))->rebuild();
        self::rebuildBoards([$targetThread->boardID]);
    }

    /**
     * Merges posts into the post given by `targetPostID`, defaulting to the oldest
     * of the selected posts. Messages are concatenated in chronological order.
     */
    public function merge(): void
    {
        if (empty($this->objects)) {
            $this->readObjects();
        }

        $posts = $this->getObjects();
        \uasort($posts, static fn($a, $b) => [$a->time, $a->postID] <=> [$b->time, $b->postID]);

        $messages = \array_map(static fn($post) => $post->message, $posts);

        $target = null;
        foreach ($posts as $key => $post) {
            if ($post->postID == ($this->parameters['targetPostID'] ?? 0)) {
                $target = $post;
                unset($posts[$key]);
                break;
            }
        }
        $target ??= \array_shift($posts);
        if ($target === null || $posts === []) {
            return;
        }

        $message = \implode('<p><br></p>', $messages);
        $mergedIDs = [];
        foreach ($posts as $post) {
            $mergedIDs[] = $post->postID;

            // attachments are moved to the target post
            if ($post->attachments) {
                $sql = "UPDATE  wcf1_attachment
                        SET     objectID = ?
                        WHERE   objectTypeID = (
                                    SELECT  objectTypeID
                                    FROM    wcf1_object_type
                                    WHERE   objectType = ?
                                        AND definitionID = (
                                            SELECT  definitionID
                                            FROM    wcf1_object_type_definition
                                            WHERE   definitionName = ?
                                        )
                                )
                            AND objectID = ?";
                $statement = WCF::getDB()->prepare($sql);
                $statement->execute([$target->postID, Post::OBJECT_TYPE, 'com.woltlab.wcf.attachment.objectType', $post->postID]);
            }
        }

        $htmlInputProcessor = new HtmlInputProcessor();
        $htmlInputProcessor->processIntermediate($message);

        (new self([$target], 'update', [
            'data' => [
                'attachments' => $target->attachments + \array_sum(\array_map(static fn($p) => $p->attachments, $posts)),
            ],
            'htmlInputProcessor' => $htmlInputProcessor,
        ]))->executeAction();

        PostModificationLogHandler::getInstance()->add($target->getDecoratedObject(), 'merge', [
            'count' => \count($mergedIDs),
        ]);

        // the merged posts no longer own any attachments
        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add('postID IN (?)', [$mergedIDs]);
        $sql = "UPDATE forum1_post SET attachments = 0 " . $conditions;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditions->getParameters());

        (new self($mergedIDs, 'delete', ['skipLog' => true]))->executeAction();
    }

    /**
     * @inheritDoc
     */
    public function validateQuickReply()
    {
        $this->thread = ThreadRuntimeCache::getInstance()->getObject(\intval($this->parameters['objectID'] ?? 0));
        if ($this->thread === null) {
            throw new UserInputException('objectID');
        }

        $lastTime = FloodControl::getInstance()->getLastTime('com.tinsever.forum.post');
        $limit = \intval(WCF::getSession()->getPermission('user.forum.floodControlTime'));
        if ($limit && $lastTime !== null && $lastTime > TIME_NOW - $limit) {
            throw new UserInputException(
                'message',
                WCF::getLanguage()->getDynamicVariable('forum.post.error.floodControl', [
                    'waitTime' => $lastTime + $limit - TIME_NOW,
                ])
            );
        }

        // The poll is not part of the message data and would be discarded by the quick reply manager.
        $pollData = $this->parameters['data']['poll'] ?? null;
        unset($this->parameters['data']['poll']);

        if (!WCF::getUser()->userID) {
            $this->setGuestDialogCaptcha();
        }

        QuickReplyManager::getInstance()->setDisallowedBBCodes(\explode(
            ',',
            WCF::getSession()->getPermission('user.message.disallowedBBCodes')
        ));
        QuickReplyManager::getInstance()->validateParameters($this, $this->parameters, Thread::class);

        if (!WCF::getUser()->userID && empty($this->parameters['requireGuestDialog'])) {
            $this->validateGuestDialogUsername();
            $this->validateGuestDialogCaptcha();
        }

        $this->readQuickReplyPollData($pollData);

        if ($this->messageIsProbablySpam()) {
            throw new PermissionDeniedException();
        }
    }

    /**
     * Validates the poll of a quick reply and stores it in the `pollData` parameter.
     */
    private function readQuickReplyPollData(mixed $pollData): void
    {
        if (!\MODULE_POLL || !\is_array($pollData) || !$this->thread->getPermission('canStartPoll')) {
            return;
        }

        $question = StringUtil::trim(\is_string($pollData['pollQuestion'] ?? null) ? $pollData['pollQuestion'] : '');
        $options = [];
        foreach (\is_array($pollData['pollOptions'] ?? null) ? $pollData['pollOptions'] : [] as $option) {
            // The options are formatted as `{optionID}_{value}`, new options always use the id 0.
            if (!\is_string($option) || !\str_contains($option, '_')) {
                continue;
            }

            $value = StringUtil::trim(\explode('_', $option, 2)[1]);
            if ($value !== '') {
                $options[] = $value;
            }
        }

        if ($question === '' && $options === []) {
            return;
        }

        $input = [
            'pollQuestion' => $question,
            'pollOptions' => \array_map(static fn(string $value) => '0_' . $value, $options),
            'pollMaxVotes' => \is_numeric($pollData['pollMaxVotes'] ?? null) ? $pollData['pollMaxVotes'] : 1,
            'pollEndTime' => \is_string($pollData['pollEndTime'] ?? null) ? $pollData['pollEndTime'] : '',
        ];
        foreach (['pollIsChangeable', 'pollIsPublic', 'pollResultsRequireVote', 'pollSortByVotes'] as $key) {
            if (!empty($pollData[$key])) {
                $input[$key] = 1;
            }
        }

        $pollManager = PollManager::getInstance();
        $pollManager->setObject(Post::OBJECT_TYPE, 0);
        $pollManager->readFormParameters($input);
        try {
            $pollManager->validate();
        } catch (UserInputException $e) {
            if ($e->getField() === 'pollQuestion' || $e->getField() === 'pollOptions') {
                throw new UserInputException(
                    'message',
                    WCF::getLanguage()->get('forum.post.poll.error.' . \substr($e->getField(), 4))
                );
            }

            throw $e;
        }

        $endTime = \DateTimeImmutable::createFromFormat('Y-m-d\\TH:i:sP', $input['pollEndTime']);
        $this->parameters['pollData'] = [
            'question' => $question,
            'endTime' => $endTime !== false ? $endTime->getTimestamp() : 0,
            'maxVotes' => \max(\intval($input['pollMaxVotes']), 1),
            'isChangeable' => isset($input['pollIsChangeable']) ? 1 : 0,
            'isPublic' => isset($input['pollIsPublic']) && $pollManager->canStartPublicPoll() ? 1 : 0,
            'resultsRequireVote' => isset($input['pollResultsRequireVote']) ? 1 : 0,
            'sortByVotes' => isset($input['pollSortByVotes']) ? 1 : 0,
            'options' => \array_map(
                static fn(string $value) => ['optionID' => 0, 'optionValue' => $value],
                \array_slice($options, 0, \POLL_MAX_OPTIONS)
            ),
        ];
    }

    /**
     * @inheritDoc
     */
    public function quickReply()
    {
        if (
            !WCF::getUser()->userID
            && (!empty($this->parameters['requireGuestDialog']) || $this->guestDialogErrors !== [])
        ) {
            return [
                'guestDialog' => WCF::getTPL()->render('wcf', 'messageQuickReplyGuestDialog', [
                    'ajaxCaptcha' => true,
                    'captchaID' => self::QUICK_REPLY_GUEST_DIALOG_ID,
                    'captchaObjectType' => $this->guestDialogCaptchaObjectType,
                    'errorType' => $this->guestDialogErrors,
                    'username' => $this->parameters['data']['username'] ?? '',
                ]),
                'guestDialogID' => self::QUICK_REPLY_GUEST_DIALOG_ID,
            ];
        }

        $this->parameters['thread'] = QuickReplyManager::getInstance()->getContainer();

        WCF::getTPL()->assign([
            'likeData' => [],
            'polls' => [],
            'postInteractions' => new InteractionContextMenuComponent(new PostInteractions()),
        ]);

        $returnValues = QuickReplyManager::getInstance()->createMessage(
            $this,
            $this->parameters,
            ThreadAction::class,
            $this->thread->getBoard()->getPostSortOrder(),
            'threadPostList',
            'forum'
        );

        if ($this->mergedIntoPreviousPost) {
            $post = new Post(\intval($returnValues['objectID'] ?? 0));
            $returnValues = [
                'objectID' => $post->postID,
                'url' => $post->getLink(),
            ];
        }

        FloodControl::getInstance()->registerContent('com.tinsever.forum.post');

        // A checked box closes the thread, an unchecked one is not submitted and opens it.
        $closeThread = !empty($this->parameters['closeThread']);
        $closeStateChanged = $this->thread->canClose() && $closeThread !== (bool)$this->thread->isClosed;
        if ($closeStateChanged) {
            (new ThreadAction([$this->thread], $closeThread ? 'close' : 'open'))->executeAction();
        }

        // The page has to be reloaded to show the new poll or state of the thread.
        if (
            isset($returnValues['template'])
            && ($closeStateChanged || !empty($this->parameters['pollData']))
        ) {
            $returnValues = [
                'objectID' => $returnValues['objectID'],
                'url' => (new Post($returnValues['objectID']))->getLink(),
            ];
        }

        if (!WCF::getUser()->userID) {
            $returnValues['guestDialogID'] = self::QUICK_REPLY_GUEST_DIALOG_ID;
        }

        EventHandler::getInstance()->fireAction($this, 'afterQuickReply', $returnValues);

        return $returnValues;
    }

    /**
     * @inheritDoc
     */
    public function validateContainer(DatabaseObject $container)
    {
        \assert($container instanceof Thread);
        if (!$container->threadID) {
            throw new UserInputException('objectID');
        }

        if (!$container->canReply()) {
            throw new PermissionDeniedException();
        }
    }

    /**
     * @inheritDoc
     */
    public function validateMessage(DatabaseObject $container, HtmlInputProcessor $htmlInputProcessor)
    {
        $message = $htmlInputProcessor->getTextContent();
        $maxLength = \intval(WCF::getSession()->getPermission('user.forum.maxPostLength'));
        if ($maxLength && \mb_strlen($message) > $maxLength) {
            throw new UserInputException(
                'message',
                WCF::getLanguage()->getDynamicVariable('wcf.message.error.tooLong', ['maxTextLength' => $maxLength])
            );
        }

        $disallowedBBCodes = $htmlInputProcessor->validate();
        if ($disallowedBBCodes !== []) {
            throw new UserInputException(
                'message',
                WCF::getLanguage()->getDynamicVariable(
                    'wcf.message.error.disallowedBBCodes',
                    ['disallowedBBCodes' => $disallowedBBCodes]
                )
            );
        }

        $censoredWords = Censorship::getInstance()->test($message);
        if ($censoredWords) {
            throw new UserInputException(
                'message',
                WCF::getLanguage()->getDynamicVariable(
                    'wcf.message.error.censoredWordsFound',
                    ['censoredWords' => $censoredWords]
                )
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getMessageList(DatabaseObject $container, int $lastMessageTime)
    {
        \assert($container instanceof Thread);

        $postList = new ViewablePostList();
        $postList->getConditionBuilder()->add('post.threadID = ?', [$container->threadID]);
        $postList->getConditionBuilder()->add('post.time > ?', [$lastMessageTime]);
        if (!$container->getPermission('canReadDeletedPost')) {
            $postList->getConditionBuilder()->add('post.isDeleted = ?', [0]);
        }
        if (!$container->getPermission('canEnablePost')) {
            $postList->getConditionBuilder()->add(
                '(post.isDisabled = ? OR post.userID = ?)',
                [0, WCF::getUser()->userID ?: -1]
            );
        }
        $postList->sqlOrderBy = 'post.time ' . $container->getBoard()->getPostSortOrder() . ', post.postID';
        $postList->readObjects();

        return $postList;
    }

    /**
     * @inheritDoc
     */
    public function getPageNo(DatabaseObject $container)
    {
        \assert($container instanceof Thread);

        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add('threadID = ?', [$container->threadID]);
        if (!$container->getPermission('canReadDeletedPost')) {
            $conditions->add('isDeleted = ?', [0]);
        }
        if (!$container->getPermission('canEnablePost')) {
            $conditions->add('(isDisabled = ? OR userID = ?)', [0, WCF::getUser()->userID ?: -1]);
        }

        $sql = "SELECT  COUNT(*)
                FROM    forum1_post
                " . $conditions;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditions->getParameters());
        $count = $statement->fetchSingleColumn();

        if ($container->getBoard()->getPostSortOrder() === 'DESC') {
            return [1, $count];
        }

        return [\intval(\ceil($count / $container->getBoard()->getPostsPerPage())), $count];
    }

    /**
     * @inheritDoc
     */
    public function getRedirectUrl(DatabaseObject $container, DatabaseObject $message)
    {
        \assert($message instanceof Post);

        return $message->getLink();
    }

    /**
     * @inheritDoc
     */
    public function getAttachmentHandler(DatabaseObject $container)
    {
        \assert($container instanceof Thread);
        if (!$container->getPermission('canUploadAttachment')) {
            return null;
        }

        return new AttachmentHandler(Post::OBJECT_TYPE, 0, $this->parameters['tmpHash'], $container->boardID);
    }

    /**
     * @inheritDoc
     */
    public function getHtmlInputProcessor(?string $message = null, int $objectID = 0)
    {
        if ($message === null) {
            return $this->htmlInputProcessor;
        }

        $this->htmlInputProcessor = new HtmlInputProcessor();
        $this->htmlInputProcessor->process($message, Post::OBJECT_TYPE, $objectID);

        return $this->htmlInputProcessor;
    }

    /**
     * @inheritDoc
     */
    public function validateBeginEdit()
    {
        $this->readInteger('containerID');
        $this->readInteger('objectID');

        $this->post = PostRuntimeCache::getInstance()->getObject($this->parameters['objectID']);
        if ($this->post === null || $this->post->threadID != $this->parameters['containerID']) {
            throw new UserInputException('objectID');
        }

        if (!$this->post->canEdit()) {
            throw new PermissionDeniedException();
        }

        $this->thread = $this->post->getThread();

        BBCodeHandler::getInstance()->setDisallowedBBCodes(\explode(
            ',',
            WCF::getSession()->getPermission('user.message.disallowedBBCodes')
        ));
    }

    /**
     * @inheritDoc
     */
    public function beginEdit()
    {
        $upcastProcessor = new HtmlUpcastProcessor();
        $upcastProcessor->process($this->post->message, Post::OBJECT_TYPE, $this->post->postID);

        $tmpHash = StringUtil::getRandomID();
        $attachmentHandler = null;
        $attachmentList = [];
        if ($this->thread->getPermission('canUploadAttachment')) {
            $attachmentHandler = new AttachmentHandler(Post::OBJECT_TYPE, $this->post->postID, $tmpHash, $this->thread->boardID);
            $attachmentList = $attachmentHandler->getAttachmentList()->getObjects();
        }

        return [
            'actionName' => 'beginEdit',
            'template' => WCF::getTPL()->render('forum', 'postInlineEditor', [
                'defaultSmilies' => SmileyCache::getInstance()->getCategorySmilies(),
                'post' => $this->post,
                'text' => $upcastProcessor->getHtml(),
                'permissionCanUseSmilies' => 'user.message.canUseSmilies',
                'wysiwygSelector' => 'messageEditor' . $this->post->postID,
                'attachmentHandler' => $attachmentHandler,
                'attachmentList' => $attachmentList,
                'attachmentObjectID' => $this->post->postID,
                'attachmentObjectType' => Post::OBJECT_TYPE,
                'attachmentParentObjectID' => $this->thread->boardID,
                'tmpHash' => $tmpHash,
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function validateSave()
    {
        $this->readString('message', true, 'data');
        $this->readString('editReason', true, 'data');

        if (empty($this->parameters['data']['message'])) {
            throw new UserInputException(
                'message',
                WCF::getLanguage()->getDynamicVariable('wcf.global.form.error.empty')
            );
        }

        $this->validateBeginEdit();

        $this->validateMessage(
            $this->thread,
            $this->getHtmlInputProcessor($this->parameters['data']['message'], $this->post->postID)
        );

        if ($this->messageIsProbablySpam()) {
            throw new PermissionDeniedException();
        }
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        $data = [];
        $editReason = StringUtil::trim($this->parameters['data']['editReason'] ?? '');

        // edits shortly after posting or by the author within the grace period are not tracked
        if (!(\FORUM_POST_EDIT_GRACE_PERIOD && $this->post->time > TIME_NOW - \FORUM_POST_EDIT_GRACE_PERIOD && $this->post->isOwner())) {
            $data = [
                'editorID' => WCF::getUser()->userID ?: null,
                'editor' => WCF::getUser()->username,
                'lastEditTime' => TIME_NOW,
                'editCount' => $this->post->editCount + 1,
                'editReason' => $editReason,
            ];
        }

        (new self([$this->post], 'update', [
            'data' => $data,
            'htmlInputProcessor' => $this->getHtmlInputProcessor(),
            'isEdit' => true,
        ]))->executeAction();

        $this->post = new Post($this->post->postID);

        $attachmentList = $this->post->getAttachments(true);
        $count = $attachmentList === null ? 0 : \count($attachmentList);
        if ($count != $this->post->attachments) {
            (new PostEditor($this->post))->update(['attachments' => $count]);
            (new ThreadEditor(new Thread($this->post->threadID)))->rebuild();
        }

        MessageEmbeddedObjectManager::getInstance()->loadObjects(Post::OBJECT_TYPE, [$this->post->postID]);

        return [
            'actionName' => 'save',
            'message' => $this->post->getFormattedMessage(),
            'attachmentList' => WCF::getTPL()->render('wcf', 'attachments', [
                'attachmentList' => $attachmentList,
                'objectID' => $this->post->postID,
            ]),
        ];
    }

    /**
     * Triggers the spam check and returns its result.
     */
    protected function messageIsProbablySpam(): bool
    {
        $event = new MessageSpamChecking(
            $this->htmlInputProcessor,
            WCF::getUser()->userID ? WCF::getUser() : null,
            UserUtil::getIpAddress()
        );
        EventHandler::getInstance()->fire($event);

        return $event->defaultPrevented();
    }
}
