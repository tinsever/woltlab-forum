<?php

namespace forum\system\attachment;

use forum\data\board\BoardCache;
use forum\data\post\Post;
use forum\system\cache\runtime\PostRuntimeCache;
use wcf\system\attachment\AbstractAttachmentObjectType;
use wcf\system\WCF;
use wcf\util\ArrayUtil;

/**
 * Attachment object type for posts. The parent object id is the board id.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostAttachmentObjectType extends AbstractAttachmentObjectType
{
    #[\Override]
    public function getMaxSize()
    {
        return WCF::getSession()->getPermission('user.forum.maxAttachmentSize');
    }

    #[\Override]
    public function getAllowedExtensions()
    {
        return ArrayUtil::trim(\explode("\n", WCF::getSession()->getPermission('user.forum.allowedAttachmentExtensions')));
    }

    #[\Override]
    public function getMaxCount()
    {
        return WCF::getSession()->getPermission('user.forum.maxAttachmentCount');
    }

    private function getPost(int $objectID): ?Post
    {
        $post = $this->getObject($objectID);
        if ($post === null) {
            $post = PostRuntimeCache::getInstance()->getObject($objectID);
        }

        return $post;
    }

    #[\Override]
    public function canDownload($objectID)
    {
        if (!$objectID) {
            return false;
        }

        $post = $this->getPost($objectID);

        return $post !== null && $post->canRead() && $post->getPermission('canDownloadAttachment');
    }

    #[\Override]
    public function canViewPreview($objectID)
    {
        if (!$objectID) {
            return false;
        }

        $post = $this->getPost($objectID);

        return $post !== null && $post->canRead() && $post->getPermission('canViewAttachmentPreview');
    }

    #[\Override]
    public function canUpload($objectID, $parentObjectID = 0)
    {
        if ($objectID) {
            $post = $this->getPost($objectID);

            return $post !== null && $post->canEdit() && $post->getPermission('canUploadAttachment');
        }

        $board = BoardCache::getInstance()->getBoard(\intval($parentObjectID));

        return $board !== null && $board->canEnter() && $board->getPermission('canUploadAttachment');
    }

    #[\Override]
    public function canDelete($objectID)
    {
        if (!$objectID) {
            return false;
        }

        $post = $this->getPost($objectID);

        return $post !== null && $post->canEdit();
    }

    #[\Override]
    public function cacheObjects(array $objectIDs)
    {
        foreach (PostRuntimeCache::getInstance()->getObjects(\array_unique($objectIDs)) as $objectID => $post) {
            if ($post !== null) {
                $this->cachedObjects[$objectID] = $post;
            }
        }
    }
}
