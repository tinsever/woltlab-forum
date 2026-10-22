<?php

namespace forum\data\rss\feed;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit RSS feeds.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @mixin RssFeed
 * @extends DatabaseObjectEditor<RssFeed>
 */
class RssFeedEditor extends DatabaseObjectEditor
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = RssFeed::class;
}
