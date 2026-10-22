<?php

namespace forum\data\board;

use forum\data\thread\Thread;
use wcf\data\user\group\UserGroup;
use wcf\data\user\UserProfile;
use wcf\system\user\collapsible\content\UserCollapsibleContentHandler;

/**
 * Represents a board within the board hierarchy including its children and
 * cumulated statistics.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class BoardNode implements \Countable, \IteratorAggregate
{
    /**
     * @var BoardNode[]
     */
    public array $children = [];

    public int $threads = 0;

    public int $posts = 0;

    public int $unreadThreads = 0;

    /**
     * Threads with the most recent activity in this board and its sub boards.
     *
     * @var list<Thread>
     */
    public array $lastThreads = [];

    /**
     * Users with moderative permissions in this board.
     *
     * @var list<UserProfile>
     */
    public array $moderatorUsers = [];

    /**
     * Groups with moderative permissions in this board.
     *
     * @var list<UserGroup>
     */
    public array $moderatorGroups = [];

    public function __construct(
        public readonly Board $board,
        public readonly int $depth,
        public readonly ?BoardNode $parent = null,
    ) {}

    public function getBoard(): Board
    {
        return $this->board;
    }

    public function hasChildren(): bool
    {
        return $this->children !== [];
    }

    public function isNew(): bool
    {
        return $this->unreadThreads > 0;
    }

    /**
     * Returns true if the active user has collapsed this category.
     */
    public function isCollapsed(): bool
    {
        return UserCollapsibleContentHandler::getInstance()->isCollapsed(
            'com.tinsever.forum.boardCategory',
            (string)$this->board->boardID
        );
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->children);
    }

    #[\Override]
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->children);
    }

    /**
     * Returns the ids of the direct children that are rendered as sub boards.
     *
     * @return Board[]
     */
    public function getSubBoards(): array
    {
        return \array_map(static fn(BoardNode $node) => $node->board, $this->children);
    }

    /**
     * Returns a list containing only this node, used by templates.
     *
     * @return BoardNode[]
     */
    public function asList(): array
    {
        return [$this];
    }
}
