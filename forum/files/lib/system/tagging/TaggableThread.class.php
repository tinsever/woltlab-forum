<?php

namespace forum\system\tagging;

use forum\system\listView\user\TaggedThreadListView;
use wcf\system\listView\AbstractListView;
use wcf\system\tagging\AbstractTaggedListViewProvider;

/**
 * Taggable object type for threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class TaggableThread extends AbstractTaggedListViewProvider
{
    #[\Override]
    public function getListView(array $tagIDs): AbstractListView
    {
        return new TaggedThreadListView($tagIDs);
    }
}
