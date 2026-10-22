<?php

namespace forum\system\endpoint\controller\forum\threads;

use forum\data\thread\Thread;
use forum\data\thread\ThreadAction;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\http\Helper;
use wcf\system\endpoint\DeleteRequest;
use wcf\system\endpoint\IController;
use wcf\system\exception\PermissionDeniedException;

/**
 * Permanently deletes the thread with the given id.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
#[DeleteRequest('/forum/threads/{id:\d+}')]
final class DeleteThread implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $thread = Helper::fetchObjectFromRequestParameter($variables['id'], Thread::class);
        if (!$thread->canRead() || !($thread->canDelete())) {
            throw new PermissionDeniedException();
        }

        (new ThreadAction([$thread], 'delete'))->executeAction();

        return new JsonResponse([]);
    }
}
