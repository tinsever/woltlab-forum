<?php

namespace forum\data\post;

use wcf\data\like\Like;
use wcf\data\like\object\AbstractLikeObject;
use wcf\data\reaction\object\IReactionObject;
use wcf\system\user\notification\object\LikeUserNotificationObject;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\system\WCF;

/**
 * Likeable (reactable) post.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @mixin Post
 * @extends AbstractLikeObject<Post>
 */
class LikeablePost extends AbstractLikeObject implements IReactionObject
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = Post::class;

    #[\Override]
    public function getTitle(): string
    {
        return $this->getDecoratedObject()->getTitle();
    }

    #[\Override]
    public function getURL()
    {
        return $this->getDecoratedObject()->getLink();
    }

    #[\Override]
    public function getUserID()
    {
        return $this->userID;
    }

    #[\Override]
    public function getObjectID()
    {
        return $this->postID;
    }

    #[\Override]
    public function updateLikeCounter($cumulativeLikes)
    {
        (new PostEditor($this->getDecoratedObject()))->update(['cumulativeLikes' => $cumulativeLikes]);

        // keep the cumulated value of the thread in sync
        $sql = "UPDATE  forum1_thread
                SET     cumulativeLikes = (
                            SELECT  COALESCE(SUM(cumulativeLikes), 0)
                            FROM    forum1_post
                            WHERE   threadID = ?
                                AND isDeleted = 0
                                AND isDisabled = 0
                        )
                WHERE   threadID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$this->threadID, $this->threadID]);
    }

    #[\Override]
    public function getLanguageID()
    {
        return $this->getDecoratedObject()->getThread()->languageID;
    }

    #[\Override]
    public function sendNotification(Like $like)
    {
        $post = $this->getDecoratedObject();
        if ($post->userID && $post->userID != WCF::getUser()->userID) {
            UserNotificationHandler::getInstance()->fireEvent(
                'like',
                'com.tinsever.forum.likeablePost.notification',
                new LikeUserNotificationObject($like),
                [$post->userID],
                ['objectID' => $post->postID],
                $post->threadID
            );
        }
    }
}
