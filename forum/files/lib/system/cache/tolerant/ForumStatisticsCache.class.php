<?php

namespace forum\system\cache\tolerant;

use wcf\system\cache\tolerant\AbstractTolerantCache;
use wcf\system\WCF;

/**
 * Caches the global forum statistics.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractTolerantCache<array{threads: int, posts: int, postsPerDay: float, members: int, newestMemberID: int}>
 */
final class ForumStatisticsCache extends AbstractTolerantCache
{
    #[\Override]
    public function getLifetime(): int
    {
        return 600;
    }

    #[\Override]
    protected function rebuildCacheData(): array
    {
        $sql = "SELECT  COALESCE(SUM(threads), 0) AS threads, COALESCE(SUM(posts), 0) AS posts
                FROM    forum1_board";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute();
        $row = $statement->fetchArray();

        $sql = "SELECT  MIN(time)
                FROM    forum1_post";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute();
        $firstPostTime = $statement->fetchSingleColumn() ?: TIME_NOW;
        $days = \max(1, \ceil((TIME_NOW - $firstPostTime) / 86400));

        $sql = "SELECT  COUNT(*)
                FROM    wcf1_user";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute();
        $members = $statement->fetchSingleColumn();

        $sql = "SELECT      userID
                FROM        wcf1_user
                WHERE       activationCode = 0
                        AND banned = 0
                ORDER BY    userID DESC";
        $statement = WCF::getDB()->prepare($sql, 1);
        $statement->execute();
        $newestMemberID = $statement->fetchSingleColumn() ?: 0;

        return [
            'threads' => \intval($row['threads']),
            'posts' => \intval($row['posts']),
            'postsPerDay' => \round($row['posts'] / $days, 2),
            'members' => \intval($members),
            'newestMemberID' => \intval($newestMemberID),
        ];
    }
}
