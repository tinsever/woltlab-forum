<?php

namespace forum\system\cache\eager;

use forum\data\board\Board;
use forum\data\board\BoardList;
use forum\system\cache\eager\data\BoardStructureCacheData;
use wcf\data\object\type\ObjectTypeCache;
use wcf\system\cache\eager\AbstractEagerCache;
use wcf\system\WCF;

/**
 * Caches all boards and their hierarchy.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractEagerCache<BoardStructureCacheData>
 */
final class BoardStructureCache extends AbstractEagerCache
{
    #[\Override]
    protected function getCacheData(): BoardStructureCacheData
    {
        $boardList = new BoardList();
        $boardList->readObjects();

        /** @var array<int, Board> $boards */
        $boards = $boardList->getObjects();
        $children = [0 => []];
        foreach ($boards as $board) {
            $parentID = $board->parentID ?: 0;
            if ($parentID && !isset($boards[$parentID])) {
                $parentID = 0;
            }

            $children[$parentID][] = $board->boardID;
        }

        foreach ($children as &$childIDs) {
            \usort($childIDs, static function (int $a, int $b) use ($boards) {
                return [$boards[$a]->position, $a] <=> [$boards[$b]->position, $b];
            });
        }
        unset($childIDs);

        $labelGroups = [];
        $objectTypeID = ObjectTypeCache::getInstance()->getObjectTypeIDByName(
            'com.woltlab.wcf.label.objectType',
            'com.tinsever.forum.board'
        );
        if ($objectTypeID) {
            $sql = "SELECT  groupID, objectID
                    FROM    wcf1_label_group_to_object
                    WHERE   objectTypeID = ?";
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute([$objectTypeID]);
            while ($row = $statement->fetchArray()) {
                $labelGroups[$row['objectID']][] = $row['groupID'];
            }
        }

        return new BoardStructureCacheData($boards, $children, $labelGroups);
    }

    /**
     * Returns freshly generated data, bypassing the cache of the current request.
     */
    public function getFreshData(): BoardStructureCacheData
    {
        return $this->getCacheData();
    }
}
