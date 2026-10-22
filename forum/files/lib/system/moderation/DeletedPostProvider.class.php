<?php

namespace forum\system\moderation;

use forum\system\listView\user\DeletedPostListView;
use wcf\system\listView\AbstractListView;
use wcf\system\moderation\AbstractDeletedContentListViewProvider;

/**
 * Lists the soft-deleted posts in the moderation panel.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class DeletedPostProvider extends AbstractDeletedContentListViewProvider
{
    public function __construct()
    {
        parent::__construct('com.tinsever.forum.post', 'forum.post.posts', 'forum.post.deletedPosts');
    }

    #[\Override]
    public function getListView(): AbstractListView
    {
        return new DeletedPostListView();
    }
}
