<?php

namespace forum\system\box;

use forum\system\listView\user\ThreadListView;
use wcf\system\box\AbstractListViewBoxController;
use wcf\system\listView\AbstractListView;
use wcf\system\WCF;

/**
 * Box with the latest, most active or most viewed threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ThreadListBoxController extends AbstractListViewBoxController
{
    /**
     * @inheritDoc
     */
    protected static $supportedPositions = [
        'sidebarLeft',
        'sidebarRight',
        'contentTop',
        'contentBottom',
        'top',
        'bottom',
        'footerBoxes',
    ];

    /**
     * @inheritDoc
     */
    protected $sortFieldLanguageItemPrefix = 'forum.acp.board.sortField';

    /**
     * @inheritDoc
     */
    public $defaultLimit = 5;

    /**
     * @inheritDoc
     */
    public $validSortFields = [
        'lastPostTime',
        'time',
        'replies',
        'views',
    ];

    #[\Override]
    protected function createListView(): AbstractListView
    {
        return new ThreadListView(0, 'latest');
    }

    #[\Override]
    protected function getTemplate()
    {
        return match ($this->box->position) {
            'contentTop', 'contentBottom', 'top', 'bottom' => parent::getTemplate(),
            default => WCF::getTPL()->render('forum', 'boxThreadList', [
                'boxThreadList' => $this->getListView()->getItems(),
                'boxSortField' => $this->sortField,
            ]),
        };
    }
}
