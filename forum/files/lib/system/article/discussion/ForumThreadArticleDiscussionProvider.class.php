<?php

namespace forum\system\article\discussion;

use forum\data\board\BoardCache;
use forum\data\post\Post;
use forum\data\post\PostList;
use forum\data\thread\Thread;
use forum\data\thread\ThreadAction;
use wcf\data\article\Article;
use wcf\data\article\content\ArticleContent;
use wcf\data\article\content\ViewableArticleContent;
use wcf\data\user\UserProfile;
use wcf\system\article\discussion\AbstractArticleDiscussionProvider;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;

/**
 * Discusses articles in a forum thread instead of using comments.
 *
 * A thread is created for each article content in the board configured by
 * `FORUM_ARTICLE_DISCUSSION_BOARD_ID` once the article is viewed for the first
 * time after it has been published.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ForumThreadArticleDiscussionProvider extends AbstractArticleDiscussionProvider
{
    private const POST_LIMIT = 5;

    /**
     * @var array<int, ?int>
     */
    private static array $threadIDs = [];

    #[\Override]
    public function getDiscussionCount()
    {
        return $this->getThread()?->replies ?? 0;
    }

    #[\Override]
    public function getDiscussionCountPhrase()
    {
        return WCF::getLanguage()->getDynamicVariable('forum.article.discussion.count', [
            'count' => $this->getDiscussionCount(),
        ]);
    }

    #[\Override]
    public function getDiscussionLink()
    {
        $thread = $this->getThread();
        if ($thread !== null && $thread->canRead()) {
            return $thread->getLink();
        }

        return $this->getArticleContent()->getLink() . '#forumArticleDiscussion';
    }

    #[\Override]
    public function renderDiscussions()
    {
        $thread = $this->getThread(true);
        if ($thread === null || !$thread->canRead()) {
            return '';
        }

        $postList = new PostList();
        $postList->getConditionBuilder()->add('post.threadID = ?', [$thread->threadID]);
        $postList->getConditionBuilder()->add('post.postID <> ?', [$thread->firstPostID]);
        $postList->getConditionBuilder()->add('post.isDeleted = ?', [0]);
        $postList->getConditionBuilder()->add('post.isDisabled = ?', [0]);
        $postList->sqlOrderBy = 'post.time DESC, post.postID DESC';
        $postList->sqlLimit = self::POST_LIMIT;
        $postList->readObjects();
        $posts = \array_reverse($postList->getObjects());

        UserProfileRuntimeCache::getInstance()->cacheObjectIDs(\array_filter(\array_map(
            static fn(Post $post) => $post->userID,
            $posts
        )));

        return WCF::getTPL()->render('forum', 'articleDiscussion', [
            'discussionThread' => $thread,
            'discussionPosts' => $posts,
            'discussionMorePosts' => \max(0, $thread->replies - \count($posts)),
        ]);
    }

    #[\Override]
    public function migrateDiscussions(ArticleContent $oldContent, ArticleContent $newContent): void
    {
        $sql = "UPDATE IGNORE   forum1_article_thread
                SET             articleContentID = ?
                WHERE           articleContentID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$newContent->articleContentID, $oldContent->articleContentID]);

        unset(self::$threadIDs[$oldContent->articleContentID], self::$threadIDs[$newContent->articleContentID]);
    }

    #[\Override]
    public static function isResponsible(Article $article)
    {
        if (!$article->enableComments || !\FORUM_ARTICLE_DISCUSSION_BOARD_ID) {
            return false;
        }

        $board = BoardCache::getInstance()->getBoard(\FORUM_ARTICLE_DISCUSSION_BOARD_ID);

        return $board !== null && $board->isBoard();
    }

    private function getArticleContent(): ArticleContent|ViewableArticleContent
    {
        return $this->articleContent ?: $this->article->getArticleContent();
    }

    /**
     * Returns the discussion thread, optionally creating it if the article has
     * never been discussed before.
     */
    private function getThread(bool $create = false): ?Thread
    {
        $articleContent = $this->getArticleContent();
        $articleContentID = $articleContent->articleContentID;

        if (!\array_key_exists($articleContentID, self::$threadIDs)) {
            $sql = "SELECT  threadID
                    FROM    forum1_article_thread
                    WHERE   articleContentID = ?";
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute([$articleContentID]);
            $row = $statement->fetchArray();

            if ($row === false) {
                if (!$create || !$this->canCreateThread()) {
                    return null;
                }

                self::$threadIDs[$articleContentID] = $this->createThread($articleContent);
            } else {
                self::$threadIDs[$articleContentID] = $row['threadID'] !== null ? (int)$row['threadID'] : null;
            }
        }

        $threadID = self::$threadIDs[$articleContentID];
        if ($threadID === null) {
            return null;
        }

        $thread = new Thread($threadID);

        return $thread->threadID && !$thread->isDeleted ? $thread : null;
    }

    private function canCreateThread(): bool
    {
        return $this->article->publicationStatus == Article::PUBLISHED && !$this->article->isDeleted;
    }

    private function createThread(ArticleContent|ViewableArticleContent $articleContent): ?int
    {
        // claim the article first, concurrent requests must not create a second thread
        $sql = "INSERT IGNORE INTO  forum1_article_thread
                                    (articleContentID, threadID)
                VALUES              (?, NULL)";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$articleContent->articleContentID]);
        if (!$statement->getAffectedRows()) {
            return null;
        }

        try {
            $threadID = $this->createThreadForClaimedArticle($articleContent);
        } catch (\Throwable $e) {
            $sql = "DELETE FROM forum1_article_thread
                    WHERE       articleContentID = ?";
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute([$articleContent->articleContentID]);

            throw $e;
        }

        $sql = "UPDATE  forum1_article_thread
                SET     threadID = ?
                WHERE   articleContentID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$threadID, $articleContent->articleContentID]);

        return $threadID;
    }

    private function createThreadForClaimedArticle(ArticleContent|ViewableArticleContent $articleContent): int
    {
        $language = LanguageFactory::getInstance()->getLanguage(
            $articleContent->languageID ?: LanguageFactory::getInstance()->getDefaultLanguageID()
        ) ?? WCF::getLanguage();
        $message = $language->getDynamicVariable('forum.article.discussion.message', [
            'articleContent' => $articleContent,
        ]);
        $htmlInputProcessor = new HtmlInputProcessor();
        $htmlInputProcessor->process($message, Post::OBJECT_TYPE);

        $author = $this->article->userID ? UserProfileRuntimeCache::getInstance()->getObject($this->article->userID) : null;

        $thread = (new ThreadAction([], 'create', [
            'data' => [
                'boardID' => \FORUM_ARTICLE_DISCUSSION_BOARD_ID,
                'languageID' => $articleContent->languageID,
                'topic' => \mb_substr($articleContent->title, 0, 255),
                'time' => $this->article->time ?: TIME_NOW,
                'userID' => $author instanceof UserProfile ? $author->userID : null,
                'username' => $author instanceof UserProfile ? $author->username : $this->article->username,
                'isDisabled' => 0,
            ],
            'postData' => [
                'ipAddress' => '',
            ],
            'htmlInputProcessor' => $htmlInputProcessor,
        ]))->executeAction()['returnValues'];
        \assert($thread instanceof Thread);

        return $thread->threadID;
    }
}
