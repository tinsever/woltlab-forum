<?php

namespace forum\page;

use forum\system\listView\user\ThreadListView;
use wcf\data\user\UserProfile;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\exception\IllegalLinkException;
use wcf\system\listView\AbstractListView;
use wcf\system\WCF;

/**
 * Shows the threads started by a user.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class UserThreadListPage extends AbstractThreadListPage
{
    protected string $filter = 'user';

    public UserProfile $user;

    #[\Override]
    public function readParameters()
    {
        parent::readParameters();

        $user = UserProfileRuntimeCache::getInstance()->getObject(\intval($_REQUEST['id'] ?? 0));
        if ($user === null) {
            throw new IllegalLinkException();
        }
        $this->user = $user;
    }

    #[\Override]
    protected function createListView(): AbstractListView
    {
        return new ThreadListView(0, 'user', $this->user->userID);
    }

    #[\Override]
    protected function getBaseUrlParameters(): array
    {
        return ['object' => $this->user];
    }

    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();

        WCF::getTPL()->assign(['user' => $this->user]);
    }
}
