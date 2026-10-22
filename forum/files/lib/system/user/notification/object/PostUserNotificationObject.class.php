<?php

namespace forum\system\user\notification\object;

use forum\data\post\Post;
use wcf\data\DatabaseObjectDecorator;
use wcf\system\user\notification\object\IUserNotificationObject;

/**
 * Notification object for posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @mixin Post
 * @extends DatabaseObjectDecorator<Post>
 */
class PostUserNotificationObject extends DatabaseObjectDecorator implements IUserNotificationObject
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = Post::class;

    #[\Override]
    public function getTitle(): string
    {
        return $this->getThread()->topic;
    }

    #[\Override]
    public function getURL(): string
    {
        return $this->getLink();
    }

    #[\Override]
    public function getAuthorID()
    {
        return $this->userID;
    }
}
