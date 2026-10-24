<?php

namespace wcf\page;

use wcf\system\user\LinkedAccountHandler;
use wcf\data\user\UserProfile;
use wcf\page\AbstractPage;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\menu\user\UserMenu;
use wcf\system\request\LinkHandler;
use wcf\system\user\authentication\TReauthenticationCheck;
use wcf\system\WCF;

/**
 * Lists the accounts linked with the active user.
 *
 * The page stays accessible without the permission to use the switcher, so
 * that users can always remove existing links.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class AccountSwitcherPage extends AbstractPage
{
    use TReauthenticationCheck;

    /**
     * @inheritDoc
     */
    public $loginRequired = true;

    /**
     * @inheritDoc
     */
    public $neededModules = ['MODULE_ACCOUNT_SWITCHER'];

    /**
     * @var UserProfile[]
     */
    private array $accounts = [];

    /**
     * @var array<int, int>
     */
    private array $linkTimes = [];

    /**
     * @var array<int, array{notifications: int, conversations: int}>
     */
    private array $unreadCounts = [];

    #[\Override]
    public function readParameters()
    {
        parent::readParameters();

        // Linking an account requires the password of the active account.
        if (isset($_GET['link']) && LinkedAccountHandler::getInstance()->isAvailable()) {
            $this->requestReauthentication($this->getLinkDialogUrl());
        }
    }

    #[\Override]
    public function readData()
    {
        parent::readData();

        $handler = LinkedAccountHandler::getInstance();
        $userID = WCF::getUser()->userID;

        $this->accounts = $handler->getLinkedAccounts($userID);
        $userIDs = \array_map(static fn(UserProfile $account) => $account->userID, $this->accounts);
        $this->linkTimes = $handler->getLinkTimes([$userID, ...$userIDs]);
        $this->unreadCounts = $handler->getUnreadCounts($userIDs);
    }

    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();

        $handler = LinkedAccountHandler::getInstance();
        $limit = ACCOUNT_SWITCHER_MAX_ACCOUNTS;
        $isAvailable = $handler->isAvailable();

        WCF::getTPL()->assign([
            'accounts' => $this->accounts,
            'activeAccount' => UserProfileRuntimeCache::getInstance()->getObject(WCF::getUser()->userID),
            'linkTimes' => $this->linkTimes,
            'unreadCounts' => $this->unreadCounts,
            'isAvailable' => $isAvailable,
            'canLink' => $isAvailable && (!$limit || \count($this->accounts) + 1 < $limit),
            'needsReauthentication' => WCF::getSession()->needsReauthentication(),
            'reauthenticationLink' => LinkHandler::getInstance()->getLink('Reauthentication', [
                'url' => $this->getLinkDialogUrl(),
            ]),
            'openLinkDialog' => isset($_GET['link']),
        ]);
    }

    /**
     * Returns the url of this page that opens the link dialog.
     */
    private function getLinkDialogUrl(): string
    {
        return LinkHandler::getInstance()->getControllerLink(static::class, [], 'link=1');
    }

    #[\Override]
    public function show()
    {
        UserMenu::getInstance()->setActiveMenuItem('wcf.user.menu.profile.linkedAccounts');

        parent::show();
    }
}
