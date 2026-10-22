<?php

namespace forum\system\post;

use forum\data\post\Post;
use forum\data\thread\Thread;
use forum\system\board\BoardStatusHandler;
use forum\system\thread\ThreadStatusHandler;
use forum\system\thread\ThreadVisibility;
use forum\system\user\notification\object\PostUserNotificationObject;
use wcf\data\user\UserList;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\SingletonFactory;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\util\MessageUtil;

/**
 * Sends the notifications for new posts and threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostNotificationHandler extends SingletonFactory
{
    public const OBJECT_TYPE = 'com.tinsever.forum.post.notification';

    /**
     * Notifies thread subscribers, mentioned and quoted users about a new reply.
     */
    public function firePostCreated(Post $post, Thread $thread, ?HtmlInputProcessor $htmlInputProcessor): void
    {
        $notified = $this->fireMentionsAndQuotes($post, $thread, $htmlInputProcessor);

        $recipientIDs = ThreadStatusHandler::getSubscribers($thread->threadID);
        $recipientIDs = \array_diff($recipientIDs, [$post->userID], $notified);
        $recipientIDs = BoardStatusHandler::filterIgnoredUserIDs(\array_values($recipientIDs), $thread->boardID);

        $this->fire('post', $post, $thread, $recipientIDs);
    }

    /**
     * Notifies board subscribers, mentioned and quoted users about a new thread.
     */
    public function fireThreadCreated(Post $post, Thread $thread, ?HtmlInputProcessor $htmlInputProcessor): void
    {
        $notified = $this->fireMentionsAndQuotes($post, $thread, $htmlInputProcessor);

        $recipientIDs = BoardStatusHandler::getSubscribers($thread->boardID);
        $recipientIDs = \array_diff($recipientIDs, [$post->userID], $notified);

        $this->fire('thread', $post, $thread, \array_values($recipientIDs));
    }

    /**
     * @return int[] ids of notified users
     */
    private function fireMentionsAndQuotes(Post $post, Thread $thread, ?HtmlInputProcessor $htmlInputProcessor): array
    {
        if ($htmlInputProcessor === null) {
            $htmlInputProcessor = new HtmlInputProcessor();
            $htmlInputProcessor->processIntermediate($post->message, Post::OBJECT_TYPE, $post->postID);
        }

        $mentionedIDs = \array_diff(MessageUtil::getMentionedUserIDs($htmlInputProcessor), [$post->userID]);
        $mentionedIDs = $this->fire('mention', $post, $thread, \array_values($mentionedIDs));

        $quotedUsernames = MessageUtil::getQuotedUsers($htmlInputProcessor);
        $quotedIDs = [];
        if ($quotedUsernames !== []) {
            $userList = new UserList();
            $userList->getConditionBuilder()->add('user_table.username IN (?)', [\array_unique($quotedUsernames)]);
            $userList->readObjects();
            foreach ($userList as $user) {
                if ($user->userID != $post->userID && !\in_array($user->userID, $mentionedIDs)) {
                    $quotedIDs[] = $user->userID;
                }
            }
        }
        $quotedIDs = $this->fire('quote', $post, $thread, $quotedIDs);

        return \array_merge($mentionedIDs, $quotedIDs);
    }

    /**
     * Fires the notification for all recipients that can read the thread.
     *
     * @param int[] $recipientIDs
     * @return int[] ids of notified users
     */
    private function fire(string $eventName, Post $post, Thread $thread, array $recipientIDs): array
    {
        $recipientIDs = ThreadStatusHandler::filterIgnoredUserIDs(\array_values(\array_unique($recipientIDs)), $thread->threadID);
        if ($recipientIDs === []) {
            return [];
        }

        $userList = new UserList();
        $userList->setObjectIDs($recipientIDs);
        $userList->readObjects();

        $recipientIDs = [];
        foreach ($userList as $user) {
            if (ThreadVisibility::canUserRead($thread, $user)) {
                $recipientIDs[] = $user->userID;
            }
        }

        if ($recipientIDs === []) {
            return [];
        }

        UserNotificationHandler::getInstance()->fireEvent(
            $eventName,
            self::OBJECT_TYPE,
            new PostUserNotificationObject($post),
            $recipientIDs,
            [],
            $thread->threadID,
            $thread->languageID ?: 0
        );

        return $recipientIDs;
    }
}
