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
 * Soft-deletes the post with the given id, the first post trashes the thread.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
#[PostRequest('/forum/posts/{id:\d+}/soft-delete')]
final class SoftDeletePost implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $post = Helper::fetchObjectFromRequestParameter($variables['id'], Post::class);
        if (!$post->canRead() || !($post->isFirstPost() ? $post->getThread()->canTrash() : $post->canTrash())) {
            throw new PermissionDeniedException();
        }

        $parameters = Helper::mapApiParameters($request, SoftDeletePostParameters::class);
        if ($post->isFirstPost()) {
            (new ThreadAction([$post->getThread()], 'trash', ['reason' => $parameters->reason ?? '']))->executeAction();
        } else {
            (new PostAction([$post], 'trash', ['reason' => $parameters->reason ?? '']))->executeAction();
        }

        return new JsonResponse([]);
    }
}

/** @internal */
final class SoftDeletePostParameters
{
    public function __construct(
        public readonly ?string $reason = null,
    ) {}
}
