<?php

namespace forum\system\listView\user;

use forum\data\post\Post;
use forum\data\post\ViewablePostList;
use forum\system\thread\ThreadVisibility;
use wcf\data\DatabaseObjectList;
use wcf\system\listView\AbstractListView;
use wcf\system\listView\ListViewSortField;
use wcf\system\WCF;

/**
 * Lists the visible posts of a user.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractListView<Post, ViewablePostList>
 */
final class UserPostListView extends AbstractListView
{
    public function __construct(public readonly int $userID)
    {
        $this->addAvailableSortFields([
            new ListViewSortField('time', 'wcf.global.date'),
        ]);
        $this->setDefaultSortField('time');
        $this->setDefaultSortOrder('DESC');
        $this->setItemsPerPage(20);
        $this->setCssClassName('forumPostListView');
    }

    #[\Override]
    protected function createObjectList(): DatabaseObjectList
    {
        $list = new ViewablePostList();
        $list->sqlConditionJoins = 'INNER JOIN forum1_thread thread ON thread.threadID = post.threadID';
        ThreadVisibility::addConditions($list->getConditionBuilder(), null, 'thread', true);
        $list->getConditionBuilder()->add('thread.movedThreadID IS NULL');
        $list->getConditionBuilder()->add('thread.isDeleted = ?', [0]);
        $list->getConditionBuilder()->add('thread.isDisabled = ?', [0]);
        $list->getConditionBuilder()->add('post.userID = ?', [$this->userID]);
        $list->getConditionBuilder()->add('post.isDeleted = ?', [0]);
        $list->getConditionBuilder()->add('post.isDisabled = ?', [0]);

        return $list;
    }

    #[\Override]
    public function renderItems(): string
    {
        return WCF::getTPL()->render('forum', 'postListViewItems', ['view' => $this]);
    }

    #[\Override]
    public function getParameters(): array
    {
        return ['userID' => $this->userID];
    }
}
