<?php

namespace forum\system\endpoint\controller\forum\boards;

use forum\data\board\BoardCache;
use forum\data\thread\ThreadAction;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\system\endpoint\IController;
use wcf\system\endpoint\PostRequest;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * Marks all boards as read.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
#[PostRequest('/forum/boards/mark-as-read')]
final class MarkAllBoardsAsRead implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        if (!WCF::getUser()->userID) {
            throw new PermissionDeniedException();
        }

        $board = null;
        if (isset($variables['id'])) {
            $board = BoardCache::getInstance()->getBoard(\intval($variables['id']));
            if ($board === null) {
                throw new IllegalLinkException();
            }
            if (!$board->canEnter()) {
                throw new PermissionDeniedException();
            }
        }

        ThreadAction::markAllAsRead($board);

        return new JsonResponse([]);
    }
}
