<?php

namespace forum\system\box;

use forum\data\board\Board;
use forum\data\board\BoardNodeTree;
use forum\page\BoardPage;
use forum\page\ThreadPage;
use wcf\system\box\AbstractBoxController;
use wcf\system\request\RequestHandler;
use wcf\system\WCF;

/**
 * Box with the board hierarchy, highlighting the active board.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class BoardListBoxController extends AbstractBoxController
{
    /**
     * @inheritDoc
     */
    protected static $supportedPositions = ['sidebarLeft', 'sidebarRight'];

    #[\Override]
    protected function loadContent()
    {
        $tree = new BoardNodeTree(0, static fn(Board $board) => $board->isVisible());
        $nodes = $tree->getFlatList();
        if ($nodes === []) {
            return;
        }

        $activeBoardID = 0;
        $controller = RequestHandler::getInstance()->getActiveRequest()?->getRequestObject();
        if ($controller instanceof BoardPage) {
            $activeBoardID = $controller->boardID;
        } elseif ($controller instanceof ThreadPage && isset($controller->thread)) {
            $activeBoardID = $controller->thread->boardID;
        }

        $this->content = WCF::getTPL()->render('forum', 'boxBoardList', [
            'boxBoardNodes' => $nodes,
            'boxActiveBoardID' => $activeBoardID,
        ]);
    }
}
