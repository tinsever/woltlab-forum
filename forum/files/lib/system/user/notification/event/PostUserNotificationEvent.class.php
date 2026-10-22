<?php

namespace forum\system\user\notification\event;

/**
 * Notification event 'post' for posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class PostUserNotificationEvent extends AbstractPostUserNotificationEvent
{
    /**
     * @inheritDoc
     */
    protected $stackable = true;

    #[\Override]
    public function getEventHash()
    {
        return \sha1($this->eventID . '-' . $this->getUserNotificationObject()->threadID);
    }

    #[\Override]
    protected function getEventKey(): string
    {
        return 'post';
    }
}
