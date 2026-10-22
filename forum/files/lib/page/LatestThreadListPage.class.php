<?php

namespace forum\page;

/**
 * Shows the 'latest' thread list.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class LatestThreadListPage extends AbstractThreadListPage
{
    protected string $filter = 'latest';
}
