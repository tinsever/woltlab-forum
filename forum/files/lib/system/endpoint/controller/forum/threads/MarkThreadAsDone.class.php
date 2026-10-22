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
 * Marks the thread with the given id as done.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
#[PostRequest('/forum/threads/{id:\d+}/mark-as-done')]
final class MarkThreadAsDone implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $thread = Helper::fetchObjectFromRequestParameter($variables['id'], Thread::class);
        if (!$thread->canRead() || !($thread->canMarkAsDone() && !$thread->isDone)) {
            throw new PermissionDeniedException();
        }

        (new ThreadAction([$thread], 'markAsDone'))->executeAction();

        return new JsonResponse([]);
    }
}
