<?php

namespace forum\data\post;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends DatabaseObjectList<Post>
 */
class PostList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = Post::class;
}
