<?php

namespace wcf\form;

use wcf\system\user\LinkedAccountHandler;
use wcf\data\user\User;
use wcf\form\AbstractForm;
use wcf\form\AbstractFormBuilderForm;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\NamedUserException;
use wcf\system\request\LinkHandler;
use wcf\system\user\multifactor\IMultifactorMethod;
use wcf\system\user\multifactor\Setup;
use wcf\system\WCF;
use wcf\util\HeaderUtil;
use wcf\util\HtmlString;

/**
 * Asks for the multi-factor authentication of an account before the active
 * user may switch to it or link it. The active user stays logged in until the
 * authentication succeeded.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class AccountSwitcherMultifactorForm extends AbstractFormBuilderForm
{
    /**
     * @inheritDoc
     */
    public $loginRequired = true;

    /**
     * @inheritDoc
     */
    public $formAction = 'authenticate';

    private User $target;

    private bool $link;

    private string $returnUrl;

    /**
     * @var Setup[]
     */
    private array $setups;

    private Setup $setup;

    private IMultifactorMethod $processor;

    #[\Override]
    public function readParameters()
    {
        parent::readParameters();

        $handler = LinkedAccountHandler::getInstance();
        $pending = $handler->getPendingAuthentication();
        if ($pending === null || !$handler->isAvailable()) {
            throw new NamedUserException(HtmlString::fromSafeHtml(
                WCF::getLanguage()->getDynamicVariable('wcf.accountSwitcher.multifactor.expired')
            ));
        }

        $this->target = $pending['target'];
        $this->link = $pending['link'];
        $this->returnUrl = $pending['returnUrl'];

        // The accounts may have changed since the authentication was requested.
        $error = $this->link
            ? $handler->getLinkError(WCF::getUser(), $this->target)
            : $handler->getSwitchError($this->target);
        if ($error !== null) {
            $handler->clearPendingAuthentication();

            throw new NamedUserException(HtmlString::fromSafeHtml(WCF::getLanguage()->getDynamicVariable(
                'wcf.accountSwitcher.' . ($this->link ? 'link' : 'switch') . '.error.' . $error,
                ['limit' => ACCOUNT_SWITCHER_MAX_ACCOUNTS]
            )));
        }

        $this->setups = Setup::getAllForUser($this->target);
        if ($this->setups === []) {
            // Multi-factor authentication was disabled in the meantime.
            $handler->clearPendingAuthentication();
            $this->complete();
        }

        \uasort($this->setups, static function (Setup $a, Setup $b) {
            return $b->getObjectType()->priority <=> $a->getObjectType()->priority;
        });

        $setupID = \array_key_first($this->setups);
        if (isset($_GET['id'])) {
            $setupID = \intval($_GET['id']);
        }
        if (!isset($this->setups[$setupID])) {
            throw new IllegalLinkException();
        }

        $this->setup = $this->setups[$setupID];
        $this->processor = $this->setup->getObjectType()->getProcessor();
    }

    #[\Override]
    protected function createForm()
    {
        parent::createForm();

        $this->processor->createAuthenticationForm($this->form, $this->setup);
    }

    #[\Override]
    public function save()
    {
        AbstractForm::save();

        WCF::getDB()->beginTransaction();
        $setup = $this->setup->lock();
        $this->processor->processAuthenticationForm($this->form, $setup);
        WCF::getDB()->commitTransaction();

        $this->saved();
    }

    #[\Override]
    public function saved()
    {
        AbstractForm::saved();

        LinkedAccountHandler::getInstance()->clearPendingAuthentication();
        $this->complete();
    }

    /**
     * Performs the switch or link and leaves the form.
     */
    private function complete(): never
    {
        $handler = LinkedAccountHandler::getInstance();
        if ($this->link) {
            $handler->link(WCF::getUser(), $this->target);
            $redirectUrl = LinkHandler::getInstance()->getControllerLink(\wcf\page\AccountSwitcherPage::class);
            $message = WCF::getLanguage()->getDynamicVariable('wcf.accountSwitcher.link.success', [
                'target' => $this->target,
            ]);
        } else {
            $handler->changeUser($this->target);
            $redirectUrl = $this->returnUrl ?: LinkHandler::getInstance()->getLink();
            $message = '';
        }

        if ($message !== '') {
            HeaderUtil::delayedRedirect($redirectUrl, $message);
        } else {
            HeaderUtil::redirect($redirectUrl);
        }

        exit;
    }

    #[\Override]
    protected function setFormAction()
    {
        $this->form->action(LinkHandler::getInstance()->getControllerLink(static::class, [
            'id' => $this->setup->getId(),
        ]));
    }

    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();

        WCF::getTPL()->assign([
            'setups' => $this->setups,
            'setup' => $this->setup,
            'target' => UserProfileRuntimeCache::getInstance()->getObject($this->target->userID),
            'isLink' => $this->link,
            'returnUrl' => $this->returnUrl,
        ]);
    }
}
