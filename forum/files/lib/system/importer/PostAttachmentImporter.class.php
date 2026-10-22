<?php

namespace forum\system\importer;

use forum\data\post\Post;
use forum\data\post\PostEditor;
use wcf\data\object\type\ObjectTypeCache;
use wcf\system\importer\AbstractAttachmentImporter;

/**
 * Imports attachments of posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostAttachmentImporter extends AbstractAttachmentImporter
{
    use TForumImporter;

    public function __construct()
    {
        $this->objectTypeID = ObjectTypeCache::getInstance()->getObjectTypeIDByName(
            'com.woltlab.wcf.attachment.objectType',
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

        $attachmentID = parent::import($oldID, $data, $additionalData);
        if ($attachmentID && $attachmentID != $oldID) {
            $post = new Post($data['objectID']);
            $message = $this->fixEmbeddedAttachments($post->message, $oldID, $attachmentID);
            if ($message !== $post->message) {
                (new PostEditor($post))->update(['message' => $message]);
            }
        }

        return $attachmentID;
    }
}
