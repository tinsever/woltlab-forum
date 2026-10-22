<?php

namespace forum\data\post;

use wcf\data\attachment\GroupedAttachmentList;

/**
 * Represents a list of posts for display, preloading attachments and embedded objects.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class ViewablePostList extends PostList
{
    /**
     * @inheritDoc
     */
    public $sqlOrderBy = 'post.time, post.postID';

    protected ?GroupedAttachmentList $attachmentList = null;

    protected int $maxPostTime = 0;

    #[\Override]
    public function readObjects()
    {
        if ($this->objectIDs === null) {
            $this->readObjectIDs();
        }

        parent::readObjects();

        $attachmentObjectIDs = [];
        foreach ($this->objects as $post) {
            $this->maxPostTime = \max($this->maxPostTime, $post->time);
            if ($post->attachments) {
                $attachmentObjectIDs[] = $post->postID;
            }
        }

        if ($this->objects !== []) {
            \reset($this->objects)->getCollection()->loadEmbeddedObjects();
        }

        if ($attachmentObjectIDs !== []) {
            $this->attachmentList = new GroupedAttachmentList(Post::OBJECT_TYPE);
            $this->attachmentList->getConditionBuilder()->add('attachment.objectID IN (?)', [$attachmentObjectIDs]);
            $this->attachmentList->readObjects();
        }
    }

    public function getAttachmentList(): ?GroupedAttachmentList
    {
        return $this->attachmentList;
    }

    public function getMaxPostTime(): int
    {
        return $this->maxPostTime;
    }
}
