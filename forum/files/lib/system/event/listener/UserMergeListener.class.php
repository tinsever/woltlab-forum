<?php

namespace forum\system\event\listener;

use forum\data\post\PostEditor;
use wcf\acp\form\UserMergeForm;
use wcf\system\event\listener\AbstractUserMergeListener;

/**
 * Transfers the forum content of merged users to the destination user.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class UserMergeListener extends AbstractUserMergeListener
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
        [
            'name' => 'forum1_board_user_status',
            'username' => null,
            'ignore' => true,
        ],
        [
            'name' => 'forum1_thread_user_status',
            'username' => null,
            'ignore' => true,
        ],
    ];

    #[\Override]
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        parent::execute($eventObj, $className, $eventName, $parameters);

        \assert($eventObj instanceof UserMergeForm);
        PostEditor::updateUserPostCounters([$eventObj->destinationUserID]);
    }
}
