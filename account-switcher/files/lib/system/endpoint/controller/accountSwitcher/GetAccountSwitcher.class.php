<?php

namespace wcf\system\endpoint\controller\accountSwitcher;

use wcf\system\user\LinkedAccountHandler;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\system\endpoint\GetRequest;
use wcf\system\endpoint\IController;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * Returns the content of the account switcher menu.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
#[GetRequest('/account-switcher/accounts')]
final class GetAccountSwitcher implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $handler = LinkedAccountHandler::getInstance();
        if (!$handler->isAvailable()) {
            throw new PermissionDeniedException();
        }

        $user = WCF::getUser();
        $accounts = $handler->getLinkedAccounts($user->userID);
        $unreadCounts = $handler->getUnreadCounts(\array_map(
            static fn($account) => $account->userID,
            $accounts
        ));

        $unreadCount = 0;
        foreach ($unreadCounts as $count) {
            $unreadCount += $count['notifications'] + $count['conversations'];
        }

        return new JsonResponse([
            'template' => WCF::getTPL()->render('wcf', 'accountSwitcherMenu', [
                'accounts' => $accounts,
                'unreadCounts' => $unreadCounts,
            ]),
            'unreadCount' => $unreadCount,
        ]);
    }
}
