<?php

namespace forum\page;

use forum\system\board\BoardBreadcrumbs;
use forum\data\board\Board;
use forum\data\board\BoardCache;
use forum\data\board\BoardEditor;
use forum\data\board\BoardNode;
use forum\data\thread\ViewableThreadList;
use forum\system\board\BoardListBuilder;
use forum\system\listView\user\ThreadListView;
use forum\system\thread\ThreadVisibility;
use wcf\page\AbstractListViewPage;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\listView\AbstractListView;
use wcf\system\page\PageLocationManager;
use wcf\system\request\LinkHandler;
use wcf\system\style\StyleHandler;
use wcf\system\WCF;
use wcf\util\HeaderUtil;

/**
 * Shows a board with its sub boards and threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractListViewPage<ThreadListView>
 */
class BoardPage extends AbstractListViewPage
{
    public int $boardID = 0;

    public Board $board;

    /**
     * @var BoardNode[]
     */
    public array $boardNodes = [];

    public ?ViewableThreadList $announcementList = null;

    #[\Override]
    public function readParameters()
    {
        parent::readParameters();

        if (isset($_REQUEST['id'])) {
            $this->boardID = \intval($_REQUEST['id']);
        }

        $board = BoardCache::getInstance()->getBoard($this->boardID);
        if ($board === null) {
            throw new IllegalLinkException();
        }
        $this->board = $board;

        if (!$this->board->isVisible() || !$this->board->canEnter()) {
            throw new PermissionDeniedException();
        }

        if ($this->board->isExternalLink()) {
            (new BoardEditor($this->board))->updateCounters(['clicks' => 1]);
            HeaderUtil::redirect($this->board->externalURL);

            exit;
        }

        if ($this->board->styleID) {
            StyleHandler::getInstance()->changeStyle($this->board->styleID, true);
        }
    }

    #[\Override]
    public function readData()
    {
        $this->boardNodes = (new BoardListBuilder($this->boardID))->getNodes();

        if ($this->board->isBoard() && $this->board->canReadThreads()) {
            parent::readData();

            if ($this->listView->getPageNo() === 1) {
                $this->readAnnouncements();
            }
        } else {
            \wcf\page\AbstractPage::readData();
        }

        BoardBreadcrumbs::addBoard($this->board, false);
    }

    private function readAnnouncements(): void
    {
        $this->announcementList = new ViewableThreadList(null, false, true, false);
        ThreadVisibility::addConditions($this->announcementList->getConditionBuilder());
        $this->announcementList->getConditionBuilder()->add(
            "thread.threadID IN (
                SELECT  threadID
                FROM    forum1_thread_announcement
                WHERE   boardID = ?
            )",
            [$this->boardID]
        );
        $this->announcementList->getConditionBuilder()->add('thread.isAnnouncement = ?', [1]);
        $this->announcementList->sqlOrderBy = 'thread.lastPostTime DESC';
        $this->announcementList->readObjects();
    }

    #[\Override]
    protected function createListView(): AbstractListView
    {
        return new ThreadListView($this->boardID);
    }

    #[\Override]
    protected function getBaseUrlParameters(): array
    {
        return ['object' => $this->board];
    }

    #[\Override]
    protected function getCanonicalUrl(): string
    {
        return LinkHandler::getInstance()->getControllerLink(static::class, [
            'object' => $this->board,
            'pageNo' => $this->pageNo > 1 ? $this->pageNo : null,
        ]);
    }

    #[\Override]
    public function assignVariables()
    {
        if (isset($this->listView)) {
            parent::assignVariables();
        } else {
            \wcf\page\AbstractPage::assignVariables();
        }

        WCF::getTPL()->assign([
            'board' => $this->board,
            'boardID' => $this->boardID,
            'boardNodes' => $this->boardNodes,
            'announcementList' => $this->announcementList,
            'hasThreadList' => isset($this->listView),
        ]);
    }
}
