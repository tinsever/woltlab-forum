<?php

namespace forum\system\listView\user;

use wcf\system\listView\user\UserSearchResultsListView;

/**
 * User search results shown as a table.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class UserSearchResultsTableListView extends UserSearchResultsListView
{
    use TMembersTableListView;

    public function __construct(int $searchID)
    {
        parent::__construct($searchID);

        $this->initTableLayout();
    }
}
