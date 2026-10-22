<?php

namespace forum\data\thread;

use forum\data\post\Post;
use wcf\data\DatabaseObjectEditor;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;

/**
 * Provides functions to edit threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @mixin Thread
 * @extends DatabaseObjectEditor<Thread>
 */
class ThreadEditor extends DatabaseObjectEditor
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = Thread::class;

    /**
     * Updates the thread after a new visible post has been added.
     */
    public function addPost(Post $post): void
    {
        $this->updateCounters([
            'replies' => 1,
            'attachments' => $post->attachments,
        ]);

        if ($post->time >= $this->lastPostTime) {
            $this->update([
                'lastPostID' => $post->postID,
                'lastPostTime' => $post->time,
                'lastPosterID' => $post->userID,
                'lastPoster' => $post->username,
            ]);
        }
    }

    /**
     * Recalculates the counters and the first and last post of this thread.
     *
     * @return bool false if the thread does not contain any posts
     */
    public function rebuild(): bool
    {
        // first post, regardless of its state
        $sql = "SELECT      postID, time, userID, username
                FROM        forum1_post
                WHERE       threadID = ?
                ORDER BY    time ASC, postID ASC";
        $statement = WCF::getDB()->prepare($sql, 1);
        $statement->execute([$this->threadID]);
        $firstPost = $statement->fetchArray();
        if (!$firstPost) {
            return false;
        }

        $sql = "SELECT  COUNT(*) AS posts, COALESCE(SUM(attachments), 0) AS attachments,
                        SUM(CASE WHEN pollID IS NULL THEN 0 ELSE 1 END) AS polls,
                        COALESCE(SUM(cumulativeLikes), 0) AS cumulativeLikes
                FROM    forum1_post
                WHERE   threadID = ?
                    AND isDeleted = ?
                    AND isDisabled = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$this->threadID, 0, 0]);
        $counts = $statement->fetchArray();

        $sql = "SELECT      postID, time, userID, username
                FROM        forum1_post
                WHERE       threadID = ?
                        AND isDeleted = ?
                        AND isDisabled = ?
                ORDER BY    time DESC, postID DESC";
        $statement = WCF::getDB()->prepare($sql, 1);
        $statement->execute([$this->threadID, 0, 0]);
        $lastPost = $statement->fetchArray() ?: $firstPost;

        // the first post of a thread is not counted as a reply, even if it is hidden
        $replies = \max(0, $counts['posts'] - 1);

        $data = [
            'firstPostID' => $firstPost['postID'],
            'time' => $firstPost['time'],
            'userID' => $firstPost['userID'],
            'username' => $firstPost['username'],
            'lastPostID' => $lastPost['postID'],
            'lastPostTime' => $lastPost['time'],
            'lastPosterID' => $lastPost['userID'],
            'lastPoster' => $lastPost['username'],
            'replies' => $replies,
            'attachments' => $counts['attachments'],
            'polls' => $counts['polls'] ?? 0,
            'cumulativeLikes' => $counts['cumulativeLikes'],
        ];

        // the best answer must be part of the thread
        if ($this->bestAnswerPostID) {
            $sql = "SELECT  COUNT(*)
                    FROM    forum1_post
                    WHERE   postID = ?
                        AND threadID = ?";
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute([$this->bestAnswerPostID, $this->threadID]);
            if (!$statement->fetchSingleColumn()) {
                $data['bestAnswerPostID'] = null;
            }
        }

        $this->update($data);

        // keep moved thread links in sync
        $sql = "UPDATE  forum1_thread
                SET     topic = ?,
                        lastPostTime = ?,
                        replies = ?
                WHERE   movedThreadID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$this->topic, $data['lastPostTime'], $replies, $this->threadID]);

        return true;
    }

    /**
     * Resets the cached unread thread counters of all users.
     */
    public static function resetUnreadCounters(): void
    {
        UserStorageHandler::getInstance()->resetAll('forumUnreadThreads');
    }
}
