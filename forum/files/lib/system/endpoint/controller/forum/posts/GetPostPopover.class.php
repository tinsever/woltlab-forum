<?php

namespace forum\system\endpoint\controller\forum\posts;

use forum\data\post\Post;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\http\Helper;
use wcf\system\endpoint\GetRequest;
use wcf\system\endpoint\IController;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * Returns the popover of the post with the given id.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
#[GetRequest('/forum/posts/{id:\d+}/popover')]
final class GetPostPopover implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $post = Helper::fetchObjectFromRequestParameter($variables['id'], Post::class);
        if (!$post->canRead()) {
            throw new PermissionDeniedException();
        }

        return new JsonResponse([
            'template' => WCF::getTPL()->render('forum', 'postPopover', [
                'post' => $post,
                'thread' => $post->getThread(),
            ]),
        ]);
    }
}
