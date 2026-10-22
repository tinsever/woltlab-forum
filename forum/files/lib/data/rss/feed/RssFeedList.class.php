<?php

namespace forum\data\rss\feed;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of RSS feeds.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends DatabaseObjectList<RssFeed>
 */
class RssFeedList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = RssFeed::class;
}
