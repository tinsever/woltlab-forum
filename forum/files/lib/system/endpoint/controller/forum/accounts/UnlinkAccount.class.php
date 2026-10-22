<?php

namespace forum\system\endpoint\controller\forum\accounts;

use forum\system\account\AccountSwitcherHandler;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\system\endpoint\IController;
use wcf\system\endpoint\PostRequest;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * Removes an account from the group of linked accounts of the active user.
 * Removing is possible without the permission to use the switcher, so that
 * users can always clean up their links.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
#[PostRequest('/forum/account-switcher/{id:\d+}/unlink')]
final class UnlinkAccount implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $userID = WCF::getUser()->userID;
        if (!MODULE_FORUM_ACCOUNT_SWITCHER || !$userID) {
            throw new PermissionDeniedException();
        }

        $handler = AccountSwitcherHandler::getInstance();
        $targetID = \intval($variables['id']);
        if ($targetID !== $userID && !$handler->isLinked($userID, $targetID)) {
            throw new IllegalLinkException();
        }

        $handler->unlink($targetID);

        return new JsonResponse([]);
    }
}
