<?php

namespace forum\event\interaction\bulk\user;

use wcf\event\IPsr14Event;
use forum\system\interaction\bulk\user\PostBulkInteractions;

/**
 * Indicates that the provider for PostBulkInteractions is collecting interactions.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostBulkInteractionCollecting implements IPsr14Event
{
    public function __construct(public readonly PostBulkInteractions $provider)
    {
    }
}
