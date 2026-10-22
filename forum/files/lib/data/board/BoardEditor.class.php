<?php

namespace forum\data\board;

use forum\system\board\BoardPermissionHandler;
use forum\system\cache\eager\BoardStructureCache;
use wcf\data\DatabaseObjectEditor;
use wcf\data\IEditableCachedObject;
use wcf\system\WCF;

/**
 * Provides functions to edit boards.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @mixin Board
 * @extends DatabaseObjectEditor<Board>
 */
class BoardEditor extends DatabaseObjectEditor implements IEditableCachedObject
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = Board::class;

    /**
     * Recalculates the thread and post counters and the last posts.
     */
    public function rebuildStats(): void
    {
        $sql = "SELECT  COUNT(*) AS threads, COALESCE(SUM(replies), 0) AS replies
                FROM    forum1_thread
                WHERE   boardID = ?
                    AND isDeleted = ?
                    AND isDisabled = ?
                    AND movedThreadID IS NULL";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$this->boardID, 0, 0]);
        $row = $statement->fetchArray();

        $this->update([
            'threads' => $row['threads'],
            'posts' => $row['threads'] + $row['replies'],
        ]);

        $this->updateLastPost();
    }

    /**
     * Updates the last post information of this board.
     */
    public function updateLastPost(): void
    {
        $sql = "DELETE FROM forum1_board_last_post
                WHERE       boardID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$this->boardID]);

        $sql = "SELECT      threadID, COALESCE(languageID, 0) AS languageID
                FROM        forum1_thread
                WHERE       boardID = ?
                        AND isDeleted = ?
                        AND isDisabled = ?
                        AND movedThreadID IS NULL
                ORDER BY    lastPostTime DESC";
        $statement = WCF::getDB()->prepare($sql, 1);
        $statement->execute([$this->boardID, 0, 0]);
        $globalThreadID = $statement->fetchSingleColumn();
        if (!$globalThreadID) {
            return;
        }

        // one entry per content language, the entry with language id 0 represents all languages
        $sql = "SELECT      COALESCE(languageID, 0) AS languageID, MAX(lastPostTime) AS lastPostTime
                FROM        forum1_thread
                WHERE       boardID = ?
                        AND isDeleted = ?
                        AND isDisabled = ?
                        AND movedThreadID IS NULL
                GROUP BY    languageID";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$this->boardID, 0, 0]);
        $languages = $statement->fetchMap('languageID', 'lastPostTime');

        $insertSql = "INSERT INTO forum1_board_last_post
                                  (boardID, languageID, threadID)
                      VALUES      (?, ?, ?)";
        $insertStatement = WCF::getDB()->prepare($insertSql);
        $insertStatement->execute([$this->boardID, 0, $globalThreadID]);

        if (\count($languages) > 1 || !isset($languages[0])) {
            $sql = "SELECT      threadID
                    FROM        forum1_thread
                    WHERE       boardID = ?
                            AND languageID = ?
                            AND isDeleted = ?
                            AND isDisabled = ?
                            AND movedThreadID IS NULL
                    ORDER BY    lastPostTime DESC";
            $statement = WCF::getDB()->prepare($sql, 1);
            foreach (\array_keys($languages) as $languageID) {
                if (!$languageID) {
                    continue;
                }

                $statement->execute([$this->boardID, $languageID, 0, 0]);
                $threadID = $statement->fetchSingleColumn();
                if ($threadID) {
                    $insertStatement->execute([$this->boardID, $languageID, $threadID]);
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function resetCache()
    {
        (new BoardStructureCache())->rebuild();
        BoardCache::getInstance()->reload();
        BoardPermissionHandler::resetCache();
    }
}
