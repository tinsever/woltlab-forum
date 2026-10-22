<?php

namespace forum\system\cache\runtime;

use forum\data\post\Post;
use forum\data\post\PostList;
use wcf\system\cache\runtime\AbstractRuntimeCache;

/**
 * Runtime cache for posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractRuntimeCache<Post, PostList>
 */
class PostRuntimeCache extends AbstractRuntimeCache
{
    /**
     * @inheritDoc
     */
    protected $listClassName = PostList::class;
}
