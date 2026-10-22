<?php

namespace forum\system\event\listener;

use forum\acp\form\BoardAddForm;
use forum\acp\form\RssFeedAddForm;
use forum\acp\page\BoardListPage;
use forum\acp\page\RssFeedListPage;
use wcf\event\acp\menu\item\ItemCollecting;
use wcf\system\menu\acp\AcpMenuItem;
use wcf\system\request\LinkHandler;
use wcf\system\style\FontAwesomeIcon;
use wcf\system\WCF;

/**
 * Adds the forum to the ACP menu.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class AcpMenuItemCollectingListener
{
    public function __invoke(ItemCollecting $event): void
    {
        $canManageBoards = WCF::getSession()->getPermission('admin.forum.canManageBoard');
        $canManageFeeds = WCF::getSession()->getPermission('admin.forum.canManageRssFeed');
        if (!$canManageBoards && !$canManageFeeds) {
            return;
        }

        $event->register(new AcpMenuItem(
            'forum.acp.menu.link.forum',
            parentMenuItem: 'wcf.acp.menu.link.application'
        ));

        if ($canManageFeeds) {
            $event->register(new AcpMenuItem(
                'forum.acp.menu.link.rssFeed.list',
                parentMenuItem: 'forum.acp.menu.link.forum',
                link: LinkHandler::getInstance()->getControllerLink(RssFeedListPage::class),
            ));
            $event->register(new AcpMenuItem(
                'forum.acp.menu.link.rssFeed.add',
                parentMenuItem: 'forum.acp.menu.link.rssFeed.list',
                link: LinkHandler::getInstance()->getControllerLink(RssFeedAddForm::class),
                icon: FontAwesomeIcon::fromValues('plus')
            ));
        }

        if (!$canManageBoards) {
            return;
        }

        $event->register(new AcpMenuItem(
            'forum.acp.menu.link.board.list',
            parentMenuItem: 'forum.acp.menu.link.forum',
            link: LinkHandler::getInstance()->getControllerLink(BoardListPage::class),
        ));
        $event->register(new AcpMenuItem(
            'forum.acp.menu.link.board.add',
            parentMenuItem: 'forum.acp.menu.link.board.list',
            link: LinkHandler::getInstance()->getControllerLink(BoardAddForm::class),
            icon: FontAwesomeIcon::fromValues('plus')
        ));
    }
}
