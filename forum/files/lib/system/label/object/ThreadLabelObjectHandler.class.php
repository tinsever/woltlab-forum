<?php

namespace forum\system\label\object;

use forum\data\board\BoardCache;
use wcf\system\label\LabelHandler;
use wcf\system\label\object\AbstractLabelObjectHandler;

/**
 * Label handler for threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ThreadLabelObjectHandler extends AbstractLabelObjectHandler
{
    /**
     * @inheritDoc
     */
    protected $objectType = 'com.tinsever.forum.thread';

    /**
     * Restricts the available label groups to those assigned to the given board.
     */
    public function setBoardID(int $boardID): void
    {
        $groupIDs = BoardCache::getInstance()->getLabelGroupIDs($boardID);

        $this->labelGroups = $groupIDs === [] ? [] : LabelHandler::getInstance()->getLabelGroups($groupIDs);
    }

    /**
     * Returns the label groups that are available in the given board.
     *
     * @return \wcf\data\label\group\ViewableLabelGroup[]
     */
    public static function getLabelGroupsForBoard(int $boardID, string $permission = 'canSetLabel'): array
    {
        $groupIDs = BoardCache::getInstance()->getLabelGroupIDs($boardID);
        if ($groupIDs === []) {
            return [];
        }

        return LabelHandler::getInstance()->getLabelGroups($groupIDs, true, $permission);
    }

    public function getObjectTypeID(): int
    {
        return $this->objectTypeID;
    }
}
