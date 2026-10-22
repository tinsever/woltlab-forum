<?php

namespace forum\data\rss\feed;

use forum\data\board\Board;
use forum\data\board\BoardCache;
use wcf\data\DatabaseObject;
use wcf\data\ITitledObject;
use wcf\util\ArrayUtil;

/**
 * Represents an RSS feed whose entries are imported as threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @property-read int $feedID
 * @property-read int $boardID
 * @property-read string $title
 * @property-read string $url
 * @property-read int|null $userID
 * @property-read string $username
 * @property-read int|null $languageID
 * @property-read int $cycleTime
 * @property-read int $maxResults
 * @property-read string $searchKeywords
 * @property-read string $threadTags
 * @property-read int $useCategoriesAsTags
 * @property-read int $closeThread
 * @property-read int $disableThread
 * @property-read int $isDisabled
 * @property-read int $lastRun
 * @property-read string|null $lastError
 */
class RssFeed extends DatabaseObject implements ITitledObject
{
    /**
     * @inheritDoc
     */
    protected static $databaseTableName = 'rss_feed';

    /**
     * @inheritDoc
     */
    protected static $databaseTableIndexName = 'feedID';

    #[\Override]
    public function getTitle(): string
    {
        return $this->title ?: $this->url;
    }

    public function getBoard(): ?Board
    {
        return BoardCache::getInstance()->getBoard($this->boardID);
    }

    /**
     * Returns the keywords an entry must contain to be imported.
     *
     * @return string[]
     */
    public function getSearchKeywords(): array
    {
        return \array_values(\array_filter(ArrayUtil::trim(\explode("\n", $this->searchKeywords ?? ''))));
    }

    /**
     * Returns the tags that are added to every imported thread.
     *
     * @return string[]
     */
    public function getThreadTags(): array
    {
        return \array_values(\array_filter(ArrayUtil::trim(\explode(',', $this->threadTags ?? ''))));
    }

    /**
     * Returns true if the feed is due to be checked for new entries.
     */
    public function isDue(): bool
    {
        return !$this->isDisabled && $this->lastRun + $this->cycleTime <= TIME_NOW;
    }
}
