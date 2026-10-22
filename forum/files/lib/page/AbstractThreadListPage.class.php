<?php

namespace forum\page;

use forum\system\listView\user\ThreadListView;
use wcf\page\AbstractListViewPage;
use wcf\system\listView\AbstractListView;
use wcf\system\page\PageLocationManager;
use wcf\system\WCF;

/**
 * Base class for pages that show a filtered list of threads across all boards.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractListViewPage<ThreadListView>
 */
abstract class AbstractThreadListPage extends AbstractListViewPage
{
    /**
     * @inheritDoc
     */
    public $templateName = 'threadList';

    /**
     * @inheritDoc
     */
    public $templateNameApplication = 'forum';

    /**
     * filter of the thread list view
     */
    protected string $filter = '';

    #[\Override]
    protected function createListView(): AbstractListView
    {
        return new ThreadListView(0, $this->filter);
    }

    #[\Override]
    public function readData()
    {
        parent::readData();

        PageLocationManager::getInstance()->addParentLocation('com.tinsever.forum.BoardList');
    }

    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();

        WCF::getTPL()->assign([
            'threadListFilter' => $this->filter,
        ]);
    }
}
