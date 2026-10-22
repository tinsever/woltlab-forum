<?php

namespace forum\system\cache\runtime;

use forum\data\thread\Thread;
use forum\data\thread\ViewableThreadList;
use wcf\system\cache\runtime\AbstractRuntimeCache;

/**
 * Runtime cache for threads, including their unread state.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractRuntimeCache<Thread, ViewableThreadList>
 */
class ThreadRuntimeCache extends AbstractRuntimeCache
{
    /**
     * @inheritDoc
     */
    protected $listClassName = ViewableThreadList::class;

    #[\Override]
    protected function getObjectList()
    {
        return new ViewableThreadList(null, false, false, false);
    }
}
