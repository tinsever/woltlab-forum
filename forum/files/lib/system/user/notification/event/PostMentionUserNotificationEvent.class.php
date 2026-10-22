<?php

namespace forum\system\user\notification\event;

/**
 * Notification event 'mention' for posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class PostMentionUserNotificationEvent extends AbstractPostUserNotificationEvent
{
    #[\Override]
    protected function getEventKey(): string
    {
        return 'mention';
    }
}
