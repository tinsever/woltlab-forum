<?php

namespace forum\system\cronjob;

use forum\data\post\PostAction;
use forum\data\thread\ThreadAction;
use wcf\data\cronjob\Cronjob;
use wcf\system\cronjob\AbstractCronjob;
use wcf\system\WCF;

/**
 * Removes expired links to moved threads and empties the trash.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ForumCleanUpCronjob extends AbstractCronjob
{
    private const BATCH_SIZE = 250;

    #[\Override]
    public function execute(Cronjob $cronjob)
    {
        parent::execute($cronjob);

        $this->removeExpiredLinks();

        if (\FORUM_TRASH_LIFETIME) {
            $this->emptyTrash(TIME_NOW - \FORUM_TRASH_LIFETIME * 86400);
        }
    }

    private function removeExpiredLinks(): void
    {
        $sql = "SELECT  threadID
                FROM    forum1_thread
                WHERE   movedThreadID IS NOT NULL
                    AND movedTime > ?
                    AND movedTime < ?";
        $statement = WCF::getDB()->prepare($sql, self::BATCH_SIZE);
        $statement->execute([0, TIME_NOW]);
        $threadIDs = $statement->fetchAll(\PDO::FETCH_COLUMN);

        if ($threadIDs !== []) {
            (new ThreadAction($threadIDs, 'delete', ['skipLog' => true]))->executeAction();
        }
    }

    private function emptyTrash(int $deletedBefore): void
    {
        $sql = "SELECT  threadID
                FROM    forum1_thread
                WHERE   isDeleted = ?
                    AND deleteTime < ?";
        $statement = WCF::getDB()->prepare($sql, self::BATCH_SIZE);
        $statement->execute([1, $deletedBefore]);
        $threadIDs = $statement->fetchAll(\PDO::FETCH_COLUMN);
        if ($threadIDs !== []) {
            (new ThreadAction($threadIDs, 'delete'))->executeAction();
        }

        $sql = "SELECT      post.postID
                FROM        forum1_post post
                INNER JOIN  forum1_thread thread
                ON          thread.threadID = post.threadID
                WHERE       post.isDeleted = ?
                        AND post.deleteTime < ?
                        AND post.postID <> thread.firstPostID";
        $statement = WCF::getDB()->prepare($sql, self::BATCH_SIZE);
        $statement->execute([1, $deletedBefore]);
        $postIDs = $statement->fetchAll(\PDO::FETCH_COLUMN);
        if ($postIDs !== []) {
            (new PostAction($postIDs, 'delete'))->executeAction();
        }
    }
}
