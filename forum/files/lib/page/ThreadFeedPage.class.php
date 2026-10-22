<?php

namespace forum\page;

use forum\data\board\Board;
use forum\data\board\BoardCache;
use forum\data\post\ViewablePostList;
use forum\data\thread\Thread;
use forum\data\thread\ViewableThreadList;
use forum\system\cache\runtime\PostRuntimeCache;
use forum\system\cache\runtime\ThreadRuntimeCache;
use wcf\page\AbstractRssFeedPage;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\rssFeed\RssFeed;
use wcf\system\rssFeed\RssFeedItem;
use wcf\system\WCF;

/**
 * RSS feed with the latest threads of all boards or a single board, or with
 * the latest posts of a thread.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class ThreadFeedPage extends AbstractRssFeedPage
{
    public ?Board $board = null;

    public ?Thread $thread = null;

    public ?ViewableThreadList $threadList = null;

    public ?ViewablePostList $postList = null;

    #[\Override]
    public function readParameters()
    {
        parent::readParameters();

        if (!empty($_REQUEST['threadID'])) {
            $this->thread = ThreadRuntimeCache::getInstance()->getObject(\intval($_REQUEST['threadID']));
            if ($this->thread === null || $this->thread->isLink()) {
                throw new IllegalLinkException();
            }
            if (!$this->thread->canRead()) {
                throw new PermissionDeniedException();
            }
        } elseif (!empty($_REQUEST['id'])) {
            $this->board = BoardCache::getInstance()->getBoard(\intval($_REQUEST['id']));
            if ($this->board === null || !$this->board->isBoard()) {
                throw new IllegalLinkException();
            }
            if (!$this->board->canReadThreads()) {
                throw new PermissionDeniedException();
            }
        }
    }

    #[\Override]
    public function readData()
    {
        parent::readData();

        if ($this->thread !== null) {
            $this->postList = new ViewablePostList();
            $this->postList->getConditionBuilder()->add('post.threadID = ?', [$this->thread->threadID]);
            $this->postList->getConditionBuilder()->add('post.isDeleted = ?', [0]);
            $this->postList->getConditionBuilder()->add('post.isDisabled = ?', [0]);
            $this->postList->sqlOrderBy = 'post.time DESC, post.postID DESC';
            $this->postList->sqlLimit = 20;
            $this->postList->readObjects();

            return;
        }

        $this->threadList = new ViewableThreadList($this->board !== null ? $this->board->getAllChildIDs() : null, $this->board === null);
        $this->threadList->getConditionBuilder()->add('thread.isDeleted = ?', [0]);
        $this->threadList->getConditionBuilder()->add('thread.isDisabled = ?', [0]);
        $this->threadList->getConditionBuilder()->add('thread.movedThreadID IS NULL');
        $this->threadList->sqlOrderBy = 'thread.time DESC';
        $this->threadList->sqlLimit = 20;
        $this->threadList->readObjects();

        $firstPostIDs = [];
        foreach ($this->threadList as $thread) {
            if ($thread->firstPostID) {
                $firstPostIDs[] = $thread->firstPostID;
            }
        }
        PostRuntimeCache::getInstance()->cacheObjectIDs($firstPostIDs);
    }

    #[\Override]
    protected function getRssFeed(): RssFeed
    {
        $feed = new RssFeed();
        $channel = $this->getDefaultChannel();

        if ($this->thread !== null) {
            $channel->title(\sprintf('%s - %s', $this->thread->topic, WCF::getLanguage()->get(\PAGE_TITLE)));
            $channel->link($this->thread->getLink());
            $feed->channel($channel);

            foreach ($this->postList as $post) {
                $item = new RssFeedItem();
                $item
                    ->title($post->getTitle())
                    ->link($post->getLink())
                    ->guid($post->getLink())
                    ->pubDateFromTimestamp($post->time)
                    ->creator($post->username)
                    ->description($post->getExcerpt())
                    ->contentEncoded($post->getSimplifiedFormattedMessage());
                $channel->item($item);
            }

            return $feed;
        }

        $title = $this->board !== null ? $this->board->getTitle() : WCF::getLanguage()->get('forum.thread.latestThreads');
        $channel->title(\sprintf('%s - %s', $title, WCF::getLanguage()->get(\PAGE_TITLE)));
        if ($this->board !== null) {
            $channel->link($this->board->getLink());
        }
        if ($this->threadList->valid()) {
            $channel->lastBuildDateFromTimestamp($this->threadList->current()->time);
        }
        $feed->channel($channel);

        foreach ($this->threadList as $thread) {
            $item = new RssFeedItem();
            $item
                ->title($thread->topic)
                ->link($thread->getLink())
                ->guid($thread->getLink())
                ->pubDateFromTimestamp($thread->time)
                ->creator($thread->username)
                ->slashComments($thread->replies)
                ->category($thread->getBoard()->getTitle(), $thread->getBoard()->getLink());

            $post = $thread->getFirstPost();
            if ($post !== null) {
                $item
                    ->description($post->getExcerpt())
                    ->contentEncoded($post->getSimplifiedFormattedMessage());
            }

            $channel->item($item);
        }

        return $feed;
    }
}
