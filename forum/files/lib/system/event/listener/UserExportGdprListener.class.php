<?php

namespace forum\system\event\listener;

use wcf\system\event\listener\IParameterizedEventListener;

/**
 * Adds the forum data to the GDPR export of users.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class UserExportGdprListener implements IParameterizedEventListener
{
    #[\Override]
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        $eventObj->ipAddresses['com.tinsever.forum'] = ['forum1_post'];
        $eventObj->exportUserProperties[] = 'forumPosts';
    }
}
