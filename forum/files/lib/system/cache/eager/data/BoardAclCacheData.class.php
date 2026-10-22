<?php

namespace forum\system\cache\eager\data;

/**
 * Cached board ACL values.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class BoardAclCacheData
{
    /**
     * @param array<int, array<int, array<string, int>>> $groups board id => group id => option name => value
     * @param array<int, array<int, array<string, int>>> $users board id => user id => option name => value
     */
    public function __construct(
        public readonly array $groups,
        public readonly array $users,
    ) {}
}
