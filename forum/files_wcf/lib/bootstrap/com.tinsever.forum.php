<?php

/**
 * Bootstrap script of the forum.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */

use wcf\system\event\EventHandler;

return static function (): void {
    $eventHandler = EventHandler::getInstance();

    $eventHandler->register(
        \wcf\event\acp\menu\item\ItemCollecting::class,
        \forum\system\event\listener\AcpMenuItemCollectingListener::class
    );

    $eventHandler->register(
        \wcf\event\user\profile\UserProfileStatItemCollecting::class,
        \forum\system\event\listener\UserProfileListener::addStatItem(...)
    );
    $eventHandler->register(
        \wcf\event\user\profile\UserProfileHeaderSearchContentLinkCollecting::class,
        \forum\system\event\listener\UserProfileListener::addSearchContentLinks(...)
    );

    $eventHandler->register(
        \wcf\event\listView\user\MembersListViewInitialized::class,
        \forum\system\event\listener\UserListSortFieldListener::addMembersListSortField(...)
    );

    $eventHandler->register(
        \wcf\event\worker\RebuildWorkerCollecting::class,
        static function (\wcf\event\worker\RebuildWorkerCollecting $event) {
            $event->register(\forum\system\worker\PostRebuildDataWorker::class, 10);
            $event->register(\forum\system\worker\ThreadRebuildDataWorker::class, 20);
            $event->register(\forum\system\worker\BoardRebuildDataWorker::class, 30);
            $event->register(\forum\system\worker\UserPostCounterRebuildDataWorker::class, 40);
            $event->register(\forum\system\worker\PostSearchIndexRebuildDataWorker::class, 300);
        }
    );

    $eventHandler->register(
        \wcf\event\endpoint\ControllerCollecting::class,
        static function (\wcf\event\endpoint\ControllerCollecting $event) {
            $event->register(new \forum\system\endpoint\controller\forum\boards\MarkAllBoardsAsRead());
            $event->register(new \forum\system\endpoint\controller\forum\boards\MarkBoardAsRead());
            $event->register(new \forum\system\endpoint\controller\forum\boards\SetBoardCategoryCollapsed());
            $event->register(new \forum\system\endpoint\controller\forum\boards\SetBoardSubscription());
            $event->register(new \forum\system\endpoint\controller\forum\rss\feeds\DeleteRssFeed());
            $event->register(new \forum\system\endpoint\controller\forum\rss\feeds\DisableRssFeed());
            $event->register(new \forum\system\endpoint\controller\forum\rss\feeds\EnableRssFeed());
            $event->register(new \forum\system\endpoint\controller\forum\rss\feeds\ImportRssFeed());
            $event->register(new \forum\system\endpoint\controller\forum\posts\ClosePost());
            $event->register(new \forum\system\endpoint\controller\forum\posts\DeletePost());
            $event->register(new \forum\system\endpoint\controller\forum\posts\DisablePost());
            $event->register(new \forum\system\endpoint\controller\forum\posts\EnablePost());
            $event->register(new \forum\system\endpoint\controller\forum\posts\GetPostPopover());
            $event->register(new \forum\system\endpoint\controller\forum\posts\MarkPostAsBestAnswer());
            $event->register(new \forum\system\endpoint\controller\forum\posts\OpenPost());
            $event->register(new \forum\system\endpoint\controller\forum\posts\RestorePost());
            $event->register(new \forum\system\endpoint\controller\forum\posts\SoftDeletePost());
            $event->register(new \forum\system\endpoint\controller\forum\threads\CloseThread());
            $event->register(new \forum\system\endpoint\controller\forum\threads\DeleteThread());
            $event->register(new \forum\system\endpoint\controller\forum\threads\DisableThread());
            $event->register(new \forum\system\endpoint\controller\forum\threads\EnableThread());
            $event->register(new \forum\system\endpoint\controller\forum\threads\GetThreadContentHeaderTitle());
            $event->register(new \forum\system\endpoint\controller\forum\threads\GetThreadPopover());
            $event->register(new \forum\system\endpoint\controller\forum\threads\MarkThreadAsDone());
            $event->register(new \forum\system\endpoint\controller\forum\threads\MarkThreadAsRead());
            $event->register(new \forum\system\endpoint\controller\forum\threads\MarkThreadAsUndone());
            $event->register(new \forum\system\endpoint\controller\forum\threads\OpenThread());
            $event->register(new \forum\system\endpoint\controller\forum\threads\RestoreThread());
            $event->register(new \forum\system\endpoint\controller\forum\threads\SetThreadSubscription());
            $event->register(new \forum\system\endpoint\controller\forum\threads\SoftDeleteThread());
            $event->register(new \forum\system\endpoint\controller\forum\threads\StickThread());
            $event->register(new \forum\system\endpoint\controller\forum\threads\UnsetThreadAnnouncement());
            $event->register(new \forum\system\endpoint\controller\forum\threads\UnsetThreadBestAnswer());
            $event->register(new \forum\system\endpoint\controller\forum\threads\UnstickThread());
        }
    );

    $eventHandler->register(
        \wcf\event\moderation\DeletedContentProvidersCollecting::class,
        static function (\wcf\event\moderation\DeletedContentProvidersCollecting $event) {
            $event->register(new \forum\system\moderation\DeletedThreadProvider());
            $event->register(new \forum\system\moderation\DeletedPostProvider());
        }
    );
};
