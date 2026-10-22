<?php

namespace forum\system\user\notification\object\type;

use forum\data\post\Post;
use forum\data\post\PostList;
use forum\system\user\notification\object\PostUserNotificationObject;
use wcf\system\user\notification\object\type\AbstractUserNotificationObjectType;

/**
 * Notification object type for posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class PostNotificationObjectType extends AbstractUserNotificationObjectType
{
    /**
     * @inheritDoc
     */
    protected static $decoratorClassName = PostUserNotificationObject::class;

    /**
     * @inheritDoc
     */
    protected static $objectClassName = Post::class;

    /**
     * @inheritDoc
     */
    protected static $objectListClassName = PostList::class;
}
