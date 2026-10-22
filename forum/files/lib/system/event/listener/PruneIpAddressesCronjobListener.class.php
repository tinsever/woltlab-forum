<?php

namespace forum\system\event\listener;

use wcf\system\event\listener\IParameterizedEventListener;

/**
 * Removes old ip addresses of posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PruneIpAddressesCronjobListener implements IParameterizedEventListener
{
    #[\Override]
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        $eventObj->columns['forum1_post']['ipAddress'] = 'time';
    }
}
