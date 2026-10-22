<?php

namespace forum\acp\form;

use forum\data\board\Board;
use wcf\system\exception\IllegalLinkException;
use wcf\system\WCF;

/**
 * Shows the form to edit a board.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class BoardEditForm extends BoardAddForm
{
    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'forum.acp.menu.link.board.list';

    /**
     * @inheritDoc
     */
    public $formAction = 'edit';

    #[\Override]
    public function readParameters()
    {
        parent::readParameters();

        $this->formObject = new Board(\intval($_REQUEST['id'] ?? 0));
        if (!$this->formObject->boardID) {
            throw new IllegalLinkException();
        }
    }

    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();

        WCF::getTPL()->assign('board', $this->formObject);
    }
}
