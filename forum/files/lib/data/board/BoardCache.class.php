<?php

namespace forum\data\board;

use forum\system\board\BoardPermissionHandler;
use forum\system\cache\eager\BoardStructureCache;
use forum\system\cache\eager\data\BoardStructureCacheData;
use wcf\system\SingletonFactory;

/**
 * Provides access to the cached board hierarchy.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class BoardCache extends SingletonFactory
{
    private BoardStructureCacheData $data;

    #[\Override]
    protected function init()
    {
        $this->data = (new BoardStructureCache())->getCache();
    }

    /**
     * Reloads the data after a modification within the same request.
     */
    public function reload(): void
    {
        $this->data = (new BoardStructureCache())->getFreshData();
    }

    public function getBoard(int $boardID): ?Board
    {
        return $this->data->boards[$boardID] ?? null;
    }

    /**
     * @return array<int, Board>
     */
    public function getBoards(): array
    {
        return $this->data->boards;
    }

    /**
     * @param int[] $boardIDs
     * @return array<int, Board>
     */
    public function getBoardsByIDs(array $boardIDs): array
    {
        $boards = [];
        foreach ($boardIDs as $boardID) {
            if (isset($this->data->boards[$boardID])) {
                $boards[$boardID] = $this->data->boards[$boardID];
            }
        }

        return $boards;
    }

    /**
     * @return int[]
     */
    public function getChildIDs(int $parentID = 0): array
    {
        return $this->data->children[$parentID] ?? [];
    }

    /**
     * @return array<int, Board>
     */
    public function getChildren(int $parentID = 0): array
    {
        return $this->getBoardsByIDs($this->getChildIDs($parentID));
    }

    /**
     * Returns the ids of the given board and all of its descendants.
     *
     * @return int[]
     */
    public function getAllChildIDs(int $boardID): array
    {
        $boardIDs = [$boardID];
        foreach ($this->getChildIDs($boardID) as $childID) {
            $boardIDs = \array_merge($boardIDs, $this->getAllChildIDs($childID));
        }

        return $boardIDs;
    }

    /**
     * Returns the label group ids that are assigned to the given board.
     *
     * @return int[]
     */
    public function getLabelGroupIDs(int $boardID): array
    {
        return $this->data->labelGroups[$boardID] ?? [];
    }

    /**
     * @return array{users: int[], groups: int[]}
     */
    public function getModerators(int $boardID): array
    {
        return BoardPermissionHandler::getInstance()->getModerators($boardID);
    }
}
