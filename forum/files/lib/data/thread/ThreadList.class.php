<?php

namespace forum\data\thread;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends DatabaseObjectList<Thread>
 */
class ThreadList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = Thread::class;
}
