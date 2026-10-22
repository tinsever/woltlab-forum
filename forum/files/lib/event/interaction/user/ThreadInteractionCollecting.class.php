<?php

namespace forum\event\interaction\user;

use wcf\event\IPsr14Event;
use forum\system\interaction\user\ThreadInteractions;

/**
 * Indicates that the provider for ThreadInteractions is collecting interactions.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ThreadInteractionCollecting implements IPsr14Event
{
    public function __construct(public readonly ThreadInteractions $provider)
    {
    }
}
