<?php

namespace forum\action;

use forum\system\account\AccountSwitcherHandler;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use wcf\data\user\authentication\failure\UserAuthenticationFailure;
use wcf\data\user\authentication\failure\UserAuthenticationFailureAction;
use wcf\data\user\User;
use wcf\system\exception\NamedUserException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\form\builder\field\PasswordFormField;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\form\builder\field\validation\FormFieldValidationError;
use wcf\system\form\builder\field\validation\FormFieldValidator;
use wcf\system\form\builder\Psr15DialogForm;
use wcf\system\user\authentication\DefaultUserAuthentication;
use wcf\system\user\authentication\EmailUserAuthentication;
use wcf\system\user\authentication\UserAuthenticationFactory;
use wcf\system\WCF;
use wcf\util\HtmlString;
use wcf\util\UserUtil;

/**
 * Links another account with the active user after asking for its credentials.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class AccountSwitcherLinkDialogAction implements RequestHandlerInterface
{
    private ?User $target = null;

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $handler = AccountSwitcherHandler::getInstance();
        if (!$handler->isAvailable()) {
            throw new PermissionDeniedException();
        }

        // A hijacked session must not be able to link an account of the attacker.
        if (WCF::getSession()->needsReauthentication()) {
            throw new PermissionDeniedException();
        }

        if ($this->isBlocked()) {
            throw new NamedUserException(HtmlString::fromSafeHtml(
                WCF::getLanguage()->getDynamicVariable('wcf.user.login.blocked')
            ));
        }

        $form = $this->getForm();
        if ($request->getMethod() === 'GET') {
            return $form->toResponse();
        }

        $response = $form->validateRequest($request);
        if ($response !== null) {
            return $response;
        }

        \assert($this->target !== null);

        if ($this->target->multifactorActive) {
            return new JsonResponse([
                'result' => [
                    'redirectUrl' => $handler->startMultifactorAuthentication($this->target, true, ''),
                ],
            ]);
        }

        $handler->link(WCF::getUser(), $this->target);

        return new JsonResponse([
            'result' => [
                'message' => WCF::getLanguage()->getDynamicVariable('forum.accountSwitcher.link.success', [
                    'target' => $this->target,
                ]),
            ],
        ]);
    }

    private function getForm(): Psr15DialogForm
    {
        $form = new Psr15DialogForm(
            'forumAccountSwitcherLink',
            WCF::getLanguage()->get('forum.accountSwitcher.link')
        );
        $form->appendChildren([
            TextFormField::create('username')
                ->label('wcf.user.usernameOrEmail')
                ->description('forum.accountSwitcher.link.username.description')
                ->required()
                ->autoFocus()
                ->autocomplete('off')
                ->maximumLength(255),
            PasswordFormField::create('password')
                ->label('wcf.user.password')
                ->required()
                ->passwordStrengthMeter(false)
                ->removeFieldClass('medium')
                ->addFieldClass('long')
                ->autocomplete('current-password')
                ->addValidator(new FormFieldValidator('credentials', function (PasswordFormField $field) {
                    $usernameField = $field->getDocument()->getNodeById('username');
                    \assert($usernameField instanceof TextFormField);

                    $this->validateCredentials($usernameField, $field);
                })),
        ]);
        $form->markRequiredFields(false);
        $form->build();

        return $form;
    }

    private function validateCredentials(TextFormField $usernameField, PasswordFormField $passwordField): void
    {
        $username = (string)$usernameField->getValue();
        $password = (string)$passwordField->getValue();
        if ($username === '' || $password === '') {
            return;
        }

        // Do not even try the password of accounts that are under attack.
        $known = $this->findUser($username);
        if (
            $known !== null
            && ENABLE_USER_AUTHENTICATION_FAILURE
            && USER_AUTHENTICATION_FAILURE_USER_CAPTCHA
            && UserAuthenticationFailure::countUserFailures($known->userID) >= USER_AUTHENTICATION_FAILURE_USER_CAPTCHA
        ) {
            $passwordField->addValidationError(new FormFieldValidationError(
                'tooManyFailures',
                'forum.accountSwitcher.link.error.tooManyFailures'
            ));

            return;
        }

        try {
            $user = $this->authenticate($username, $password);
        } catch (UserInputException $e) {
            $this->saveAuthenticationFailure($e->getField(), $username);

            // Do not reveal whether the account exists.
            $passwordField->addValidationError(new FormFieldValidationError(
                'invalidCredentials',
                'forum.accountSwitcher.link.error.invalidCredentials'
            ));

            return;
        }

        $error = AccountSwitcherHandler::getInstance()->getLinkError(WCF::getUser(), $user);
        if ($error !== null) {
            $usernameField->addValidationError(new FormFieldValidationError(
                $error,
                'forum.accountSwitcher.link.error.' . $error,
                ['limit' => FORUM_ACCOUNT_SWITCHER_MAX_ACCOUNTS]
            ));

            return;
        }

        $this->target = $user;
    }

    /**
     * Checks the credentials like the login form does, including the login
     * by email address.
     *
     * @throws UserInputException
     */
    private function authenticate(string $username, #[\SensitiveParameter] string $password): User
    {
        $authentication = UserAuthenticationFactory::getInstance()->getUserAuthentication();
        try {
            return $authentication->loginManually($username, $password);
        } catch (UserInputException $e) {
            if ($e->getField() !== 'username' || \get_class($authentication) !== DefaultUserAuthentication::class) {
                throw $e;
            }

            try {
                return EmailUserAuthentication::getInstance()->loginManually($username, $password);
            } catch (UserInputException $emailException) {
                throw $emailException->getField() === 'username' ? $e : $emailException;
            }
        }
    }

    private function findUser(string $username): ?User
    {
        $user = User::getUserByUsername($username);
        if (!$user->userID) {
            $user = User::getUserByEmail($username);
        }

        return $user->userID ? $user : null;
    }

    private function isBlocked(): bool
    {
        if (!ENABLE_USER_AUTHENTICATION_FAILURE) {
            return false;
        }

        // The dialog has no captcha, therefore the captcha threshold blocks it.
        $limit = USER_AUTHENTICATION_FAILURE_IP_CAPTCHA ?: USER_AUTHENTICATION_FAILURE_IP_BLOCK;

        return $limit && UserAuthenticationFailure::countIPFailures(UserUtil::getIpAddress()) >= $limit;
    }

    private function saveAuthenticationFailure(?string $field, string $username): void
    {
        if (!ENABLE_USER_AUTHENTICATION_FAILURE) {
            return;
        }

        $user = $this->findUser($username);
        (new UserAuthenticationFailureAction([], 'create', [
            'data' => [
                'environment' => 'user',
                'userID' => $user?->userID,
                'username' => \mb_substr($username, 0, 100),
                'time' => TIME_NOW,
                'ipAddress' => UserUtil::getIpAddress(),
                'userAgent' => UserUtil::getUserAgent(),
                'validationError' => 'invalid' . \ucfirst($field === 'password' ? 'password' : 'username'),
            ],
        ]))->executeAction();
    }
}
