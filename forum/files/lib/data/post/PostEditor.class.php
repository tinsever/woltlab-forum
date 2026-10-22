<?php

namespace forum\data\post;

use wcf\data\DatabaseObjectEditor;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

/**
 * Provides functions to edit posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @mixin Post
 * @extends DatabaseObjectEditor<Post>
 */
class PostEditor extends DatabaseObjectEditor
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = Post::class;

    /**
     * Recalculates the forum post counter of the given users.
     *
     * @param int[] $userIDs
     */
    public static function updateUserPostCounters(array $userIDs): void
    {
        $userIDs = \array_unique(\array_filter($userIDs));
        if ($userIDs === []) {
            return;
        }

        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add('user_table.userID IN (?)', [$userIDs]);

        $sql = "UPDATE  wcf1_user user_table
                SET     forumPosts = (
                            SELECT      COUNT(*)
                            FROM        forum1_post post
                            INNER JOIN  forum1_thread thread
                            ON          thread.threadID = post.threadID
                            INNER JOIN  forum1_board board
                            ON          board.boardID = thread.boardID
                            WHERE       post.userID = user_table.userID
                                    AND post.isDeleted = 0
                                    AND post.isDisabled = 0
                                    AND thread.isDeleted = 0
                                    AND thread.isDisabled = 0
                                    AND board.countUserPosts = 1
                        )
                " . $conditions;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditions->getParameters());
    }
}
