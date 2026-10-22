<?php

namespace forum\system\endpoint\controller\forum\boards;

use forum\data\board\BoardCache;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\http\Helper;
use wcf\system\endpoint\IController;
use wcf\system\endpoint\PostRequest;
use wcf\system\exception\IllegalLinkException;
use wcf\system\user\collapsible\content\UserCollapsibleContentHandler;

/**
 * Remembers whether a board category is collapsed in the board list.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
#[PostRequest('/forum/boards/{id:\d+}/collapsed')]
final class SetBoardCategoryCollapsed implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $board = BoardCache::getInstance()->getBoard(\intval($variables['id']));
        if ($board === null || !$board->isVisible()) {
            throw new IllegalLinkException();
        }

        $parameters = Helper::mapApiParameters($request, SetBoardCategoryCollapsedParameters::class);

        $handler = UserCollapsibleContentHandler::getInstance();
        $objectTypeID = $handler->getObjectTypeID('com.tinsever.forum.boardCategory');
        if ($parameters->collapsed) {
            $handler->markAsCollapsed($objectTypeID, (string)$board->boardID);
        } else {
            $handler->markAsOpened($objectTypeID, (string)$board->boardID);
        }

        return new JsonResponse([]);
    }
}

/** @internal */
final class SetBoardCategoryCollapsedParameters
{
    public function __construct(
        public readonly bool $collapsed,
    ) {}
}
