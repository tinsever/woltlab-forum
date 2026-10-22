<?php

namespace forum\event\interaction\user;

use wcf\event\IPsr14Event;
use forum\system\interaction\user\PostInteractions;

/**
 * Indicates that the provider for PostInteractions is collecting interactions.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostInteractionCollecting implements IPsr14Event
{
    public function __construct(public readonly PostInteractions $provider)
    {
    }
}
