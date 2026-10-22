<?php

namespace forum\system\listView\user;

use forum\data\board\BoardCache;
use forum\data\thread\Thread;
use forum\data\thread\ViewableThreadList;
use wcf\data\DatabaseObjectList;
use wcf\system\listView\AbstractListView;
use wcf\system\listView\ListViewSortField;
use wcf\system\tagging\TagEngine;
use wcf\system\WCF;

/**
 * List of threads with the given tags.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractListView<Thread, ViewableThreadList>
 */
final class TaggedThreadListView extends AbstractListView
{
    /**
     * @param int[] $tagIDs
     */
    public function __construct(public readonly array $tagIDs)
    {
        $this->addAvailableSortFields([
            new ListViewSortField('lastPostTime', 'forum.thread.lastPostTime'),
            new ListViewSortField('time', 'forum.thread.time'),
            new ListViewSortField('topic', 'forum.thread.topic'),
        ]);
        $this->setDefaultSortField('lastPostTime');
        $this->setDefaultSortOrder('DESC');
        $this->setItemsPerPage(\FORUM_THREADS_PER_PAGE);
        $this->setCssClassName('forumThreadList');
    }

    #[\Override]
    protected function createObjectList(): DatabaseObjectList
    {
        $list = new ViewableThreadList(null, true);
        $list->getConditionBuilder()->add('thread.movedThreadID IS NULL');

        $subselect = TagEngine::getInstance()->getSubselectForObjectsByTagIDs('com.tinsever.forum.thread', $this->tagIDs);
        $list->getConditionBuilder()->add("thread.threadID IN ({$subselect['sql']})", $subselect['parameters']);

        return $list;
    }

    #[\Override]
    public function renderItems(): string
    {
        return WCF::getTPL()->render('forum', 'threadListItems', [
            'view' => $this,
            'threads' => $this->getItems(),
            'showBoard' => true,
        ]);
    }

    #[\Override]
    public function isAccessible(): bool
    {
        return \MODULE_TAGGING && WCF::getSession()->getPermission('user.tag.canViewTag');
    }

    #[\Override]
    public function getParameters(): array
    {
        return ['tagIDs' => $this->tagIDs];
    }
}
