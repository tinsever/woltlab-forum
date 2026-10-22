<?php

namespace forum\system\user\activity\event;

use forum\system\cache\runtime\PostRuntimeCache;
use wcf\system\SingletonFactory;
use wcf\system\user\activity\event\IUserActivityEvent;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Recent activity event for replies.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostUserActivityEvent extends SingletonFactory implements IUserActivityEvent
{
    #[\Override]
    public function prepare(array $events)
    {
        $postIDs = [];
        foreach ($events as $event) {
            $postIDs[] = $event->objectID;
        }
        $posts = PostRuntimeCache::getInstance()->getObjects($postIDs);

        foreach ($events as $event) {
            $post = $posts[$event->objectID] ?? null;
            if ($post === null) {
                $event->setIsOrphaned();
                continue;
            }

            if (!$post->canRead() || $post->isDeleted || $post->isDisabled) {
                continue;
            }
            $event->setIsAccessible();

            $event->setTitle(WCF::getLanguage()->getDynamicVariable('forum.post.recentActivity', [
                'post' => $post,
                'thread' => $post->getThread(),
                'author' => $event->getUserProfile(),
            ]));
            $event->setDescription(StringUtil::encodeHTML($post->getTeaser(500)), true);
            $event->setLink($post->getLink());
        }
    }
}
