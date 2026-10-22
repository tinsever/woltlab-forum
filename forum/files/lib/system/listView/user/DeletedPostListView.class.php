<?php

namespace forum\system\listView\user;

use forum\data\post\Post;
use forum\data\post\ViewablePostList;
use forum\system\board\BoardPermissionHandler;
use forum\system\interaction\user\PostInteractions;
use forum\system\thread\ThreadVisibility;
use wcf\data\DatabaseObjectList;
use wcf\system\listView\AbstractListView;
use wcf\system\listView\ListViewSortField;
use wcf\system\WCF;

/**
 * Lists soft-deleted posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractListView<Post, ViewablePostList>
 */
final class DeletedPostListView extends AbstractListView
{
    public function __construct()
    {
        $this->addAvailableSortFields([
            new ListViewSortField('deleteTime', 'forum.post.deleteTime'),
            new ListViewSortField('time', 'wcf.global.date'),
        ]);
        $this->setDefaultSortField('deleteTime');
        $this->setDefaultSortOrder('DESC');
        $this->setItemsPerPage(20);
        $this->setInteractionProvider(new PostInteractions());
        $this->setCssClassName('forumPostListView');
    }

    #[\Override]
    protected function createObjectList(): DatabaseObjectList
    {
        $list = new ViewablePostList();
        $list->sqlConditionJoins = 'INNER JOIN forum1_thread thread ON thread.threadID = post.threadID';
        ThreadVisibility::addConditions(
            $list->getConditionBuilder(),
            BoardPermissionHandler::getInstance()->getBoardIDs(['canReadDeletedPost']) ?: [0]
        );
        $list->getConditionBuilder()->add('post.isDeleted = ?', [1]);

        return $list;
    }

    #[\Override]
    public function renderItems(): string
    {
        return WCF::getTPL()->render('forum', 'postListViewItems', ['view' => $this]);
    }

    #[\Override]
    public function isAccessible(): bool
    {
        return BoardPermissionHandler::getInstance()->getBoardIDs(['canReadDeletedPost']) !== [];
    }
}
