<?php

namespace forum\system\endpoint\controller\forum\boards;

use forum\data\board\BoardCache;
use forum\system\board\BoardStatusHandler;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\http\Helper;
use wcf\system\endpoint\IController;
use wcf\system\endpoint\PostRequest;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * Changes the subscription status of the active user for a board.
 * `status` is one of `watching`, `ignoring` or empty to reset it.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
#[PostRequest('/forum/boards/{id:\d+}/subscription')]
final class SetBoardSubscription implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        if (!WCF::getUser()->userID) {
            throw new PermissionDeniedException();
        }

        $board = BoardCache::getInstance()->getBoard(\intval($variables['id']));
        if ($board === null || !$board->isBoard()) {
            throw new IllegalLinkException();
        }
        if (!$board->canEnter()) {
            throw new PermissionDeniedException();
        }

        $parameters = Helper::mapApiParameters($request, SetBoardSubscriptionParameters::class);
        $status = match ($parameters->status) {
            'watching' => BoardStatusHandler::STATUS_WATCHING,
            'ignoring' => BoardStatusHandler::STATUS_IGNORING,
            default => null,
        };

        if ($status === BoardStatusHandler::STATUS_IGNORING && !$board->canIgnore()) {
            throw new PermissionDeniedException();
        }

        BoardStatusHandler::saveStatus($board->boardID, $status, $parameters->enableNotification);

        return new JsonResponse([
            'status' => $status ?? '',
        ]);
    }
}

/** @internal */
final class SetBoardSubscriptionParameters
{
    public function __construct(
        public readonly string $status = '',
        public readonly bool $enableNotification = true,
    ) {}
}
