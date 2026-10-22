<?php

namespace forum\system\user\content\provider;

use forum\data\post\Post;
use wcf\system\user\content\provider\AbstractDatabaseUserContentProvider;

/**
 * User content provider for posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostUserContentProvider extends AbstractDatabaseUserContentProvider
{
    #[\Override]
    public static function getDatabaseObjectClass()
    {
        return Post::class;
    }
}
