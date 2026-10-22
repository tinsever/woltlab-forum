<?php

namespace forum\page;

use forum\system\board\BoardBreadcrumbs;
use forum\data\post\Post;
use forum\data\post\ViewablePostList;
use forum\data\thread\Thread;
use forum\data\thread\ThreadAction;
use forum\data\thread\ThreadEditor;
use forum\system\cache\runtime\ThreadRuntimeCache;
use forum\system\interaction\bulk\user\PostBulkInteractions;
use forum\system\interaction\user\PostInteractions;
use forum\system\interaction\user\ThreadPageInteractions;
use forum\system\thread\ThreadStatusHandler;
use forum\system\thread\ThreadVisibility;
use wcf\data\smiley\SmileyCache;
use wcf\data\user\UserProfile;
use wcf\page\MultipleLinkPage;
use wcf\system\attachment\AttachmentHandler;
use wcf\system\bbcode\BBCodeHandler;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\interaction\bulk\BulkFormBuilderDialogInteraction;
use wcf\system\interaction\bulk\IBulkInteraction;
use wcf\system\interaction\InteractionContextMenuComponent;
use wcf\system\interaction\StandaloneInteractionContextMenuComponent;
use wcf\system\page\PageLocationManager;
use wcf\system\poll\PollManager;
use wcf\system\reaction\ReactionHandler;
use wcf\system\request\LinkHandler;
use wcf\system\tagging\TagEngine;
use wcf\system\user\signature\SignatureCache;
use wcf\system\WCF;
use wcf\util\HeaderUtil;

/**
 * Shows the posts of a thread.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends MultipleLinkPage<ViewablePostList>
 */
class ThreadPage extends MultipleLinkPage
{
    /**
     * @inheritDoc
     */
    public $objectListClassName = ViewablePostList::class;

    public int $threadID = 0;

    public int $postID = 0;

    public Thread $thread;

    public ?Post $post = null;

    public array $likeData = [];

    public array $polls = [];

    public array $tags = [];

    /**
     * @var Thread[]
     */
    public array $similarThreads = [];

    #[\Override]
    public function readParameters()
    {
        parent::readParameters();

        if (isset($_REQUEST['id'])) {
            $this->threadID = \intval($_REQUEST['id']);
        }
        if (isset($_REQUEST['postID'])) {
            $this->postID = \intval($_REQUEST['postID']);
            $this->post = new Post($this->postID);
            if (!$this->post->postID) {
                throw new IllegalLinkException();
            }
            $this->threadID = $this->post->threadID;
        }

        $thread = ThreadRuntimeCache::getInstance()->getObject($this->threadID);
        if ($thread === null) {
            throw new IllegalLinkException();
        }
        $this->thread = $thread;

        if ($this->thread->isLink()) {
            HeaderUtil::redirect((new Thread($this->thread->movedThreadID))->getLink(), true);

            exit;
        }

        if (!$this->thread->canRead()) {
            throw new PermissionDeniedException();
        }
        if ($this->post !== null && !$this->post->canRead()) {
            throw new PermissionDeniedException();
        }

        $board = $this->thread->getBoard();
        if ($board->styleID) {
            \wcf\system\style\StyleHandler::getInstance()->changeStyle($board->styleID, true);
        }

        $this->itemsPerPage = $board->getPostsPerPage();
        $this->sortOrder = $board->getPostSortOrder();
        $this->sortField = 'post.time';

        $this->canonicalURL = LinkHandler::getInstance()->getControllerLink(static::class, [
            'object' => $this->thread,
            'pageNo' => $this->pageNo > 1 ? $this->pageNo : null,
        ]);
    }

    #[\Override]
    protected function initObjectList()
    {
        parent::initObjectList();

        $conditions = $this->objectList->getConditionBuilder();
        $conditions->add('post.threadID = ?', [$this->thread->threadID]);
        if (!$this->thread->getPermission('canReadDeletedPost')) {
            $conditions->add('post.isDeleted = ?', [0]);
        }
        if (!$this->thread->getPermission('canEnablePost')) {
            if (WCF::getUser()->userID) {
                $conditions->add('(post.isDisabled = ? OR post.userID = ?)', [0, WCF::getUser()->userID]);
            } else {
                $conditions->add('post.isDisabled = ?', [0]);
            }
        }

        if ($this->action === 'lastPost') {
            $this->goToPost($this->getEdgePostID('DESC'));
        } elseif ($this->action === 'firstNew') {
            $this->goToFirstNewPost();
        } elseif ($this->post !== null) {
            $this->calculatePostPageNo($this->post);
        }
    }

    /**
     * Returns the id of the first or last visible post.
     */
    private function getEdgePostID(string $order): int
    {
        $sql = "SELECT      post.postID
                FROM        forum1_post post
                " . $this->objectList->getConditionBuilder() . "
                ORDER BY    post.time {$order}, post.postID {$order}";
        $statement = WCF::getDB()->prepare($sql, 1);
        $statement->execute($this->objectList->getConditionBuilder()->getParameters());

        return \intval($statement->fetchSingleColumn());
    }

    private function goToPost(int $postID): void
    {
        if (!$postID) {
            return;
        }

        HeaderUtil::redirect(
            LinkHandler::getInstance()->getControllerLink(static::class, [
                'object' => $this->thread,
                'postID' => $postID,
            ]) . '#post' . $postID
        );

        exit;
    }

    private function goToFirstNewPost(): void
    {
        $conditions = clone $this->objectList->getConditionBuilder();
        $conditions->add('post.time > ?', [$this->thread->getVisitTime()]);

        $sql = "SELECT      post.postID
                FROM        forum1_post post
                " . $conditions . "
                ORDER BY    post.time ASC, post.postID ASC";
        $statement = WCF::getDB()->prepare($sql, 1);
        $statement->execute($conditions->getParameters());
        $postID = \intval($statement->fetchSingleColumn());

        $this->goToPost($postID ?: $this->getEdgePostID('DESC'));
    }

    private function calculatePostPageNo(Post $post): void
    {
        $conditions = clone $this->objectList->getConditionBuilder();
        $operator = $this->sortOrder === 'ASC' ? '<' : '>';
        $conditions->add(
            "(post.time {$operator} ? OR (post.time = ? AND post.postID {$operator}= ?))",
            [$post->time, $post->time, $post->postID]
        );

        $sql = "SELECT  COUNT(*)
                FROM    forum1_post post
                " . $conditions;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditions->getParameters());
        $this->pageNo = \intval(\ceil($statement->fetchSingleColumn() / $this->itemsPerPage));
    }

    #[\Override]
    public function readData()
    {
        parent::readData();

        BoardBreadcrumbs::addBoard($this->thread->getBoard());

        $this->updateViews();
        $this->markAsRead();

        $postIDs = $userIDs = $pollIDs = [];
        foreach ($this->objectList as $post) {
            $postIDs[] = $post->postID;
            if ($post->userID) {
                $userIDs[] = $post->userID;
            }
            if ($post->pollID) {
                $pollIDs[] = $post->pollID;
            }
        }
        $userIDs = \array_unique($userIDs);

        if ($userIDs !== []) {
            if (\MODULE_TROPHY) {
                UserProfile::prepareSpecialTrophies($userIDs);
            }
            if (\MODULE_USER_SIGNATURE) {
                SignatureCache::getInstance()->cacheUserSignature($userIDs);
            }
        }

        if ($this->objectList->getAttachmentList() !== null) {
            $this->objectList->getAttachmentList()->setPermissions([
                'canDownload' => $this->thread->getPermission('canDownloadAttachment'),
                'canViewPreview' => $this->thread->getPermission('canViewAttachmentPreview'),
            ]);
        }

        if (\MODULE_LIKE && $postIDs !== []) {
            $objectType = ReactionHandler::getInstance()->getObjectType('com.tinsever.forum.likeablePost');
            ReactionHandler::getInstance()->loadLikeObjects($objectType, $postIDs);
            $this->likeData = ReactionHandler::getInstance()->getLikeObjects($objectType);
        }

        if (\MODULE_POLL && $pollIDs !== []) {
            $this->polls = PollManager::getInstance()->getPolls($pollIDs);
            foreach ($this->polls as $poll) {
                $poll->setRelatedObject($this->objectList->search($this->getPostIDForPoll($poll->pollID)));
            }
        }

        if (\MODULE_TAGGING && WCF::getSession()->getPermission('user.tag.canViewTag')) {
            $this->tags = TagEngine::getInstance()->getObjectTags(
                'com.tinsever.forum.thread',
                $this->thread->threadID,
                [$this->thread->languageID ?: null]
            );
        }

        if (\FORUM_THREAD_ENABLE_SIMILAR_THREADS && $this->pageNo === $this->pages) {
            $this->readSimilarThreads();
        }
    }

    private function getPostIDForPoll(int $pollID): int
    {
        foreach ($this->objectList as $post) {
            if ($post->pollID == $pollID) {
                return $post->postID;
            }
        }

        return 0;
    }

    /**
     * Increments the view counter once per session.
     */
    private function updateViews(): void
    {
        $viewed = WCF::getSession()->getVar('forumViewedThreads') ?: [];
        if (isset($viewed[$this->thread->threadID])) {
            return;
        }

        $viewed[$this->thread->threadID] = true;
        WCF::getSession()->register('forumViewedThreads', $viewed);

        (new ThreadEditor($this->thread))->updateCounters(['views' => 1]);
    }

    /**
     * Marks the posts of the current page as read.
     */
    private function markAsRead(): void
    {
        if (!WCF::getUser()->userID || !$this->thread->isNew()) {
            return;
        }

        $maxPostTime = $this->objectList->getMaxPostTime();
        if ($maxPostTime <= $this->thread->getVisitTime()) {
            return;
        }

        $visitTime = $maxPostTime >= $this->thread->lastPostTime ? TIME_NOW : $maxPostTime;
        (new ThreadAction([$this->thread], 'markAsRead', ['visitTime' => $visitTime]))->executeAction();
    }

    /**
     * Searches for threads with a similar topic.
     */
    private function readSimilarThreads(): void
    {
        $sql = "SELECT      thread.threadID
                FROM        forum1_thread thread
                WHERE       MATCH(thread.topic) AGAINST (?)
                        AND thread.threadID <> ?
                        AND thread.movedThreadID IS NULL
                ORDER BY    MATCH(thread.topic) AGAINST (?) DESC";
        $statement = WCF::getDB()->prepare($sql, 25);
        $statement->execute([$this->thread->topic, $this->thread->threadID, $this->thread->topic]);
        $threadIDs = $statement->fetchAll(\PDO::FETCH_COLUMN);
        if ($threadIDs === []) {
            return;
        }

        $threadList = new \forum\data\thread\ViewableThreadList(null, true, true, false);
        ThreadVisibility::addConditions($threadList->getConditionBuilder(), null, 'thread', true);
        $threadList->getConditionBuilder()->add('thread.threadID IN (?)', [$threadIDs]);
        $threadList->getConditionBuilder()->add('thread.isDeleted = ?', [0]);
        $threadList->getConditionBuilder()->add('thread.isDisabled = ?', [0]);
        $threadList->sqlLimit = \FORUM_THREAD_SIMILAR_THREADS_COUNT;
        $threadList->readObjects();

        $this->similarThreads = [];
        foreach ($threadIDs as $threadID) {
            $thread = $threadList->search($threadID);
            if ($thread !== null) {
                $this->similarThreads[] = $thread;
            }
            if (\count($this->similarThreads) >= \FORUM_THREAD_SIMILAR_THREADS_COUNT) {
                break;
            }
        }
    }

    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();

        $identifier = WCF::getUser()->userID ?: WCF::getSession()->sessionID;
        $tmpHash = \sha1(\implode("\0", [self::class, $this->thread->threadID, $identifier]));
        $attachmentHandler = null;
        if ($this->thread->getPermission('canUploadAttachment')) {
            $attachmentHandler = new AttachmentHandler(Post::OBJECT_TYPE, 0, $tmpHash, $this->thread->boardID);
        }

        $status = ThreadStatusHandler::getStatus($this->thread->threadID);

        // A new poll can be added to the quick reply.
        $canStartPoll = \MODULE_POLL && $this->thread->canReply() && $this->thread->getPermission('canStartPoll');
        if ($canStartPoll) {
            PollManager::getInstance()->setObject(Post::OBJECT_TYPE, 0);
            WCF::getTPL()->assign([
                '__showPoll' => true,
                'pollID' => 0,
                'pollOptions' => [],
                'pollQuestion' => '',
                'pollEndTime' => 0,
                'pollMaxVotes' => 1,
                'pollIsChangeable' => 0,
                'pollIsPublic' => 0,
                'pollResultsRequireVote' => 0,
                'pollSortByVotes' => 0,
                'canStartPublicPoll' => PollManager::getInstance()->canStartPublicPoll(),
            ]);
        }

        // Dialog based bulk interactions are handled by `Forum/Ui/Post/List` because
        // they redirect to the thread the posts were moved, copied or merged into.
        $postBulkInteractionInitialization = '';
        $postBulkDialogInteractions = [];
        if ($this->canModeratePosts()) {
            foreach ((new PostBulkInteractions())->getInteractions() as $interaction) {
                if ($interaction instanceof BulkFormBuilderDialogInteraction) {
                    $postBulkDialogInteractions[] = $interaction->getIdentifier();
                } elseif ($interaction instanceof IBulkInteraction) {
                    $postBulkInteractionInitialization .= $interaction->renderInitialization('forumPostList') . "\n";
                }
            }
        }

        WCF::getTPL()->assign([
            'thread' => $this->thread,
            'board' => $this->thread->getBoard(),
            'threadID' => $this->thread->threadID,
            'sortOrder' => $this->sortOrder,
            'attachmentList' => $this->objectList->getAttachmentList(),
            'attachmentHandler' => $attachmentHandler,
            'attachmentObjectID' => 0,
            'attachmentObjectType' => Post::OBJECT_TYPE,
            'attachmentParentObjectID' => $this->thread->boardID,
            'tmpHash' => $tmpHash,
            'likeData' => $this->likeData,
            'polls' => $this->polls,
            'tags' => $this->tags,
            'similarThreads' => $this->similarThreads,
            'defaultSmilies' => SmileyCache::getInstance()->getCategorySmilies(),
            'threadStatus' => $status['status'] ?? '',
            'postInteractions' => new InteractionContextMenuComponent(new PostInteractions()),
            'canModeratePosts' => $this->canModeratePosts(),
            'postBulkInteractionInitialization' => $postBulkInteractionInitialization,
            'postBulkDialogInteractions' => $postBulkDialogInteractions,
            'interactionContextMenu' => StandaloneInteractionContextMenuComponent::forContentInteractionButton(
                new ThreadPageInteractions(),
                $this->thread,
                $this->thread->getBoard()->getLink(),
                WCF::getLanguage()->get('forum.thread.edit'),
                "forum/threads/{$this->thread->threadID}/content-header-title"
            ),
        ]);

        BBCodeHandler::getInstance()->setDisallowedBBCodes(\explode(
            ',',
            WCF::getSession()->getPermission('user.message.disallowedBBCodes')
        ));
    }

    /**
     * Returns true if the active user may select posts for bulk moderation.
     */
    private function canModeratePosts(): bool
    {
        $board = $this->thread->getBoard();
        foreach (['canMovePost', 'canMergePost', 'canEnablePost', 'canClosePost', 'canDeletePost', 'canRestorePost'] as $permission) {
            if ($board->getPermission($permission)) {
                return true;
            }
        }

        return false;
    }
}
