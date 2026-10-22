<?php

namespace forum\system\worker;

use forum\data\post\Post;
use forum\data\post\PostList;
use wcf\system\search\SearchIndexManager;
use wcf\system\worker\AbstractLinearRebuildDataWorker;

/**
 * Rebuilds the search index of the posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractLinearRebuildDataWorker<PostList>
 */
final class PostSearchIndexRebuildDataWorker extends AbstractLinearRebuildDataWorker
{
    /**
     * @inheritDoc
     */
    protected $objectListClassName = PostList::class;

    /**
     * @inheritDoc
     */
    protected $limit = 1000;

    #[\Override]
    protected function initObjectList()
    {
        parent::initObjectList();

        $this->objectList->sqlSelects = 'thread.topic, thread.firstPostID, thread.languageID';
        $this->objectList->sqlJoins = 'INNER JOIN forum1_thread thread ON thread.threadID = post.threadID';
    }

    #[\Override]
    public function execute()
    {
        parent::execute();

        if (!$this->loopCount) {
            SearchIndexManager::getInstance()->reset(Post::OBJECT_TYPE);
        }

        foreach ($this->objectList as $post) {
            SearchIndexManager::getInstance()->set(
                Post::OBJECT_TYPE,
                $post->postID,
                $post->message,
                $post->firstPostID == $post->postID ? $post->topic : $post->subject,
                $post->time,
                $post->userID,
                $post->username,
                $post->languageID
            );
        }
    }
}
