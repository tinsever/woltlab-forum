<?php

namespace forum\system\event\listener;

use wcf\event\listView\user\MembersListViewInitialized;
use wcf\system\box\UserListBoxController;
use wcf\system\cache\tolerant\SortedUserCache;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\listView\ListViewSortField;

/**
 * Allows sorting the members list and user list boxes by the number of forum posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class UserListSortFieldListener implements IParameterizedEventListener
{
    public static function addMembersListSortField(MembersListViewInitialized $event): void
    {
        $event->listView->addAvailableSortField(
            new ListViewSortField('forumPosts', 'wcf.user.sortField.forumPosts')
        );
    }

    /**
     * @param UserListBoxController $eventObj
     */
    #[\Override]
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        if (\in_array('forumPosts', $eventObj->validSortFields, true)) {
            return;
        }

        $eventObj->validSortFields[] = 'forumPosts';
        $eventObj->cacheHandlers['forumPosts'] = static fn(
            int $limit,
            string $sortOrder,
            array $conditions
        ) => new SortedUserCache('forumPosts', $sortOrder, $limit, true, $conditions);
    }
}
