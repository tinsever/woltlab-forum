<?php

namespace forum\page;

/**
 * Shows the 'undone' thread list.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class UndoneThreadListPage extends AbstractThreadListPage
{
    protected string $filter = 'undone';
}
