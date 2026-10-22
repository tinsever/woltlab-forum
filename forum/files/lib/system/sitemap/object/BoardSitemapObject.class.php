<?php

namespace forum\system\sitemap\object;

use forum\data\board\Board;
use wcf\data\DatabaseObject;
use wcf\data\page\PageCache;
use wcf\system\sitemap\object\AbstractSitemapObjectObjectType;

/**
 * Sitemap entries for boards.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class BoardSitemapObject extends AbstractSitemapObjectObjectType
{
    #[\Override]
    public function getObjectClass()
    {
        return Board::class;
    }

    #[\Override]
    public function getLastModifiedColumn()
    {
        return 'time';
    }

    #[\Override]
    public function canView(DatabaseObject $object)
    {
        \assert($object instanceof Board);

        return !$object->isExternalLink() && $object->isVisible() && $object->canEnter();
    }

    #[\Override]
    public function isAvailableType()
    {
        return (bool)PageCache::getInstance()->getPageByIdentifier('com.tinsever.forum.Board')->allowSpidersToIndex;
    }
}
