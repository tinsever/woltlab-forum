<?php

namespace forum\system\worker;

use forum\data\thread\ThreadAction;
use forum\data\thread\ThreadEditor;
use forum\data\thread\ThreadList;
use wcf\system\WCF;
use wcf\system\worker\AbstractLinearRebuildDataWorker;

/**
 * Recalculates the counters, first and last posts of all threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractLinearRebuildDataWorker<ThreadList>
 */
final class ThreadRebuildDataWorker extends AbstractLinearRebuildDataWorker
{
    /**
     * @inheritDoc
     */
    protected $objectListClassName = ThreadList::class;

    /**
     * @inheritDoc
     */
    protected $limit = 250;

    #[\Override]
    protected function initObjectList()
    {
        parent::initObjectList();

        $this->objectList->getConditionBuilder()->add('thread.movedThreadID IS NULL');
    }

    #[\Override]
    public function execute()
    {
        parent::execute();

        $sql = "SELECT  COUNT(*)
                FROM    wcf1_label_object
                WHERE   objectTypeID = (
                            SELECT  objectTypeID
                            FROM    wcf1_object_type
                            WHERE   objectType = ?
                                AND definitionID = (
                                    SELECT  definitionID
                                    FROM    wcf1_object_type_definition
                                    WHERE   definitionName = ?
                                )
                        )
                    AND objectID = ?";
        $labelStatement = WCF::getDB()->prepare($sql);

        $emptyThreadIDs = [];
        foreach ($this->objectList as $thread) {
            $editor = new ThreadEditor($thread);
            if (!$editor->rebuild()) {
                $emptyThreadIDs[] = $thread->threadID;
                continue;
            }

            $labelStatement->execute(['com.tinsever.forum.thread', 'com.woltlab.wcf.label.object', $thread->threadID]);
            $editor->update(['hasLabels' => $labelStatement->fetchSingleColumn() ? 1 : 0]);
        }

        if ($emptyThreadIDs !== []) {
            (new ThreadAction($emptyThreadIDs, 'delete', ['skipBoardUpdate' => true, 'skipLog' => true]))->executeAction();
        }
    }
}
