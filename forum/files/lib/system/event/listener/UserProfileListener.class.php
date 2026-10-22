<?php

namespace forum\system\event\listener;

use forum\page\UserPostListPage;
use forum\page\UserThreadListPage;
use wcf\event\user\profile\UserProfileHeaderSearchContentLinkCollecting;
use wcf\event\user\profile\UserProfileStatItemCollecting;
use wcf\system\request\LinkHandler;
use wcf\system\view\user\profile\UserProfileHeaderViewSearchContentLink;
use wcf\system\view\user\profile\UserProfileStatItem;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Adds the forum statistics and content links to user profiles.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class UserProfileListener
{
    public static function addStatItem(UserProfileStatItemCollecting $event): void
    {
        // @phpstan-ignore property.notFound
        $posts = \intval($event->user->forumPosts);
        if (!$posts) {
            return;
        }

        $event->register(UserProfileStatItem::forLink(
            WCF::getLanguage()->get('forum.user.posts'),
            StringUtil::formatNumeric($posts),
            LinkHandler::getInstance()->getControllerLink(UserPostListPage::class, ['object' => $event->user])
        ));
    }

    public static function addSearchContentLinks(UserProfileHeaderSearchContentLinkCollecting $event): void
    {
        // @phpstan-ignore property.notFound
        if (!$event->user->forumPosts) {
            return;
        }

        $event->register(new UserProfileHeaderViewSearchContentLink(
            WCF::getLanguage()->get('forum.user.threads'),
            LinkHandler::getInstance()->getControllerLink(UserThreadListPage::class, ['object' => $event->user])
        ));
        $event->register(new UserProfileHeaderViewSearchContentLink(
            WCF::getLanguage()->get('forum.user.posts'),
            LinkHandler::getInstance()->getControllerLink(UserPostListPage::class, ['object' => $event->user])
        ));
    }
}
