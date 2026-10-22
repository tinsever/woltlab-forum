<?php

namespace forum\page;

/**
 * Shows the 'watched' thread list.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class WatchedThreadListPage extends AbstractThreadListPage
{
    /**
     * @inheritDoc
     */
    public $loginRequired = true;

    protected string $filter = 'watched';
}
