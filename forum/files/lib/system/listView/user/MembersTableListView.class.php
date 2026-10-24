<?php

namespace forum\system\listView\user;

use wcf\system\listView\user\MembersListView;

/**
 * Members list shown as a table.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class MembersTableListView extends MembersListView
{
    use TMembersTableListView;

    public function __construct()
    {
        parent::__construct();

        $this->initTableLayout();
    }
}
