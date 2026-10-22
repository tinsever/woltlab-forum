<?php

namespace forum\system\user\notification\event;

/**
 * Notification event 'quote' for posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class PostQuoteUserNotificationEvent extends AbstractPostUserNotificationEvent
{
    #[\Override]
    protected function getEventKey(): string
    {
        return 'quote';
    }
}
