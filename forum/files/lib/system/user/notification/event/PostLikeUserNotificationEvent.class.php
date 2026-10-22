<?php

namespace forum\system\user\notification\event;

use forum\system\cache\runtime\PostRuntimeCache;
use wcf\system\user\notification\event\AbstractSharedUserNotificationEvent;
use wcf\system\user\notification\event\TReactionUserNotificationEvent;
use wcf\system\user\notification\object\LikeUserNotificationObject;

/**
 * Notification about reactions to posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @method LikeUserNotificationObject getUserNotificationObject()
 */
class PostLikeUserNotificationEvent extends AbstractSharedUserNotificationEvent
{
    use TReactionUserNotificationEvent;

    /**
     * @inheritDoc
     */
    protected $stackable = true;

    #[\Override]
    protected function prepare()
    {
        PostRuntimeCache::getInstance()->cacheObjectID($this->getUserNotificationObject()->objectID);
    }

    #[\Override]
    public function getTitle(): string
    {
        $count = \count($this->getAuthors());
        if ($count > 1) {
            return $this->getLanguage()->getDynamicVariable('forum.post.like.notification.title.stacked', [
                'count' => $count,
                'timesTriggered' => $this->notification->timesTriggered,
            ]);
        }

        return $this->getLanguage()->get('forum.post.like.notification.title');
    }

    #[\Override]
    public function getMessage()
    {
        $post = PostRuntimeCache::getInstance()->getObject($this->getUserNotificationObject()->objectID);
        $authors = \array_values($this->getAuthors());
        $count = \count($authors);

        if ($count > 1) {
            return $this->getLanguage()->getDynamicVariable('forum.post.like.notification.message.stacked', [
                'authorList' => $this->getLanguage()->getDynamicVariable('wcf.user.notification.stacked.authorList', [
                    'authors' => $authors,
                    'count' => $count,
                    'others' => $count - 1,
                ]),
                'post' => $post,
                'reaction' => $this->getSingleReaction(),
            ]);
        }

        return $this->getLanguage()->getDynamicVariable('forum.post.like.notification.message', [
            'author' => $this->author,
            'post' => $post,
            'reaction' => $this->getSingleReaction(),
        ]);
    }

    #[\Override]
    public function getEmailMessage($notificationType = 'instant')
    {
        // not supported
    }

    #[\Override]
    public function supportsEmailNotification()
    {
        return false;
    }

    #[\Override]
    public function getLink(): string
    {
        return PostRuntimeCache::getInstance()->getObject($this->getUserNotificationObject()->objectID)->getLink();
    }

    #[\Override]
    public function getEventHash()
    {
        return \sha1($this->eventID . '-' . $this->additionalData['objectID']);
    }

    #[\Override]
    public function checkAccess()
    {
        $post = PostRuntimeCache::getInstance()->getObject($this->getUserNotificationObject()->objectID);

        return $post !== null && $post->canRead();
    }
}
