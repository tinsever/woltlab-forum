<?php

namespace forum\system\endpoint\controller\forum\posts;

use forum\data\post\Post;
use forum\data\post\PostAction;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\http\Helper;
use wcf\system\endpoint\DeleteRequest;
use wcf\system\endpoint\IController;
use wcf\system\exception\PermissionDeniedException;
use forum\data\thread\ThreadAction;

/**
 * Permanently deletes the post with the given id, deleting the first post removes the thread.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
#[DeleteRequest('/forum/posts/{id:\d+}')]
final class DeletePost implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $post = Helper::fetchObjectFromRequestParameter($variables['id'], Post::class);
        if (!$post->canRead() || !($post->isFirstPost() ? $post->getThread()->canDelete() : $post->canDelete())) {
            throw new PermissionDeniedException();
        }

        if ($post->isFirstPost()) {
            (new ThreadAction([$post->getThread()], 'delete'))->executeAction();
        } else {
            (new PostAction([$post], 'delete'))->executeAction();
        }

        return new JsonResponse([]);
    }
}
