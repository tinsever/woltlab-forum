<?php

namespace forum\system\edit;

use forum\data\post\HistorySavingPost;
use forum\data\post\Post;
use forum\data\post\PostList;
use wcf\data\object\type\AbstractObjectTypeProvider;
use wcf\system\edit\IHistorySavingObject;
use wcf\system\edit\IHistorySavingObjectTypeProvider;

/**
 * Edit history provider for posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostHistorySavingObjectType extends AbstractObjectTypeProvider implements IHistorySavingObjectTypeProvider
{
    /**
     * @inheritDoc
     */
    public $className = Post::class;

    /**
     * @inheritDoc
     */
    public $decoratorClassName = HistorySavingPost::class;

    /**
     * @inheritDoc
     */
    public $listClassName = PostList::class;

    #[\Override]
    public function checkPermissions(IHistorySavingObject $object)
    {
        \assert($object instanceof HistorySavingPost);

        return $object->postID && $object->getDecoratedObject()->canEdit();
    }

    #[\Override]
    public function getActivePageMenuItem()
    {
        return '';
    }
}
