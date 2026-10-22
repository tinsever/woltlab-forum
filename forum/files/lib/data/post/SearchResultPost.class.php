<?php

namespace forum\data\post;

use forum\page\ThreadPage;
use wcf\data\DatabaseObjectDecorator;
use wcf\data\search\ISearchResultObject;
use wcf\system\request\LinkHandler;
use wcf\system\search\SearchResultTextParser;

/**
 * Post as a search result.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @mixin Post
 * @extends DatabaseObjectDecorator<Post>
 */
class SearchResultPost extends DatabaseObjectDecorator implements ISearchResultObject
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = Post::class;

    #[\Override]
    public function getFormattedMessage()
    {
        return SearchResultTextParser::getInstance()->parse($this->getDecoratedObject()->getSimplifiedFormattedMessage());
    }

    #[\Override]
    public function getSubject()
    {
        return $this->getDecoratedObject()->getTitle();
    }

    #[\Override]
    public function getLink($query = '')
    {
        if ($query) {
            return LinkHandler::getInstance()->getControllerLink(ThreadPage::class, [
                'object' => $this->getThread(),
                'postID' => $this->postID,
                'highlight' => \urlencode($query),
            ], '#post' . $this->postID);
        }

        return $this->getDecoratedObject()->getLink();
    }

    #[\Override]
    public function getTime()
    {
        return $this->time;
    }

    #[\Override]
    public function getObjectTypeName()
    {
        return Post::OBJECT_TYPE;
    }

    #[\Override]
    public function getContainerTitle()
    {
        return $this->getThread()->getBoard()->getTitle();
    }

    #[\Override]
    public function getContainerLink()
    {
        return $this->getThread()->getBoard()->getLink();
    }

    #[\Override]
    public function getUserProfile()
    {
        return $this->getDecoratedObject()->getUserProfile();
    }
}
