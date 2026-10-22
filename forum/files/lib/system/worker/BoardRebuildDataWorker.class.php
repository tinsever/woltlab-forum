<?php

namespace forum\system\worker;

use forum\data\board\BoardEditor;
use forum\data\board\BoardList;
use wcf\system\worker\AbstractRebuildDataWorker;

/**
 * Recalculates the counters and last posts of all boards.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractRebuildDataWorker<BoardList>
 */
final class BoardRebuildDataWorker extends AbstractRebuildDataWorker
{
    /**
     * @inheritDoc
     */
    protected $objectListClassName = BoardList::class;

    /**
     * @inheritDoc
     */
    protected $limit = 50;

    #[\Override]
    public function execute()
    {
        parent::execute();

        foreach ($this->objectList as $board) {
            (new BoardEditor($board))->rebuildStats();
        }

        BoardEditor::resetCache();
    }
}
