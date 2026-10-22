<?php

namespace forum\system\rss;

use forum\data\post\Post;
use forum\data\rss\feed\RssFeed;
use forum\data\rss\feed\RssFeedEditor;
use forum\data\thread\ThreadAction;
use wcf\data\user\User;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Imports the entries of an RSS feed as threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class RssFeedImporter
{
    public function __construct(
        private readonly RssFeedReader $reader = new RssFeedReader(),
    ) {}

    /**
     * Fetches the feed and creates a thread for every entry that has not been
     * imported yet. Returns the number of created threads.
     */
    public function import(RssFeed $feed): int
    {
        $editor = new RssFeedEditor($feed);
        $board = $feed->getBoard();
        if ($board === null || !$board->isBoard()) {
            $editor->update(['lastRun' => TIME_NOW, 'lastError' => 'The target board does not exist.']);

            return 0;
        }

        try {
            $items = $this->reader->read($feed->url);
        } catch (\Throwable $e) {
            $editor->update(['lastRun' => TIME_NOW, 'lastError' => StringUtil::truncate($e->getMessage(), 1000)]);

            return 0;
        }

        $items = $this->filterItems($feed, $items);

        // create the oldest entries first to keep the thread order intact
        $count = 0;
        foreach (\array_reverse($items) as $item) {
            $this->createThread($feed, $item);
            $this->log($feed, $item);
            $count++;
        }

        $editor->update(['lastRun' => TIME_NOW, 'lastError' => null]);

        return $count;
    }

    /**
     * Removes entries that were already imported or do not match the keywords.
     *
     * @param RssFeedItem[] $items
     * @return RssFeedItem[]
     */
    private function filterItems(RssFeed $feed, array $items): array
    {
        $items = \array_filter($items, static fn(RssFeedItem $item) => $item->title !== '');
        if ($items === []) {
            return [];
        }

        $hashes = \array_map(static fn(RssFeedItem $item) => $item->getHash(), $items);
        $sql = "SELECT  hash
                FROM    forum1_rss_feed_log
                WHERE   feedID = ?
                    AND hash IN (?" . \str_repeat(', ?', \count($hashes) - 1) . ")";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$feed->feedID, ...\array_values($hashes)]);
        $knownHashes = \array_flip($statement->fetchAll(\PDO::FETCH_COLUMN));

        $keywords = \array_map(\mb_strtolower(...), $feed->getSearchKeywords());
        $result = [];
        foreach ($items as $item) {
            if (isset($knownHashes[$item->getHash()])) {
                continue;
            }

            if ($keywords !== []) {
                $haystack = \mb_strtolower($item->title . ' ' . \strip_tags($item->content));
                $matches = false;
                foreach ($keywords as $keyword) {
                    if (\str_contains($haystack, $keyword)) {
                        $matches = true;
                        break;
                    }
                }

                if (!$matches) {
                    continue;
                }
            }

            $result[] = $item;
            if ($feed->maxResults && \count($result) >= $feed->maxResults) {
                break;
            }
        }

        return $result;
    }

    private function createThread(RssFeed $feed, RssFeedItem $item): void
    {
        $message = $item->content;
        if ($item->link !== '') {
            $message .= \sprintf(
                '<p><a href="%s">%s</a></p>',
                StringUtil::encodeHTML($item->link),
                StringUtil::encodeHTML($item->link)
            );
        }

        $htmlInputProcessor = new HtmlInputProcessor();
        $htmlInputProcessor->process($message, Post::OBJECT_TYPE);

        $username = $feed->username;
        if ($feed->userID) {
            $user = new User($feed->userID);
            if ($user->userID) {
                $username = $user->username;
            }
        }

        $tags = $feed->getThreadTags();
        if ($feed->useCategoriesAsTags) {
            $tags = \array_merge($tags, $item->categories);
        }
        $tags = \array_values(\array_unique($tags));

        (new ThreadAction([], 'create', [
            'data' => [
                'boardID' => $feed->boardID,
                'languageID' => $feed->languageID,
                'topic' => StringUtil::truncate($item->title, 255),
                'time' => TIME_NOW,
                'userID' => $feed->userID ?: null,
                'username' => $username ?: 'RSS',
                'isClosed' => $feed->closeThread,
                'isDisabled' => $feed->disableThread,
            ],
            'postData' => [
                'ipAddress' => '',
            ],
            'htmlInputProcessor' => $htmlInputProcessor,
            'tags' => \MODULE_TAGGING ? $tags : [],
        ]))->executeAction();
    }

    private function log(RssFeed $feed, RssFeedItem $item): void
    {
        $sql = "INSERT IGNORE INTO  forum1_rss_feed_log
                                    (feedID, hash, time)
                VALUES              (?, ?, ?)";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$feed->feedID, $item->getHash(), TIME_NOW]);
    }
}
