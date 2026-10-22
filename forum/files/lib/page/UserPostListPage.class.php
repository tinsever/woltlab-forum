<?php

namespace forum\page;

use forum\system\listView\user\UserPostListView;
use wcf\data\user\UserProfile;
use wcf\page\AbstractListViewPage;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\exception\IllegalLinkException;
use wcf\system\listView\AbstractListView;
use wcf\system\page\PageLocationManager;
use wcf\system\WCF;

/**
 * Shows the posts of a user.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractListViewPage<UserPostListView>
 */
class UserPostListPage extends AbstractListViewPage
{
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
        return new UserPostListView($this->user->userID);
    }

    #[\Override]
    protected function getBaseUrlParameters(): array
    {
        return ['object' => $this->user];
    }

    #[\Override]
    public function readData()
    {
        parent::readData();

        PageLocationManager::getInstance()->addParentLocation('com.tinsever.forum.BoardList');
    }

    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();

        WCF::getTPL()->assign('user', $this->user);
    }
}
