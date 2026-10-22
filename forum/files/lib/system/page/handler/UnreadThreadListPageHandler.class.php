<?php

namespace forum\system\page\handler;

use forum\system\board\BoardListBuilder;
use wcf\system\page\handler\AbstractMenuPageHandler;
use wcf\system\WCF;

/**
 * Page handler for the list of unread threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class UnreadThreadListPageHandler extends AbstractMenuPageHandler
{
    #[\Override]
    public function getOutstandingItemCount($objectID = null)
    {
        return BoardListBuilder::getUnreadThreadCount();
    }

    #[\Override]
    public function isVisible($objectID = null)
    {
        return WCF::getUser()->userID !== 0;
    }
}
