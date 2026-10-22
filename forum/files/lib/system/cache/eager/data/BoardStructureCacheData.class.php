<?php

namespace forum\system\cache\eager\data;

use forum\data\board\Board;

/**
 * Cached board hierarchy.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class BoardStructureCacheData
{
    /**
     * @param array<int, Board> $boards
     * @param array<int, int[]> $children parent board id (0 = root) => ordered child board ids
     * @param array<int, int[]> $labelGroups board id => label group ids
     */
    public function __construct(
        public readonly array $boards,
        public readonly array $children,
        public readonly array $labelGroups,
    ) {}
}
