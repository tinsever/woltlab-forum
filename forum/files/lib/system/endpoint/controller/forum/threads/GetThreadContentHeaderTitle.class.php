<?php

namespace forum\system\endpoint\controller\forum\threads;

use forum\data\thread\Thread;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\http\Helper;
use wcf\system\endpoint\GetRequest;
use wcf\system\endpoint\IController;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * Returns the content header title of the thread page.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
#[GetRequest('/forum/threads/{id:\d+}/content-header-title')]
final class GetThreadContentHeaderTitle implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $thread = Helper::fetchObjectFromRequestParameter($variables['id'], Thread::class);
        if (!$thread->canRead()) {
            throw new PermissionDeniedException();
        }

        return new JsonResponse([
            'template' => WCF::getTPL()->render('forum', 'threadContentHeaderTitle', [
                'thread' => $thread,
            ]),
        ]);
    }
}
