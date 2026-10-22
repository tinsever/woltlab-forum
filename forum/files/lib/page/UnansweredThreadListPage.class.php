<?php

namespace forum\page;

/**
 * Shows the 'unanswered' thread list.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class UnansweredThreadListPage extends AbstractThreadListPage
{
    protected string $filter = 'unanswered';
}
