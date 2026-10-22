<?php

namespace forum\system\page\handler;

use forum\system\board\BoardListBuilder;
use wcf\system\page\handler\AbstractMenuPageHandler;

/**
 * Page handler for the board list, shows the number of unread threads in the menu.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class BoardListPageHandler extends AbstractMenuPageHandler
{
    #[\Override]
    public function getOutstandingItemCount($objectID = null)
    {
        return \FORUM_MENU_SHOW_UNREAD_COUNT ? BoardListBuilder::getUnreadThreadCount() : 0;
    }
}
