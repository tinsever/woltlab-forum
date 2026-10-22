<?php

namespace forum\system\importer;

use wcf\system\importer\AbstractImporter;
use wcf\system\WCF;

/**
 * Imports thread subscriptions.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class WatchedThreadImporter extends AbstractImporter
{
    use TForumImporter;

    #[\Override]
    public function import($oldID, array $data, array $additionalData = [])
    {
        $threadID = $this->getNewID('com.woltlab.wbb.thread', $data['objectID'] ?? null);
        $userID = $this->getNewID('com.woltlab.wcf.user', $data['userID'] ?? null);
        if (!$threadID || !$userID) {
            return 0;
        }

        $sql = "INSERT IGNORE INTO  forum1_thread_user_status
                                    (threadID, userID, status, enableNotification)
                VALUES              (?, ?, ?, ?)";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$threadID, $userID, 'watching', isset($data['notification']) ? ($data['notification'] ? 1 : 0) : 1]);

        return 1;
    }
}
