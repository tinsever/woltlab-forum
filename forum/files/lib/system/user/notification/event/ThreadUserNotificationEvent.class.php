<?php

namespace forum\system\user\notification\event;

/**
 * Notification event 'thread' for posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class ThreadUserNotificationEvent extends AbstractPostUserNotificationEvent
{
    #[\Override]
    protected function getEventKey(): string
    {
        return 'thread';
    }
}
