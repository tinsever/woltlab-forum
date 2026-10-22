<?php

namespace forum\system\page\handler;

use wcf\system\page\handler\AbstractMenuPageHandler;
use wcf\system\WCF;

/**
 * Page handler for thread lists that are only available to registered users.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class LoggedInThreadListPageHandler extends AbstractMenuPageHandler
{
    #[\Override]
    public function isVisible($objectID = null)
    {
        return WCF::getUser()->userID !== 0;
    }
}
