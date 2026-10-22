<?php

namespace forum\data\board;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of boards.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends DatabaseObjectList<Board>
 */
class BoardList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = Board::class;

    /**
     * @inheritDoc
     */
    public $sqlOrderBy = 'board.parentID, board.position';
}
