<?php

namespace forum\system\page\handler;

use forum\data\board\BoardCache;
use forum\system\board\BoardListBuilder;
use wcf\data\page\Page;
use wcf\data\user\online\UserOnline;
use wcf\system\page\handler\AbstractLookupPageHandler;
use wcf\system\page\handler\IOnlineLocationPageHandler;
use wcf\system\page\handler\TOnlineLocationPageHandler;
use wcf\system\WCF;

/**
 * Page handler for boards.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class BoardPageHandler extends AbstractLookupPageHandler implements IOnlineLocationPageHandler
{
    use TOnlineLocationPageHandler;

    #[\Override]
    public function getLink($objectID)
    {
        return BoardCache::getInstance()->getBoard($objectID)->getLink();
    }

    #[\Override]
    public function isValid($objectID)
    {
        return BoardCache::getInstance()->getBoard($objectID) !== null;
    }

    #[\Override]
    public function isVisible($objectID = null)
    {
        $board = BoardCache::getInstance()->getBoard($objectID);

        return $board !== null && $board->isVisible();
    }

    #[\Override]
    public function getOutstandingItemCount($objectID = null)
    {
        if ($objectID === null) {
            return 0;
        }

        $board = BoardCache::getInstance()->getBoard($objectID);
        if ($board === null) {
            return 0;
        }

        $counts = BoardListBuilder::getUnreadThreadCounts();
        $count = 0;
        foreach ($board->getAllChildIDs() as $boardID) {
            $count += $counts[$boardID] ?? 0;
        }

        return $count;
    }

    #[\Override]
    public function lookup($searchString)
    {
        $results = [];
        foreach (BoardCache::getInstance()->getBoards() as $board) {
            if (\mb_stripos($board->getTitle(), $searchString) === false) {
                continue;
            }

            $results[] = [
                'description' => $board->getPlainDescription(),
                'image' => 'fa-' . $board->getIconName(),
                'link' => $board->getLink(),
                'objectID' => $board->boardID,
                'title' => $board->getTitle(),
            ];

            if (\count($results) >= 10) {
                break;
            }
        }

        return $results;
    }

    #[\Override]
    public function getOnlineLocation(Page $page, UserOnline $user)
    {
        if ($user->pageObjectID === null) {
            return '';
        }

        $board = BoardCache::getInstance()->getBoard($user->pageObjectID);
        if ($board === null || !$board->isVisible()) {
            return '';
        }

        return WCF::getLanguage()->getDynamicVariable('wcf.page.onlineLocation.' . $page->identifier, [
            'board' => $board,
        ]);
    }

    #[\Override]
    public function prepareOnlineLocation(Page $page, UserOnline $user)
    {
    }

    #[\Override]
    public function cacheObject(int $objectID): void
    {
    }
}
