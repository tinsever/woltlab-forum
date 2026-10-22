<?php

namespace forum\system\importer;

use forum\data\post\Post;
use forum\data\post\PostEditor;
use wcf\data\object\type\ObjectTypeCache;
use wcf\system\importer\AbstractPollImporter;

/**
 * Imports polls of posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostPollImporter extends AbstractPollImporter
{
    use TForumImporter;

    /**
     * @inheritDoc
     */
    protected $objectTypeName = 'com.woltlab.wbb.poll';

    public function __construct()
    {
        $this->objectTypeID = ObjectTypeCache::getInstance()->getObjectTypeIDByName(
            'com.woltlab.wcf.poll',
            Post::OBJECT_TYPE
        );
    }

    #[\Override]
    public function import($oldID, array $data, array $additionalData = [])
    {
        $data['objectID'] = $this->getNewID('com.woltlab.wbb.post', $data['objectID'] ?? null);
        if (!$data['objectID']) {
            return 0;
        }

        $pollID = parent::import($oldID, $data, $additionalData);
        if ($pollID) {
            (new PostEditor(new Post($data['objectID'])))->update(['pollID' => $pollID]);
        }

        return $pollID;
    }
}
