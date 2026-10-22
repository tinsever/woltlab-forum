<?php

namespace forum\system\importer;

use wcf\data\object\type\ObjectTypeCache;
use wcf\system\importer\AbstractLikeImporter;

/**
 * Imports reactions to posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostLikeImporter extends AbstractLikeImporter
{
    use TForumImporter;

    public function __construct()
    {
        $this->objectTypeID = ObjectTypeCache::getInstance()->getObjectTypeIDByName(
            'com.woltlab.wcf.like.likeableObject',
            'com.tinsever.forum.likeablePost'
        );
    }

    #[\Override]
    public function import($oldID, array $data, array $additionalData = [])
    {
        $data['objectID'] = $this->getNewID('com.woltlab.wbb.post', $data['objectID'] ?? null);
        if (!$data['objectID']) {
            return 0;
        }

        return parent::import($oldID, $data, $additionalData);
    }
}
