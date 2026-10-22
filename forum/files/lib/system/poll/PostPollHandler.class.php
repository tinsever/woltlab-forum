<?php

namespace forum\system\poll;

use forum\data\post\Post;
use wcf\data\poll\Poll;
use wcf\system\poll\AbstractPollHandler;
use wcf\system\WCF;

/**
 * Poll handler for posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostPollHandler extends AbstractPollHandler
{
    #[\Override]
    public function canStartPublicPoll()
    {
        return (bool)WCF::getSession()->getPermission('user.forum.canStartPublicPoll');
    }

    #[\Override]
    public function getRelatedObject(Poll $poll)
    {
        $sql = "SELECT  postID
                FROM    forum1_post
                WHERE   pollID = ?";
        $statement = WCF::getDB()->prepare($sql, 1);
        $statement->execute([$poll->pollID]);
        $postID = $statement->fetchSingleColumn();
        if (!$postID) {
            return null;
        }

        $post = new Post($postID);

        return $post->canRead() ? $post : null;
    }
}
