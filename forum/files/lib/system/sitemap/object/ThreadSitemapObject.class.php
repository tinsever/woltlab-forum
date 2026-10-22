<?php

namespace forum\system\sitemap\object;

use forum\data\thread\Thread;
use forum\data\thread\ThreadList;
use wcf\data\DatabaseObject;
use wcf\data\page\PageCache;
use wcf\system\sitemap\object\AbstractSitemapObjectObjectType;

/**
 * Sitemap entries for threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ThreadSitemapObject extends AbstractSitemapObjectObjectType
{
    #[\Override]
    public function getObjectClass()
    {
        return Thread::class;
    }

    #[\Override]
    public function getObjectList()
    {
        $threadList = new ThreadList();
        $threadList->getConditionBuilder()->add('thread.isDeleted = ?', [0]);
        $threadList->getConditionBuilder()->add('thread.isDisabled = ?', [0]);
        $threadList->getConditionBuilder()->add('thread.movedThreadID IS NULL');

        return $threadList;
    }

    #[\Override]
    public function getLastModifiedColumn()
    {
        return 'lastPostTime';
    }

    #[\Override]
    public function canView(DatabaseObject $object)
    {
        \assert($object instanceof Thread);

        return $object->canRead();
    }

    #[\Override]
    public function isAvailableType()
    {
        return (bool)PageCache::getInstance()->getPageByIdentifier('com.tinsever.forum.Thread')->allowSpidersToIndex;
    }
}
