<?php

namespace forum\acp\page;

use forum\data\board\BoardNodeTree;
use wcf\page\AbstractPage;
use wcf\system\WCF;

/**
 * Lists the board hierarchy in the ACP.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class BoardListPage extends AbstractPage
{
    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'forum.acp.menu.link.board.list';

    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.forum.canManageBoard'];

    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();

        WCF::getTPL()->assign([
            'boardNodes' => (new BoardNodeTree())->getFlatList(),
        ]);
    }
}
