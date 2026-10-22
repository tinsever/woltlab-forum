<?php

namespace forum\system\worker;

use forum\data\post\PostEditor;
use wcf\data\user\UserList;
use wcf\system\worker\AbstractLinearRebuildDataWorker;

/**
 * Recalculates the forum post counter of all users.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractLinearRebuildDataWorker<UserList>
 */
final class UserPostCounterRebuildDataWorker extends AbstractLinearRebuildDataWorker
{
    /**
     * @inheritDoc
     */
    protected $objectListClassName = UserList::class;

    /**
     * @inheritDoc
     */
    protected $limit = 500;

    #[\Override]
    public function execute()
    {
        parent::execute();

        PostEditor::updateUserPostCounters($this->objectList->getObjectIDs());
    }
}
