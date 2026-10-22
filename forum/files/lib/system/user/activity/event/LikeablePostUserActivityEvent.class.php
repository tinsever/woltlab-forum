<?php

namespace forum\system\user\activity\event;

use forum\system\cache\runtime\PostRuntimeCache;
use wcf\system\reaction\ReactionHandler;
use wcf\system\SingletonFactory;
use wcf\system\user\activity\event\IUserActivityEvent;
use wcf\system\WCF;

/**
 * Recent activity event for reactions to posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class LikeablePostUserActivityEvent extends SingletonFactory implements IUserActivityEvent
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

            $reactionType = ReactionHandler::getInstance()->getReactionTypeByID(
                $event->reactionTypeID ?? $event->reactionType->reactionTypeID
            );
            if ($reactionType === null) {
                $event->setIsOrphaned();
                continue;
            }

            if (!$post->canRead()) {
                continue;
            }
            $event->setIsAccessible();

            $event->setTitle(WCF::getLanguage()->getDynamicVariable('forum.post.recentActivity.likedPost', [
                'post' => $post,
                'thread' => $post->getThread(),
                'reactionType' => $reactionType,
                'author' => $event->getUserProfile(),
            ]));
            $event->setLink($post->getLink());
        }
    }
}
