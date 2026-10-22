<?php

namespace forum\page;

use forum\action\ThreadAddBoardSelectDialogAction;
use forum\data\board\BoardNode;
use forum\system\board\BoardListBuilder;
use forum\system\cache\tolerant\ForumStatisticsCache;
use wcf\data\user\online\UsersOnlineList;
use wcf\page\AbstractPage;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;

/**
 * Shows the board list, the start page of the forum.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class BoardListPage extends AbstractPage
{
    /**
     * @var BoardNode[]
     */
    public array $boardNodes = [];

    public array $stats = [];

    public ?UsersOnlineList $usersOnlineList = null;

    #[\Override]
    public function readParameters()
    {
        parent::readParameters();

        $this->canonicalURL = LinkHandler::getInstance()->getControllerLink(static::class);
    }

    #[\Override]
    public function readData()
    {
        parent::readData();

        $this->boardNodes = (new BoardListBuilder())->getNodes();

        if (\FORUM_BOARD_LIST_ENABLE_STATS) {
            $this->stats = (new ForumStatisticsCache())->getCache();
            if ($this->stats['newestMemberID']) {
                UserProfileRuntimeCache::getInstance()->cacheObjectID($this->stats['newestMemberID']);
            }
        }

        if (
            \FORUM_BOARD_LIST_ENABLE_ONLINE_LIST
            && \MODULE_USERS_ONLINE
            && WCF::getSession()->getPermission('user.profile.canViewUsersOnlineList')
        ) {
            $this->usersOnlineList = new UsersOnlineList();
            $this->usersOnlineList->readStats();
            $this->usersOnlineList->checkRecord();
            $this->usersOnlineList->getConditionBuilder()->add('session.userID IS NOT NULL');
            $this->usersOnlineList->readObjects();
        }
    }

    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();

        $newestMember = null;
        if (!empty($this->stats['newestMemberID'])) {
            $newestMember = UserProfileRuntimeCache::getInstance()->getObject($this->stats['newestMemberID']);
        }

        WCF::getTPL()->assign([
            'boardNodes' => $this->boardNodes,
            'stats' => $this->stats,
            'newestMember' => $newestMember,
            'usersOnlineList' => $this->usersOnlineList,
            'unreadThreadCount' => BoardListBuilder::getUnreadThreadCount(),
            'canStartThread' => ThreadAddBoardSelectDialogAction::canStartAnyThread(),
        ]);
    }
}
