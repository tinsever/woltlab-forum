<?php

namespace forum\system\listView\user;

use wcf\system\WCF;

/**
 * Renders a members list view as table rows instead of user cards.
 *
 * Rows are plain grid rows (no `<table>`), because the list view replaces
 * single items with `replaceWith()` after interactions.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
trait TMembersTableListView
{
    protected function initTableLayout(): void
    {
        $this->setCssClassName('forumMembersTable');
        $this->setContainerCssClassName('forumMembersTable__container');
    }

    #[\Override]
    public function renderItems(): string
    {
        return WCF::getTPL()->render('forum', 'membersTableListItems', [
            'view' => $this,
            'showHeader' => $this->getObjectIDFilter() === null,
        ]);
    }
}
