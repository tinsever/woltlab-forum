<?php

namespace wcf\system\endpoint\controller\accountSwitcher;

use wcf\system\user\LinkedAccountHandler;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\data\user\User;
use wcf\http\Helper;
use wcf\system\application\ApplicationHandler;
use wcf\system\endpoint\IController;
use wcf\system\endpoint\PostRequest;
use wcf\system\exception\NamedUserException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;
use wcf\util\HtmlString;

/**
 * Logs the active user into one of their linked accounts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
#[PostRequest('/account-switcher/accounts/{id:\d+}/switch')]
final class SwitchAccount implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $handler = LinkedAccountHandler::getInstance();
        if (!$handler->isAvailable()) {
            throw new PermissionDeniedException();
        }

        $parameters = Helper::mapApiParameters($request, SwitchAccountParameters::class);

        $target = new User(\intval($variables['id']));
        $error = $handler->getSwitchError($target);
        if ($error !== null) {
            throw new NamedUserException(HtmlString::fromSafeHtml(
                WCF::getLanguage()->getDynamicVariable('wcf.accountSwitcher.switch.error.' . $error)
            ));
        }

        // Only ever return to pages of this community.
        $returnUrl = $parameters->returnUrl;
        if ($returnUrl !== '' && !ApplicationHandler::getInstance()->isInternalURL($returnUrl)) {
            $returnUrl = '';
        }

        return new JsonResponse([
            'redirectUrl' => $handler->switchTo($target, $returnUrl),
        ]);
    }
}

/** @internal */
final class SwitchAccountParameters
{
    public function __construct(
        public readonly string $returnUrl = '',
    ) {}
}
