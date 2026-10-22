<?php

namespace forum\system\search;

use forum\data\board\Board;
use forum\data\board\BoardCache;
use forum\data\board\BoardNodeTree;
use forum\data\post\PostList;
use forum\data\post\SearchResultPost;
use forum\system\board\BoardPermissionHandler;
use forum\system\thread\ThreadVisibility;
use wcf\data\search\ISearchResultObject;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\search\AbstractSearchProvider;
use wcf\system\WCF;
use wcf\util\ArrayUtil;

/**
 * Search provider for posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostSearch extends AbstractSearchProvider
{
    /**
     * @var int[]
     */
    private array $boardIDs = [];

    private int $threadID = 0;

    /**
     * @var SearchResultPost[]
     */
    private array $postCache = [];

    #[\Override]
    public function cacheObjects(array $objectIDs, ?array $additionalData = null): void
    {
        $postList = new PostList();
        $postList->decoratorClassName = SearchResultPost::class;
        $postList->setObjectIDs($objectIDs);
        $postList->readObjects();
        if ($postList->getObjects() !== []) {
            \reset($postList->objects)->getDecoratedObject()->getCollection()->loadEmbeddedObjects();
        }

        foreach ($postList->getObjects() as $post) {
            $this->postCache[$post->postID] = $post;
        }
    }

    #[\Override]
    public function getObject(int $objectID): ?ISearchResultObject
    {
        $post = $this->postCache[$objectID] ?? null;
        if ($post === null || !$post->getDecoratedObject()->canRead()) {
            return null;
        }

        return $post;
    }

    #[\Override]
    public function getTableName(): string
    {
        return 'forum1_post';
    }

    #[\Override]
    public function getIDFieldName(): string
    {
        return $this->getTableName() . '.postID';
    }

    #[\Override]
    public function getSubjectFieldName(): string
    {
        return $this->getTableName() . '.subject';
    }

    #[\Override]
    public function getUsernameFieldName(): string
    {
        return $this->getTableName() . '.username';
    }

    #[\Override]
    public function getTimeFieldName(): string
    {
        return $this->getTableName() . '.time';
    }

    #[\Override]
    public function getJoins(): string
    {
        return "    INNER JOIN  forum1_thread thread
                    ON          thread.threadID = " . $this->getTableName() . ".threadID";
    }

    #[\Override]
    public function getConditionBuilder(array $parameters): PreparedStatementConditionBuilder
    {
        $this->readParameters($parameters);

        $boardIDs = [];
        foreach (BoardPermissionHandler::getInstance()->getAccessibleBoardIDs() as $boardID) {
            if (BoardCache::getInstance()->getBoard($boardID)->searchable) {
                $boardIDs[] = $boardID;
            }
        }
        if ($this->boardIDs !== []) {
            $boardIDs = \array_values(\array_intersect($boardIDs, $this->boardIDs));
        }

        $conditionBuilder = new PreparedStatementConditionBuilder();
        ThreadVisibility::addConditions($conditionBuilder, $boardIDs ?: [0], 'thread', false, false);
        $conditionBuilder->add($this->getTableName() . '.isDeleted = ?', [0]);
        $conditionBuilder->add($this->getTableName() . '.isDisabled = ?', [0]);
        $conditionBuilder->add('thread.movedThreadID IS NULL');

        if ($this->threadID) {
            $conditionBuilder->add('thread.threadID = ?', [$this->threadID]);
        }

        return $conditionBuilder;
    }

    #[\Override]
    public function isAccessible(): bool
    {
        return BoardPermissionHandler::getInstance()->getAccessibleBoardIDs() !== [];
    }

    #[\Override]
    public function getFormTemplateName(): string
    {
        return 'searchPost';
    }

    #[\Override]
    public function getApplication(): string
    {
        return 'forum';
    }

    #[\Override]
    public function assignVariables(): void
    {
        $tree = new BoardNodeTree(0, static fn(Board $board) => $board->isVisible() && $board->canEnter());

        WCF::getTPL()->assign([
            'forumSearchBoards' => $tree->getFlatList(),
            'forumSearchBoardIDs' => \array_map('intval', ArrayUtil::toIntegerArray($_REQUEST['boardIDs'] ?? [])),
            'forumSearchThreadID' => \intval($_REQUEST['threadID'] ?? 0),
        ]);
    }

    #[\Override]
    public function getAdditionalData(): array
    {
        return [
            'boardIDs' => $this->boardIDs,
            'threadID' => $this->threadID,
        ];
    }

    private function readParameters(array $parameters): void
    {
        if (!empty($parameters['boardIDs']) && \is_array($parameters['boardIDs'])) {
            $boardIDs = [];
            foreach (ArrayUtil::toIntegerArray($parameters['boardIDs']) as $boardID) {
                if (BoardCache::getInstance()->getBoard($boardID) !== null) {
                    $boardIDs = \array_merge($boardIDs, BoardCache::getInstance()->getAllChildIDs($boardID));
                }
            }
            $this->boardIDs = \array_unique($boardIDs);
        }

        if (!empty($parameters['threadID'])) {
            $this->threadID = \intval($parameters['threadID']);
        }
    }
}
