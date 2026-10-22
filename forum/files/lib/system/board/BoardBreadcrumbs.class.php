<?php

namespace forum\system\board;

use forum\data\board\Board;
use forum\data\thread\Thread;
use wcf\system\page\PageLocationManager;

/**
 * Adds the board hierarchy to the breadcrumbs. Parent locations are displayed
 * in the reverse order in which they are added, therefore the nearest location
 * has to be added first.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class BoardBreadcrumbs
{
    public static function addBoard(Board $board, bool $includeBoard = true): void
    {
        $manager = PageLocationManager::getInstance();

        if ($includeBoard) {
            $manager->addParentLocation('com.tinsever.forum.Board', $board->boardID, $board);
        }

        foreach (\array_reverse($board->getParentBoards()) as $parentBoard) {
            $manager->addParentLocation('com.tinsever.forum.Board', $parentBoard->boardID, $parentBoard);
        }

        $manager->addParentLocation('com.tinsever.forum.BoardList');
    }

    public static function addThread(Thread $thread): void
    {
        PageLocationManager::getInstance()->addParentLocation('com.tinsever.forum.Thread', $thread->threadID, $thread);

        self::addBoard($thread->getBoard());
    }
}
