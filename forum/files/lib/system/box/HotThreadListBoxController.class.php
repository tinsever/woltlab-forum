<?php

namespace forum\system\box;

use forum\system\listView\user\ThreadListView;
use wcf\system\box\AbstractListViewBoxController;
use wcf\system\listView\AbstractListView;
use wcf\system\WCF;

/**
 * Box with the threads that received the most replies among the recently active threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class HotThreadListBoxController extends AbstractListViewBoxController
{
    /**
     * @inheritDoc
     */
    protected static $supportedPositions = [
        'sidebarLeft',
        'sidebarRight',
        'footerBoxes',
    ];

    /**
     * @inheritDoc
     */
    public $defaultLimit = 5;

    #[\Override]
    protected function createListView(): AbstractListView
    {
        $listView = new ThreadListView(0, 'hot');
        $listView->setSortField('replies');
        $listView->setSortOrder('DESC');

        return $listView;
    }

    #[\Override]
    protected function getTemplate()
    {
        return WCF::getTPL()->render('forum', 'boxThreadList', [
            'boxThreadList' => $this->getListView()->getItems(),
            'boxSortField' => 'hot',
        ]);
    }
}
