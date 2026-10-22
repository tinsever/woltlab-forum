<?php

namespace forum\system\moderation;

use forum\system\listView\user\ThreadListView;
use wcf\system\listView\AbstractListView;
use wcf\system\moderation\AbstractDeletedContentListViewProvider;

/**
 * Lists the soft-deleted threads in the moderation panel.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class DeletedThreadProvider extends AbstractDeletedContentListViewProvider
{
    public function __construct()
    {
        parent::__construct('com.tinsever.forum.thread', 'forum.thread.threads', 'forum.thread.deletedThreads');
    }

    #[\Override]
    public function getListView(): AbstractListView
    {
        return new ThreadListView(0, 'deleted');
    }
}
