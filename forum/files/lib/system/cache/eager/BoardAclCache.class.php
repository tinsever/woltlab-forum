<?php

namespace forum\system\cache\eager;

use forum\system\cache\eager\data\BoardAclCacheData;
use wcf\system\acl\ACLHandler;
use wcf\system\cache\eager\AbstractEagerCache;
use wcf\system\WCF;

/**
 * Caches the ACL values of all boards.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractEagerCache<BoardAclCacheData>
 */
final class BoardAclCache extends AbstractEagerCache
{
    #[\Override]
    protected function getCacheData(): BoardAclCacheData
    {
        $objectTypeID = ACLHandler::getInstance()->getObjectTypeID('com.tinsever.forum.board');

        $groups = $users = [];
        if ($objectTypeID) {
            $sql = "SELECT      option_to_group.objectID, option_to_group.groupID,
                                option_to_group.optionValue, acl_option.optionName
                    FROM        wcf1_acl_option_to_group option_to_group
                    INNER JOIN  wcf1_acl_option acl_option
                    ON          acl_option.optionID = option_to_group.optionID
                    WHERE       acl_option.objectTypeID = ?";
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute([$objectTypeID]);
            while ($row = $statement->fetchArray()) {
                $groups[$row['objectID']][$row['groupID']][$row['optionName']] = \intval($row['optionValue']);
            }

            $sql = "SELECT      option_to_user.objectID, option_to_user.userID,
                                option_to_user.optionValue, acl_option.optionName
                    FROM        wcf1_acl_option_to_user option_to_user
                    INNER JOIN  wcf1_acl_option acl_option
                    ON          acl_option.optionID = option_to_user.optionID
                    WHERE       acl_option.objectTypeID = ?";
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute([$objectTypeID]);
            while ($row = $statement->fetchArray()) {
                $users[$row['objectID']][$row['userID']][$row['optionName']] = \intval($row['optionValue']);
            }
        }

        return new BoardAclCacheData($groups, $users);
    }

    /**
     * Returns freshly generated data, bypassing the cache of the current request.
     */
    public function getFreshData(): BoardAclCacheData
    {
        return $this->getCacheData();
    }
}
