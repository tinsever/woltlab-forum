<?php

namespace forum\system\user\activity\event;

use forum\system\cache\runtime\PostRuntimeCache;
use forum\system\cache\runtime\ThreadRuntimeCache;
use wcf\system\SingletonFactory;
use wcf\system\user\activity\event\IUserActivityEvent;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Recent activity event for new threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ThreadUserActivityEvent extends SingletonFactory implements IUserActivityEvent
{
    #[\Override]
    public function prepare(array $events)
    {
        $threadIDs = [];
        foreach ($events as $event) {
            $threadIDs[] = $event->objectID;
        }
        $threads = ThreadRuntimeCache::getInstance()->getObjects($threadIDs);

        $postIDs = [];
        foreach ($threads as $thread) {
            if ($thread !== null && $thread->firstPostID) {
                $postIDs[] = $thread->firstPostID;
            }
        }
        PostRuntimeCache::getInstance()->cacheObjectIDs($postIDs);

        foreach ($events as $event) {
            $thread = $threads[$event->objectID] ?? null;
            if ($thread === null) {
                $event->setIsOrphaned();
                continue;
            }

            if (!$thread->canRead() || $thread->isDeleted || $thread->isDisabled) {
                continue;
            }
            $event->setIsAccessible();

            $event->setTitle(WCF::getLanguage()->getDynamicVariable('forum.thread.recentActivity', [
                'thread' => $thread,
                'author' => $event->getUserProfile(),
            ]));
            $post = $thread->getFirstPost();
            if ($post !== null) {
                $event->setDescription(StringUtil::encodeHTML($post->getTeaser(500)), true);
                $image = $post->getCollection()->getTeaserImage($post);
                if ($image !== null) {
                    $event->setImage($image);
                }
            }
            $event->setLink($thread->getLink());
        }
    }
}
