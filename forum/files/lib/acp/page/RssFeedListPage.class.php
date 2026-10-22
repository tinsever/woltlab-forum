<?php

namespace forum\acp\page;

use forum\system\gridView\admin\RssFeedGridView;
use wcf\page\AbstractGridViewPage;

/**
 * Lists the RSS feeds that are imported as threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractGridViewPage<RssFeedGridView>
 */
final class RssFeedListPage extends AbstractGridViewPage
{
    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'forum.acp.menu.link.rssFeed.list';

    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.forum.canManageRssFeed'];

    #[\Override]
    protected function createGridView(): RssFeedGridView
    {
        return new RssFeedGridView();
    }
}
