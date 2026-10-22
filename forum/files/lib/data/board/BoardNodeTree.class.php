<?php

namespace forum\data\board;

/**
 * Builds a tree of board nodes.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class BoardNodeTree
{
    /**
     * @var BoardNode[]
     */
    private array $nodes = [];

    /**
     * @param int $parentID id of the board whose children are the root nodes, 0 for the top-level
     * @param ?callable(Board): bool $filter boards (and their descendants) are excluded if this returns false
     * @param int $maxDepth maximum depth of the tree, -1 for unlimited
     * @param int[] $excludedBoardIDs boards (and their descendants) to exclude
     */
    public function __construct(
        int $parentID = 0,
        ?callable $filter = null,
        int $maxDepth = -1,
        array $excludedBoardIDs = [],
    ) {
        $this->nodes = $this->buildNodes($parentID, null, 0, $filter, $maxDepth, $excludedBoardIDs);
    }

    /**
     * @return BoardNode[]
     */
    private function buildNodes(
        int $parentID,
        ?BoardNode $parent,
        int $depth,
        ?callable $filter,
        int $maxDepth,
        array $excludedBoardIDs
    ): array {
        if ($maxDepth !== -1 && $depth > $maxDepth) {
            return [];
        }

        $nodes = [];
        foreach (BoardCache::getInstance()->getChildren($parentID) as $board) {
            if (\in_array($board->boardID, $excludedBoardIDs)) {
                continue;
            }
            if ($filter !== null && !$filter($board)) {
                continue;
            }

            $node = new BoardNode($board, $depth, $parent);
            $node->children = $this->buildNodes($board->boardID, $node, $depth + 1, $filter, $maxDepth, $excludedBoardIDs);
            $nodes[] = $node;
        }

        return $nodes;
    }

    /**
     * @return BoardNode[]
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    /**
     * Returns all nodes in depth-first order.
     *
     * @return BoardNode[]
     */
    public function getFlatList(): array
    {
        $list = [];
        $walk = static function (array $nodes) use (&$walk, &$list) {
            foreach ($nodes as $node) {
                $list[] = $node;
                $walk($node->children);
            }
        };
        $walk($this->nodes);

        return $list;
    }

    /**
     * Returns the boards as options for select fields, indented by depth.
     *
     * @return array<int, string>
     */
    public function getSelectOptions(): array
    {
        $options = [];
        foreach ($this->getFlatList() as $node) {
            $options[$node->board->boardID] = \str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $node->depth)
                . \htmlspecialchars($node->board->getTitle());
        }

        return $options;
    }

    /**
     * Returns the boards as nested options for form builder selection fields.
     *
     * @param ?callable(Board): bool $isSelectable defaults to "is a board"
     * @return list<array{depth: int, label: string, value: int, isSelectable: bool}>
     */
    public function getNestedOptions(?callable $isSelectable = null): array
    {
        $isSelectable ??= static fn(Board $board) => $board->isBoard();

        $options = [];
        foreach ($this->getFlatList() as $node) {
            $options[] = [
                'depth' => $node->depth,
                'label' => $node->board->getTitle(),
                'value' => $node->board->boardID,
                'isSelectable' => (bool)$isSelectable($node->board),
            ];
        }

        return $options;
    }
}
