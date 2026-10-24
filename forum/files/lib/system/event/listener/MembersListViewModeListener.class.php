<?php

namespace forum\system\event\listener;

use forum\system\listView\user\MembersTableListView;
use forum\system\listView\user\UserSearchResultsTableListView;
use wcf\page\MembersListPage;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\listView\user\MembersListView;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\HeaderUtil;

/**
 * Shows the members list as a table instead of user cards, depending on the
 * default view of the site and the choice of the visitor.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class MembersListViewModeListener implements IParameterizedEventListener
{
    public const COOKIE_NAME = 'forumMembersListView';

    public const VIEW_CARDS = 'cards';

    public const VIEW_LIST = 'list';

    /**
     * @param MembersListPage $eventObj
     */
    #[\Override]
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        $tpl = WCF::getTPL();
        $currentView = $tpl->get('listView');
        if (!($currentView instanceof MembersListView)) {
            return;
        }

        $mode = $this->getMode();
        if (\FORUM_MEMBERS_LIST_ENABLE_VIEW_SWITCH) {
            $target = $mode === self::VIEW_LIST ? self::VIEW_CARDS : self::VIEW_LIST;
            $tpl->assign([
                '__forumMembersListView' => $mode,
                '__forumMembersListViewSwitchLink' => LinkHandler::getInstance()->getControllerLink(
                    MembersListPage::class,
                    [
                        'id' => $eventObj->searchID ?: null,
                        'view' => $target,
                    ]
                ),
            ]);
        }

        if ($mode !== self::VIEW_LIST) {
            return;
        }

        $view = $eventObj->searchID
            ? new UserSearchResultsTableListView($eventObj->searchID)
            : new MembersTableListView();

        if (!$view->isAccessible()) {
            return;
        }

        $view->setSortField($currentView->getSortField());
        $view->setSortOrder($currentView->getSortOrder());
        if ($currentView->getActiveFilters() !== []) {
            $view->setActiveFilters($currentView->getActiveFilters());
        }
        if ($currentView->getPageNo() > 1) {
            $view->setPageNo($currentView->getPageNo());
        }
        $view->setBaseUrl($currentView->getBaseUrl());

        $tpl->assign('listView', $view);
    }

    private function getMode(): string
    {
        $default = \FORUM_MEMBERS_LIST_DEFAULT_VIEW === self::VIEW_LIST ? self::VIEW_LIST : self::VIEW_CARDS;
        if (!\FORUM_MEMBERS_LIST_ENABLE_VIEW_SWITCH) {
            return $default;
        }

        $requested = $_GET['view'] ?? null;
        if ($requested === self::VIEW_CARDS || $requested === self::VIEW_LIST) {
            HeaderUtil::setCookie(self::COOKIE_NAME, $requested, \TIME_NOW + 365 * 86400);

            return $requested;
        }

        $stored = $_COOKIE[\COOKIE_PREFIX . self::COOKIE_NAME] ?? null;
        if ($stored === self::VIEW_CARDS || $stored === self::VIEW_LIST) {
            return $stored;
        }

        return $default;
    }
}
