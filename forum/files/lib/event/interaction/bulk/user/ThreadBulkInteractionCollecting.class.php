<?php

namespace forum\event\interaction\bulk\user;

use wcf\event\IPsr14Event;
use forum\system\interaction\bulk\user\ThreadBulkInteractions;

/**
 * Indicates that the provider for ThreadBulkInteractions is collecting interactions.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ThreadBulkInteractionCollecting implements IPsr14Event
{
    public function __construct(public readonly ThreadBulkInteractions $provider)
    {
    }
}
