<?php

namespace forum\system\event\listener;

use wcf\system\event\listener\AbstractUserActionRenameListener;

/**
 * Updates the stored usernames after a user has been renamed.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class UserActionRenameListener extends AbstractUserActionRenameListener
{
    /**
     * @inheritDoc
     */
    protected $databaseTables = [
        'forum1_thread',
        [
            'name' => 'forum1_thread',
            'userID' => 'lastPosterID',
            'username' => 'lastPoster',
        ],
        'forum1_post',
        [
            'name' => 'forum1_post',
            'userID' => 'editorID',
            'username' => 'editor',
        ],
        'forum1_rss_feed',
    ];
}
