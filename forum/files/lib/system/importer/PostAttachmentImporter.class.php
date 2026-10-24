<?php

namespace forum\system\importer;

use forum\data\post\Post;
use forum\data\post\PostEditor;
use wcf\data\object\type\ObjectTypeCache;
use wcf\system\importer\AbstractAttachmentImporter;
use wcf\util\JSON;

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
            $message = self::replaceAttachmentID($post->message, (int)$oldID, (int)$attachmentID);
            if ($message !== $post->message) {
                (new PostEditor($post))->update(['message' => $message]);
            }
        }

        return $attachmentID;
    }

    /**
     * Replaces the references to an attachment in both forms a message can
     * contain, BBCode (`[attach]1[/attach]`, `[attach=1,…]`) and the HTML
     * metacode of WoltLab Suite 5.2 and newer.
     *
     * Unlike the Core's `fixEmbeddedAttachments()`, this does not stop after the
     * BBCode form, and it only matches the exact ID (`1` but not `10`).
     */
    public static function replaceAttachmentID(string $message, int $oldID, int $newID): string
    {
        $message = \preg_replace(
            [
                '~\[attach\]' . $oldID . '\[/attach\]~i',
                '~\[attach=' . $oldID . '(?=[\],])~i',
            ],
            [
                '[attach]' . $newID . '[/attach]',
                '[attach=' . $newID,
            ],
            $message
        );

        return \preg_replace_callback(
            '~<woltlab-metacode data-name="attach" data-attributes="(?<attributes>[^"]+)">~',
            static function (array $matches) use ($oldID, $newID): string {
                $encodedAttributes = $matches['attributes'];
                $decoded = \base64_decode($encodedAttributes, true);
                if ($decoded !== false) {
                    try {
                        $attributes = JSON::decode($decoded);
                        if (\is_array($attributes) && isset($attributes[0]) && (int)$attributes[0] === $oldID) {
                            $attributes[0] = $newID;
                            $encodedAttributes = \base64_encode(JSON::encode($attributes));
                        }
                    } catch (\Exception) {
                        // keep the reference as it is
                    }
                }

                return '<woltlab-metacode data-name="attach" data-attributes="' . $encodedAttributes . '">';
            },
            $message
        );
    }
}
