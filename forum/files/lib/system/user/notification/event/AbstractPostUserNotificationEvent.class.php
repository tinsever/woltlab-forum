<?php

namespace forum\system\user\notification\event;

use forum\system\user\notification\object\PostUserNotificationObject;
use wcf\system\email\Email;
use wcf\system\user\notification\event\AbstractUserNotificationEvent;

/**
 * Base class for post notifications. The language items are derived from the event name:
 * `forum.user.notification.post.<eventName>.(title|message|mail.title)`.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @method PostUserNotificationObject getUserNotificationObject()
 */
abstract class AbstractPostUserNotificationEvent extends AbstractUserNotificationEvent
{
    abstract protected function getEventKey(): string;

    protected function getLanguageItem(string $suffix): string
    {
        return 'forum.user.notification.post.' . $this->getEventKey() . '.' . $suffix;
    }

    #[\Override]
    public function getTitle(): string
    {
        $count = \count($this->getAuthors());
        if ($this->isStackable() && $count > 1) {
            return $this->getLanguage()->getDynamicVariable($this->getLanguageItem('title.stacked'), ['count' => $count]);
        }

        return $this->getLanguage()->get($this->getLanguageItem('title'));
    }

    #[\Override]
    public function getMessage(): string
    {
        $authors = \array_values($this->getAuthors());
        $count = \count($authors);
        $post = $this->getUserNotificationObject();

        if ($this->isStackable() && $count > 1) {
            return $this->getLanguage()->getDynamicVariable($this->getLanguageItem('message.stacked'), [
                'author' => $this->author,
                'authors' => $authors,
                'count' => $count,
                'others' => $count - 1,
                'post' => $post,
                'thread' => $post->getThread(),
                'guestTimesTriggered' => $this->notification->guestTimesTriggered,
            ]);
        }

        return $this->getLanguage()->getDynamicVariable($this->getLanguageItem('message'), [
            'author' => $this->author,
            'post' => $post,
            'thread' => $post->getThread(),
        ]);
    }

    #[\Override]
    public function getEmailTitle(): string
    {
        if ($this->isStackable() && \count($this->getAuthors()) > 1) {
            return parent::getEmailTitle();
        }

        $post = $this->getUserNotificationObject();

        return $this->getLanguage()->getDynamicVariable($this->getLanguageItem('mail.title'), [
            'author' => $this->author,
            'post' => $post,
            'thread' => $post->getThread(),
        ]);
    }

    #[\Override]
    public function getEmailMessage($notificationType = 'instant')
    {
        $post = $this->getUserNotificationObject();
        $messageID = '<com.tinsever.forum.thread/' . $post->threadID . '@' . Email::getHost() . '>';

        return [
            'template' => 'email_notification_forumPost',
            'application' => 'forum',
            'in-reply-to' => [$messageID],
            'references' => [$messageID],
            'variables' => [
                'author' => $this->author,
                'post' => $post,
                'thread' => $post->getThread(),
                'languageItem' => $this->getLanguageItem('mail'),
            ],
        ];
    }

    #[\Override]
    public function getLink(): string
    {
        return $this->getUserNotificationObject()->getLink();
    }

    #[\Override]
    public function checkAccess()
    {
        return $this->getUserNotificationObject()->getDecoratedObject()->canRead();
    }
}
