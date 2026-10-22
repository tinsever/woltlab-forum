<?php

namespace forum\system\endpoint\controller\forum\threads;

use forum\data\thread\Thread;
use forum\data\thread\ThreadAction;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\http\Helper;
use wcf\system\endpoint\PostRequest;
use wcf\system\endpoint\IController;
use wcf\system\exception\PermissionDeniedException;

/**
 * Closes the thread with the given id.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
#[PostRequest('/forum/threads/{id:\d+}/close')]
final class CloseThread implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $thread = Helper::fetchObjectFromRequestParameter($variables['id'], Thread::class);
        if (!$thread->canRead() || !($thread->canClose() && !$thread->isClosed)) {
            throw new PermissionDeniedException();
        }

        (new ThreadAction([$thread], 'close'))->executeAction();

        return new JsonResponse([]);
    }
}
