<?php

namespace forum\data\post;

use wcf\data\like\ILikeObjectTypeProvider;
use wcf\data\like\object\ILikeObject;
use wcf\data\object\type\AbstractObjectTypeProvider;
use wcf\system\like\IViewableLikeProvider;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Object type provider for likeable posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class LikeablePostProvider extends AbstractObjectTypeProvider implements ILikeObjectTypeProvider, IViewableLikeProvider
{
    /**
     * @inheritDoc
     */
    public $className = Post::class;

    /**
     * @inheritDoc
     */
    public $decoratorClassName = LikeablePost::class;

    /**
     * @inheritDoc
     */
    public $listClassName = PostList::class;

    #[\Override]
    public function checkPermissions(ILikeObject $object)
    {
        /** @var LikeablePost $object */
        return $object->postID && $object->getDecoratedObject()->canRead();
    }

    #[\Override]
    public function prepare(array $likes)
    {
        $postIDs = [];
        foreach ($likes as $like) {
            $postIDs[] = $like->objectID;
        }

        $postList = new PostList();
        $postList->setObjectIDs(\array_unique($postIDs));
        $postList->readObjects();
        $posts = $postList->getObjects();

        foreach ($likes as $like) {
            if (!isset($posts[$like->objectID])) {
                continue;
            }

            $post = $posts[$like->objectID];
            if (!$post->canRead()) {
                continue;
            }
            $like->setIsAccessible();

            $like->setTitle(WCF::getLanguage()->getDynamicVariable('wcf.like.title.com.tinsever.forum.likeablePost', [
                'post' => $post,
                'thread' => $post->getThread(),
                'reaction' => $like,
            ]));
            $like->setDescription(StringUtil::encodeHTML($post->getTeaser()));
        }
    }
}
