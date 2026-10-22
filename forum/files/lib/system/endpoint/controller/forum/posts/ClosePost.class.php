<?php

namespace forum\system\endpoint\controller\forum\posts;

use forum\data\post\Post;
use forum\data\post\PostAction;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\http\Helper;
use wcf\system\endpoint\PostRequest;
use wcf\system\endpoint\IController;
use wcf\system\exception\PermissionDeniedException;

/**
 * Closes the post with the given id for edits by its author.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
#[PostRequest('/forum/posts/{id:\d+}/close')]
final class ClosePost implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $post = Helper::fetchObjectFromRequestParameter($variables['id'], Post::class);
        if (!$post->canRead() || !($post->canClose() && !$post->isClosed)) {
            throw new PermissionDeniedException();
        }

        (new PostAction([$post], 'close'))->executeAction();

        return new JsonResponse([]);
    }
}
