<?php

namespace forum\data\post;

use forum\data\thread\Thread;
use forum\system\cache\runtime\ThreadRuntimeCache;
use wcf\data\DatabaseObjectCollection;
use wcf\data\object\type\ObjectTypeCache;
use wcf\data\TCollectionUserProfiles;
use wcf\system\cache\runtime\FileRuntimeCache;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\file\processor\ImageData;
use wcf\system\message\embedded\object\MessageEmbeddedObjectManager;
use wcf\system\WCF;

/**
 * Batch-loads related data for posts that were read together.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends DatabaseObjectCollection<Post>
 */
class PostCollection extends DatabaseObjectCollection
{
    use TCollectionUserProfiles;

    private bool $threadsLoaded = false;

    private bool $embeddedObjectsLoaded = false;

    /**
     * @var array<int, ImageData>
     */
    private array $teaserImages;

    public function getThread(Post $post): Thread
    {
        if (!$this->threadsLoaded) {
            $this->threadsLoaded = true;

            $threadIDs = [];
            foreach ($this->getObjects() as $object) {
                $threadIDs[] = $object->threadID;
            }
            ThreadRuntimeCache::getInstance()->cacheObjectIDs(\array_unique($threadIDs));
        }

        return ThreadRuntimeCache::getInstance()->getObject($post->threadID);
    }

    public function loadEmbeddedObjects(): void
    {
        if ($this->embeddedObjectsLoaded) {
            return;
        }
        $this->embeddedObjectsLoaded = true;

        $posts = [];
        $embeddedObjectIDs = [];
        foreach ($this->getObjects() as $post) {
            $posts[$post->postID] = $post;
            if ($post->hasEmbeddedObjects) {
                $embeddedObjectIDs[] = $post->postID;
            }
        }

        ObjectTypeCache::getInstance()
            ->getObjectTypeByName('com.woltlab.wcf.attachment.objectType', Post::OBJECT_TYPE)
            ->getProcessor()
            ->setCachedObjects($posts);

        if ($embeddedObjectIDs !== []) {
            MessageEmbeddedObjectManager::getInstance()->loadObjects(Post::OBJECT_TYPE, $embeddedObjectIDs);
        }
    }

    /**
     * Returns the first image attachment of the post as a teaser image.
     */
    public function getTeaserImage(Post $post): ?ImageData
    {
        if (!isset($this->teaserImages)) {
            $this->teaserImages = [];

            $conditions = new PreparedStatementConditionBuilder();
            $conditions->add("objectTypeID = ?", [ObjectTypeCache::getInstance()->getObjectTypeIDByName(
                'com.woltlab.wcf.attachment.objectType',
                Post::OBJECT_TYPE
            )]);
            $conditions->add("objectID IN (?)", [$this->getObjectIDs()]);
            $conditions->add(
                "fileID IN (SELECT fileID FROM wcf1_file WHERE mimeType IN (?))",
                [['image/gif', 'image/jpeg', 'image/png', 'image/webp']]
            );

            $sql = "SELECT  objectID, fileID
                    FROM    (
                                SELECT  objectID, fileID,
                                        ROW_NUMBER() OVER (PARTITION BY objectID ORDER BY showOrder) AS row_num
                                FROM    wcf1_attachment
                                {$conditions}
                            ) AS subselect
                    WHERE   subselect.row_num = 1";
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute($conditions->getParameters());
            $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                FileRuntimeCache::getInstance()->cacheObjectID($row['fileID']);
            }
            foreach ($rows as $row) {
                $file = FileRuntimeCache::getInstance()->getObject($row['fileID']);
                $imageData = $file?->getImageData(160, 100);
                if ($imageData !== null) {
                    $this->teaserImages[$row['objectID']] = $imageData;
                }
            }
        }

        return $this->teaserImages[$post->postID] ?? null;
    }
}
