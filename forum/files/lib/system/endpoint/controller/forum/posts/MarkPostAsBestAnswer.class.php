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
use forum\data\thread\ThreadAction;

/**
 * Marks the post with the given id as the best answer of its thread.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
#[PostRequest('/forum/posts/{id:\d+}/best-answer')]
final class MarkPostAsBestAnswer implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $post = Helper::fetchObjectFromRequestParameter($variables['id'], Post::class);
        if (!$post->canRead() || !($post->getThread()->canMarkBestAnswer() && !$post->isFirstPost() && !$post->isDeleted && !$post->isDisabled)) {
            throw new PermissionDeniedException();
        }

        (new ThreadAction([$post->getThread()], 'setBestAnswer', ['postID' => $post->postID]))->executeAction();

        return new JsonResponse([]);
    }
}
