<?php

namespace forum\system\message\quote;

use forum\data\post\Post;
use wcf\data\IMessage;
use wcf\system\message\quote\AbstractMessageQuoteHandler;

/**
 * Quote handler for posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostMessageQuoteHandler extends AbstractMessageQuoteHandler
{
    #[\Override]
    public function getMessage(int $objectID): ?IMessage
    {
        $post = new Post($objectID);
        if (!$post->postID || !$post->canRead()) {
            return null;
        }

        if ($post->hasEmbeddedObjects) {
            $post->getCollection()->loadEmbeddedObjects();
        }

        return $post;
    }
}
