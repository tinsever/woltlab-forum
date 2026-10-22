<?php

namespace forum\system\log\modification;

use forum\data\modification\log\ViewableForumModificationLog;
use forum\data\post\Post;
use forum\system\cache\runtime\PostRuntimeCache;
use wcf\system\log\modification\AbstractExtendedModificationLogHandler;

/**
 * Logs moderative actions on posts, using the thread id as parent object id.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostModificationLogHandler extends AbstractExtendedModificationLogHandler
{
    /**
     * @inheritDoc
     */
    protected $objectTypeName = 'com.tinsever.forum.post';

    public function add(Post $post, string $action, array $additionalData = []): void
    {
        $additionalData['title'] ??= $post->getTitle();

        $this->createLog($action, $post->postID, $post->threadID, $additionalData);
    }

    #[\Override]
    public function getAvailableActions()
    {
        return ['trash', 'restore', 'delete', 'enable', 'disable', 'close', 'open', 'edit', 'move', 'copy', 'merge'];
    }

    #[\Override]
    public function processItems(array $items)
    {
        $postIDs = [];
        foreach ($items as $item) {
            $postIDs[] = $item->objectID;
        }
        PostRuntimeCache::getInstance()->cacheObjectIDs(\array_unique($postIDs));

        $logs = [];
        foreach ($items as $item) {
            $log = new ViewableForumModificationLog($item);
            $post = PostRuntimeCache::getInstance()->getObject($item->objectID);
            if ($post !== null && $post->canRead()) {
                $log->setAffectedObject($post);
            }
            $logs[] = $log;
        }

        return $logs;
    }
}
