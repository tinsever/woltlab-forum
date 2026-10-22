<?php

namespace forum\data\thread;

use forum\data\board\Board;
use forum\data\board\BoardCache;
use forum\data\board\BoardEditor;
use forum\data\post\Post;
use forum\data\post\PostAction;
use forum\data\post\PostEditor;
use forum\data\post\PostList;
use forum\system\cache\runtime\ThreadRuntimeCache;
use forum\system\label\object\ThreadLabelObjectHandler;
use forum\system\log\modification\PostModificationLogHandler;
use forum\system\log\modification\ThreadModificationLogHandler;
use forum\system\post\PostNotificationHandler;
use forum\system\thread\ThreadStatusHandler;
use forum\system\thread\ThreadVisitHandler;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\IVisitableObjectAction;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\moderation\queue\ModerationQueueActivationManager;
use wcf\system\search\SearchIndexManager;
use wcf\system\language\LanguageFactory;
use wcf\system\tagging\TagEngine;
use wcf\system\user\activity\event\UserActivityEventHandler;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\visitTracker\VisitTracker;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Executes thread-related actions.
 *
 * Permission checks for moderative actions are performed by the RPC endpoints,
 * the methods of this class only implement the state transitions.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractDatabaseObjectAction<Thread, ThreadEditor>
 */
class ThreadAction extends AbstractDatabaseObjectAction implements IVisitableObjectAction
{
    /**
     * @inheritDoc
     */
    protected $className = ThreadEditor::class;

    /**
     * @inheritDoc
     */
    protected $allowGuestAccess = ['markAsRead'];

    /**
     * Creates a new thread together with its first post.
     *
     * @inheritDoc
     */
    public function create()
    {
        $data = $this->parameters['data'];
        $data['time'] ??= TIME_NOW;
        $data['lastPostTime'] = $data['time'];
        $data['lastPosterID'] = $data['userID'] ?? null;
        $data['lastPoster'] = $data['username'] ?? '';
        if (!empty($this->parameters['labelIDs'])) {
            $data['hasLabels'] = 1;
        }

        $board = BoardCache::getInstance()->getBoard($data['boardID']);
        if (!isset($data['isDisabled'])) {
            $data['isDisabled'] = (!$board->getPermission('canStartThreadWithoutModeration')
                && !$board->getPermission('canEnableThread')) ? 1 : 0;
        }

        /** @var Thread $thread */
        $thread = \call_user_func([$this->className, 'create'], $data);
        $threadEditor = new ThreadEditor($thread);

        $postData = $this->parameters['postData'] ?? [];
        $postData['threadID'] = $thread->threadID;
        $postData['time'] = $thread->time;
        $postData['userID'] = $thread->userID;
        $postData['username'] = $thread->username;
        $postData['isDisabled'] = $thread->isDisabled;

        $post = (new PostAction([], 'create', [
            'data' => $postData,
            'thread' => $thread,
            'isFirstPost' => true,
            'threadIsDisabled' => $thread->isDisabled,
            'attachmentHandler' => $this->parameters['attachmentHandler'] ?? null,
            'htmlInputProcessor' => $this->parameters['htmlInputProcessor'] ?? null,
            'pollData' => $this->parameters['pollData'] ?? null,
            'subscribeThread' => $this->parameters['subscribeThread'] ?? false,
        ]))->executeAction()['returnValues'];
        \assert($post instanceof Post);

        $threadEditor->update([
            'firstPostID' => $post->postID,
            'lastPostID' => $post->postID,
            'attachments' => $post->attachments,
            'polls' => $post->pollID ? 1 : 0,
        ]);

        if (!empty($this->parameters['tags'])) {
            TagEngine::getInstance()->addObjectTags(
                'com.tinsever.forum.thread',
                $thread->threadID,
                $this->parameters['tags'],
                $thread->languageID ?: LanguageFactory::getInstance()->getDefaultLanguageID()
            );
        }

        if (!empty($this->parameters['labelIDs'])) {
            ThreadLabelObjectHandler::getInstance()->setLabels($this->parameters['labelIDs'], $thread->threadID, false);
        }

        if ($thread->isAnnouncement) {
            $this->saveAnnouncementBoards(
                $thread->threadID,
                $this->parameters['announcementBoardIDs'] ?? [$thread->boardID]
            );
        }

        $thread = new Thread($thread->threadID);
        if ($thread->isDisabled) {
            ModerationQueueActivationManager::getInstance()->addModeratedContent('com.tinsever.forum.thread', $thread->threadID);
        } else {
            $this->publish($thread, $post, $this->parameters['htmlInputProcessor'] ?? null);
        }

        ThreadEditor::resetUnreadCounters();

        return $thread;
    }

    /**
     * Updates counters, activity and notifications once a thread becomes visible.
     */
    protected function publish(Thread $thread, Post $post, $htmlInputProcessor = null): void
    {
        $boardEditor = new BoardEditor(BoardCache::getInstance()->getBoard($thread->boardID));
        $boardEditor->updateCounters([
            'threads' => 1,
            'posts' => $thread->replies + 1,
        ]);
        $boardEditor->updateLastPost();

        PostAction::addPostCounters([$post]);

        if ($thread->userID) {
            UserActivityEventHandler::getInstance()->fireEvent(
                'com.tinsever.forum.recentActivityEvent.thread',
                $thread->threadID,
                $thread->languageID,
                $thread->userID,
                $thread->time
            );
        }

        PostNotificationHandler::getInstance()->fireThreadCreated($post, $thread, $htmlInputProcessor);
    }

    /**
     * @param int[] $boardIDs
     */
    protected function saveAnnouncementBoards(int $threadID, array $boardIDs): void
    {
        $sql = "DELETE FROM forum1_thread_announcement
                WHERE       threadID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$threadID]);

        $sql = "INSERT IGNORE INTO  forum1_thread_announcement
                                    (boardID, threadID)
                VALUES              (?, ?)";
        $statement = WCF::getDB()->prepare($sql);
        foreach (\array_unique($boardIDs) as $boardID) {
            if (BoardCache::getInstance()->getBoard($boardID) !== null) {
                $statement->execute([$boardID, $threadID]);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function update()
    {
        if (empty($this->objects)) {
            $this->readObjects();
        }

        if (isset($this->parameters['labelIDs'])) {
            $this->parameters['data']['hasLabels'] = $this->parameters['labelIDs'] === [] ? 0 : 1;
        }

        $oldTopics = [];
        foreach ($this->getObjects() as $thread) {
            $oldTopics[$thread->threadID] = $thread->topic;
        }

        parent::update();

        foreach ($this->getObjects() as $thread) {
            $thread = new Thread($thread->threadID);

            if (isset($this->parameters['data']['topic']) && $oldTopics[$thread->threadID] !== $thread->topic) {
                ThreadModificationLogHandler::getInstance()->add($thread, 'edit', [
                    'oldTopic' => $oldTopics[$thread->threadID],
                ]);

                $firstPost = $thread->getFirstPost();
                if ($firstPost !== null) {
                    SearchIndexManager::getInstance()->set(
                        Post::OBJECT_TYPE,
                        $firstPost->postID,
                        $firstPost->message,
                        $thread->topic,
                        $firstPost->time,
                        $firstPost->userID,
                        $firstPost->username,
                        $thread->languageID
                    );
                }

                $sql = "UPDATE  forum1_thread
                        SET     topic = ?
                        WHERE   movedThreadID = ?";
                $statement = WCF::getDB()->prepare($sql);
                $statement->execute([$thread->topic, $thread->threadID]);
            }

            if (isset($this->parameters['tags'])) {
                TagEngine::getInstance()->addObjectTags(
                    'com.tinsever.forum.thread',
                    $thread->threadID,
                    $this->parameters['tags'],
                    $thread->languageID ?: LanguageFactory::getInstance()->getDefaultLanguageID()
                );
            }

            if (isset($this->parameters['labelIDs'])) {
                $handler = ThreadLabelObjectHandler::getInstance();
                $handler->setBoardID($thread->boardID);
                $handler->setLabels($this->parameters['labelIDs'], $thread->threadID, false);
            }

            if (isset($this->parameters['announcementBoardIDs']) && $thread->isAnnouncement) {
                $this->saveAnnouncementBoards($thread->threadID, $this->parameters['announcementBoardIDs']);
            }
        }
    }

    /**
     * Permanently deletes threads including their posts.
     *
     * @inheritDoc
     */
    public function delete()
    {
        if (empty($this->objects)) {
            $this->readObjects();
        }

        $threadIDs = $boardIDs = [];
        foreach ($this->getObjects() as $thread) {
            $threadIDs[] = $thread->threadID;
            $boardIDs[] = $thread->boardID;

            if (empty($this->parameters['skipLog']) && !$thread->isLink()) {
                ThreadModificationLogHandler::getInstance()->add($thread->getDecoratedObject(), 'delete');
            }
        }

        if ($threadIDs === []) {
            return 0;
        }

        $postList = new PostList();
        $postList->getConditionBuilder()->add('post.threadID IN (?)', [$threadIDs]);
        $postList->readObjectIDs();
        $postIDs = $postList->getObjectIDs();
        if ($postIDs !== []) {
            foreach (\array_chunk($postIDs, 500) as $chunk) {
                (new PostAction($chunk, 'delete', [
                    'skipThreadUpdate' => true,
                    'skipLog' => true,
                ]))->executeAction();
            }
        }

        $count = parent::delete();

        TagEngine::getInstance()->deleteObjects('com.tinsever.forum.thread', $threadIDs);
        ThreadLabelObjectHandler::getInstance()->removeLabels($threadIDs, false);
        ModerationQueueActivationManager::getInstance()->removeModeratedContent('com.tinsever.forum.thread', $threadIDs);
        UserActivityEventHandler::getInstance()->removeEvents('com.tinsever.forum.recentActivityEvent.thread', $threadIDs);
        ThreadModificationLogHandler::getInstance()->deleteLogs($threadIDs, ['delete']);
        PostModificationLogHandler::getInstance()->deleteLogsByParentIDs($threadIDs);

        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add('objectTypeID = ?', [VisitTracker::getInstance()->getObjectTypeID(ThreadVisitHandler::THREAD_OBJECT_TYPE)]);
        $conditions->add('objectID IN (?)', [$threadIDs]);
        $sql = "DELETE FROM wcf1_tracked_visit " . $conditions;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditions->getParameters());

        if (empty($this->parameters['skipBoardUpdate'])) {
            PostAction::rebuildBoards($boardIDs);
        }

        ThreadEditor::resetUnreadCounters();
        UserStorageHandler::getInstance()->resetAll('forumWatchedThreads');

        return $count;
    }

    /**
     * Updates boards and counters after the visibility of threads has changed.
     */
    protected function afterVisibilityChange(array $additionalBoardIDs = []): void
    {
        $boardIDs = $additionalBoardIDs;
        $threadIDs = [];
        foreach ($this->getObjects() as $thread) {
            $boardIDs[] = $thread->boardID;
            $threadIDs[] = $thread->threadID;
        }

        PostAction::rebuildBoards($boardIDs);

        if ($threadIDs !== []) {
            $conditions = new PreparedStatementConditionBuilder();
            $conditions->add('threadID IN (?)', [$threadIDs]);
            $conditions->add('userID IS NOT NULL');
            $sql = "SELECT  DISTINCT userID
                    FROM    forum1_post
                    " . $conditions;
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute($conditions->getParameters());
            PostEditor::updateUserPostCounters($statement->fetchAll(\PDO::FETCH_COLUMN));
        }

        ThreadEditor::resetUnreadCounters();
        ThreadRuntimeCache::getInstance()->removeObjects($threadIDs);
    }

    /**
     * Soft-deletes threads.
     */
    public function trash(): void
    {
        if (empty($this->objects)) {
            $this->readObjects();
        }

        $reason = StringUtil::trim($this->parameters['reason'] ?? '');
        foreach ($this->getObjects() as $thread) {
            if ($thread->isDeleted) {
                continue;
            }

            $thread->update([
                'isDeleted' => 1,
                'deleteTime' => TIME_NOW,
            ]);
            ThreadModificationLogHandler::getInstance()->add($thread->getDecoratedObject(), 'trash', ['reason' => $reason]);
        }

        $this->afterVisibilityChange();
    }

    /**
     * Restores soft-deleted threads.
     */
    public function restore(): void
    {
        if (empty($this->objects)) {
            $this->readObjects();
        }

        foreach ($this->getObjects() as $thread) {
            if (!$thread->isDeleted) {
                continue;
            }

            $thread->update([
                'isDeleted' => 0,
                'deleteTime' => 0,
            ]);
            ThreadModificationLogHandler::getInstance()->add($thread->getDecoratedObject(), 'restore');
        }

        $this->afterVisibilityChange();
    }

    /**
     * Approves disabled threads.
     */
    public function enable(): void
    {
        if (empty($this->objects)) {
            $this->readObjects();
        }

        $published = [];
        foreach ($this->getObjects() as $thread) {
            if (!$thread->isDisabled) {
                continue;
            }

            $thread->update(['isDisabled' => 0]);
            ThreadModificationLogHandler::getInstance()->add($thread->getDecoratedObject(), 'enable');

            // the first post shares the state of the thread
            if ($thread->firstPostID) {
                $sql = "UPDATE  forum1_post
                        SET     isDisabled = ?
                        WHERE   postID = ?";
                $statement = WCF::getDB()->prepare($sql);
                $statement->execute([0, $thread->firstPostID]);
            }

            $published[] = $thread->threadID;
        }

        if ($published !== []) {
            ModerationQueueActivationManager::getInstance()->removeModeratedContent('com.tinsever.forum.thread', $published);
        }

        foreach ($published as $threadID) {
            $thread = new Thread($threadID);
            (new ThreadEditor($thread))->rebuild();
        }

        $this->afterVisibilityChange();

        foreach ($published as $threadID) {
            $thread = new Thread($threadID);
            if ($thread->userID) {
                UserActivityEventHandler::getInstance()->fireEvent(
                    'com.tinsever.forum.recentActivityEvent.thread',
                    $thread->threadID,
                    $thread->languageID,
                    $thread->userID,
                    $thread->time
                );
            }

            $firstPost = $thread->firstPostID ? new Post($thread->firstPostID) : null;
            if ($firstPost !== null && $firstPost->postID) {
                PostNotificationHandler::getInstance()->fireThreadCreated($firstPost, $thread, null);
            }
        }
    }

    /**
     * Disables threads, they need to be approved by a moderator.
     */
    public function disable(): void
    {
        if (empty($this->objects)) {
            $this->readObjects();
        }

        $threadIDs = [];
        foreach ($this->getObjects() as $thread) {
            if ($thread->isDisabled) {
                continue;
            }

            $thread->update(['isDisabled' => 1]);
            $threadIDs[] = $thread->threadID;
            ThreadModificationLogHandler::getInstance()->add($thread->getDecoratedObject(), 'disable');
            ModerationQueueActivationManager::getInstance()->addModeratedContent('com.tinsever.forum.thread', $thread->threadID);

            if ($thread->firstPostID) {
                $sql = "UPDATE  forum1_post
                        SET     isDisabled = ?
                        WHERE   postID = ?";
                $statement = WCF::getDB()->prepare($sql);
                $statement->execute([1, $thread->firstPostID]);
            }
        }

        if ($threadIDs !== []) {
            UserActivityEventHandler::getInstance()->removeEvents('com.tinsever.forum.recentActivityEvent.thread', $threadIDs);
        }

        $this->afterVisibilityChange();
    }

    /**
     * Toggles a boolean flag and writes a log entry for each changed thread.
     */
    protected function setFlag(string $column, bool $value, string $logAction): void
    {
        if (empty($this->objects)) {
            $this->readObjects();
        }

        foreach ($this->getObjects() as $thread) {
            if ((bool)$thread->{$column} === $value) {
                continue;
            }

            $thread->update([$column => $value ? 1 : 0]);
            ThreadModificationLogHandler::getInstance()->add($thread->getDecoratedObject(), $logAction);
        }

        ThreadRuntimeCache::getInstance()->removeObjects(\array_keys($this->getObjects()));
    }

    public function close(): void
    {
        $this->setFlag('isClosed', true, 'close');
    }

    public function open(): void
    {
        $this->setFlag('isClosed', false, 'open');
    }

    public function stick(): void
    {
        $this->setFlag('isSticky', true, 'sticky');
    }

    public function unstick(): void
    {
        $this->setFlag('isSticky', false, 'unsticky');
    }

    public function markAsDone(): void
    {
        $this->setFlag('isDone', true, 'done');
    }

    public function markAsUndone(): void
    {
        $this->setFlag('isDone', false, 'undone');
    }

    /**
     * Turns threads into announcements that are shown in the given boards.
     */
    public function setAnnouncement(): void
    {
        if (empty($this->objects)) {
            $this->readObjects();
        }

        foreach ($this->getObjects() as $thread) {
            $boardIDs = $this->parameters['boardIDs'] ?? [$thread->boardID];
            $this->saveAnnouncementBoards($thread->threadID, $boardIDs);

            if (!$thread->isAnnouncement) {
                $thread->update(['isAnnouncement' => 1, 'isSticky' => 0]);
                ThreadModificationLogHandler::getInstance()->add($thread->getDecoratedObject(), 'announcement');
            }
        }
    }

    public function unsetAnnouncement(): void
    {
        if (empty($this->objects)) {
            $this->readObjects();
        }

        foreach ($this->getObjects() as $thread) {
            if (!$thread->isAnnouncement) {
                continue;
            }

            $thread->update(['isAnnouncement' => 0]);
            $this->saveAnnouncementBoards($thread->threadID, []);
            ThreadModificationLogHandler::getInstance()->add($thread->getDecoratedObject(), 'unannouncement');
        }
    }

    /**
     * Moves threads into another board, optionally leaving a link in the old board.
     */
    public function move(): void
    {
        if (empty($this->objects)) {
            $this->readObjects();
        }

        $board = BoardCache::getInstance()->getBoard(\intval($this->parameters['boardID'] ?? 0));
        if ($board === null || !$board->isBoard()) {
            throw new UserInputException('boardID');
        }

        $leaveLink = !empty($this->parameters['leaveLink']);
        $linkExpires = \intval($this->parameters['linkExpires'] ?? 0);

        $oldBoardIDs = [];
        foreach ($this->getObjects() as $thread) {
            if ($thread->boardID == $board->boardID) {
                continue;
            }

            $oldBoardIDs[] = $thread->boardID;

            if ($thread->isLink()) {
                $thread->update(['boardID' => $board->boardID]);
                continue;
            }

            if ($leaveLink) {
                ThreadEditor::create([
                    'boardID' => $thread->boardID,
                    'languageID' => $thread->languageID,
                    'topic' => $thread->topic,
                    'time' => $thread->time,
                    'userID' => $thread->userID,
                    'username' => $thread->username,
                    'lastPostTime' => $thread->lastPostTime,
                    'lastPosterID' => $thread->lastPosterID,
                    'lastPoster' => $thread->lastPoster,
                    'replies' => $thread->replies,
                    'movedThreadID' => $thread->threadID,
                    'movedTime' => $linkExpires ? TIME_NOW + $linkExpires * 86400 : 0,
                ]);
            }

            ThreadModificationLogHandler::getInstance()->add($thread->getDecoratedObject(), 'move', [
                'oldBoardID' => $thread->boardID,
                'newBoardID' => $board->boardID,
            ]);

            $thread->update(['boardID' => $board->boardID]);

            // labels might not be available in the new board
            $availableGroupIDs = BoardCache::getInstance()->getLabelGroupIDs($board->boardID);
            $assigned = ThreadLabelObjectHandler::getInstance()->getAssignedLabels([$thread->threadID], false);
            $keep = [];
            foreach ($assigned[$thread->threadID] ?? [] as $label) {
                if (\in_array($label->groupID, $availableGroupIDs)) {
                    $keep[$label->groupID] = $label->labelID;
                }
            }
            if (\count($keep) !== \count($assigned[$thread->threadID] ?? [])) {
                ThreadLabelObjectHandler::getInstance()->setLabels($keep, $thread->threadID, false);
                $thread->update(['hasLabels' => $keep === [] ? 0 : 1]);
            }
        }

        $this->afterVisibilityChange(\array_merge($oldBoardIDs, [$board->boardID]));
    }

    /**
     * Merges the threads into the target thread given by `targetThreadID`.
     */
    public function merge(): void
    {
        if (empty($this->objects)) {
            $this->readObjects();
        }

        $target = new Thread(\intval($this->parameters['targetThreadID'] ?? 0));
        if (!$target->threadID) {
            throw new UserInputException('targetThreadID');
        }

        $sourceThreadIDs = [];
        $boardIDs = [$target->boardID];
        foreach ($this->getObjects() as $thread) {
            if ($thread->threadID == $target->threadID || $thread->isLink()) {
                continue;
            }
            $sourceThreadIDs[] = $thread->threadID;
            $boardIDs[] = $thread->boardID;
        }

        if ($sourceThreadIDs === []) {
            return;
        }

        $postList = new PostList();
        $postList->getConditionBuilder()->add('post.threadID IN (?)', [$sourceThreadIDs]);
        $postList->readObjectIDs();

        if ($postList->getObjectIDs() !== []) {
            (new PostAction($postList->getObjectIDs(), 'move', ['thread' => $target]))->executeAction();
        }

        ThreadModificationLogHandler::getInstance()->add($target, 'merge', [
            'count' => \count($sourceThreadIDs),
        ]);

        // the source threads are empty now and have been removed by the post action,
        // remaining links pointing to them are redirected to the target
        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add('movedThreadID IN (?)', [$sourceThreadIDs]);
        $sql = "UPDATE forum1_thread SET movedThreadID = ? " . $conditions;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute(\array_merge([$target->threadID], $conditions->getParameters()));

        PostAction::rebuildBoards($boardIDs);
        ThreadEditor::resetUnreadCounters();
    }

    /**
     * Sets the best answer of a thread, `postID = 0` removes it.
     */
    public function setBestAnswer(): void
    {
        if (empty($this->objects)) {
            $this->readObjects();
        }

        $postID = \intval($this->parameters['postID'] ?? 0);
        foreach ($this->getObjects() as $thread) {
            $thread->update([
                'bestAnswerPostID' => $postID ?: null,
                'isDone' => $postID && $thread->getBoard()->enableMarkingAsDone ? 1 : $thread->isDone,
            ]);
            ThreadModificationLogHandler::getInstance()->add($thread->getDecoratedObject(), 'bestAnswer', [
                'postID' => $postID,
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function validateMarkAsRead()
    {
        if (isset($this->parameters['visitTime'])) {
            $this->parameters['visitTime'] = \min(TIME_NOW, \intval($this->parameters['visitTime']));
        }

        if (empty($this->objects)) {
            $this->readObjects();
        }

        foreach ($this->getObjects() as $thread) {
            if (!$thread->canRead()) {
                throw new PermissionDeniedException();
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function markAsRead()
    {
        if (!WCF::getUser()->userID) {
            return;
        }

        if (empty($this->objects)) {
            $this->readObjects();
        }

        $visitTime = $this->parameters['visitTime'] ?? TIME_NOW;
        foreach ($this->getObjects() as $thread) {
            VisitTracker::getInstance()->trackObjectVisit(ThreadVisitHandler::THREAD_OBJECT_TYPE, $thread->threadID, $visitTime);
        }

        UserStorageHandler::getInstance()->reset([WCF::getUser()->userID], 'forumUnreadThreads');

        // confirm notifications about the read threads
        $threadIDs = \array_keys($this->getObjects());
        if ($threadIDs !== []) {
            $conditions = new PreparedStatementConditionBuilder();
            $conditions->add('threadID IN (?)', [$threadIDs]);
            $conditions->add('time <= ?', [$visitTime]);
            $sql = "SELECT postID FROM forum1_post " . $conditions;
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute($conditions->getParameters());
            $postIDs = $statement->fetchAll(\PDO::FETCH_COLUMN);
            if ($postIDs !== []) {
                foreach (['post', 'thread', 'mention', 'quote'] as $eventName) {
                    UserNotificationHandler::getInstance()->markAsConfirmed(
                        $eventName,
                        PostNotificationHandler::OBJECT_TYPE,
                        [WCF::getUser()->userID],
                        $postIDs
                    );
                }
            }
        }
    }

    /**
     * Marks all threads, or all threads of a board including its sub boards, as read.
     */
    public static function markAllAsRead(?Board $board = null): void
    {
        if (!WCF::getUser()->userID) {
            return;
        }

        if ($board === null) {
            VisitTracker::getInstance()->trackTypeVisit(ThreadVisitHandler::THREAD_OBJECT_TYPE);
        } else {
            foreach ($board->getAllChildIDs() as $boardID) {
                VisitTracker::getInstance()->trackObjectVisit(ThreadVisitHandler::BOARD_OBJECT_TYPE, $boardID);
            }
        }

        ThreadVisitHandler::getInstance()->reset();
        UserStorageHandler::getInstance()->reset([WCF::getUser()->userID], 'forumUnreadThreads');
    }
}
