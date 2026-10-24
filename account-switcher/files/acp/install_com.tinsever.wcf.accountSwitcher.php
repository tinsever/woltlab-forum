<?php

/**
 * Takes over the linked accounts and settings of the forum
 * (com.tinsever.forum up to 1.0.0 Beta 2), which shipped the account switcher
 * before it became a separate package. Runs once, after the installation.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */

use wcf\data\option\OptionEditor;
use wcf\data\user\group\UserGroupEditor;
use wcf\system\WCF;

$tables = WCF::getDB()->getEditor()->getTableNames();
if (\in_array('forum1_account_group', $tables, true) && \in_array('forum1_account_group_member', $tables, true)) {
    WCF::getDB()->prepare(
        "INSERT IGNORE INTO wcf1_account_switcher_group (groupID, time)
         SELECT             groupID, time
         FROM               forum1_account_group"
    )->execute();
    WCF::getDB()->prepare(
        "INSERT IGNORE INTO wcf1_account_switcher_member (userID, groupID, time)
         SELECT             userID, groupID, time
         FROM               forum1_account_group_member"
    )->execute();
}

// Settings are only left if the forum has not been updated yet.
$options = [
    'module_forum_account_switcher' => 'module_account_switcher',
    'forum_account_switcher_enable_search' => 'account_switcher_enable_search',
    'forum_account_switcher_max_accounts' => 'account_switcher_max_accounts',
];
$statement = WCF::getDB()->prepare(
    "UPDATE     wcf1_option new_option
    INNER JOIN  wcf1_option old_option
    ON          old_option.optionName = ?
    SET         new_option.optionValue = old_option.optionValue
    WHERE       new_option.optionName = ?"
);
foreach ($options as $oldName => $newName) {
    $statement->execute([$oldName, $newName]);
}
OptionEditor::resetCache();

WCF::getDB()->prepare(
    "INSERT INTO    wcf1_user_group_option_value (groupID, optionID, optionValue)
     SELECT         old_value.groupID, new_option.optionID, old_value.optionValue
     FROM           wcf1_user_group_option_value old_value
     INNER JOIN     wcf1_user_group_option old_option
     ON             old_option.optionID = old_value.optionID
                    AND old_option.optionName = 'user.profile.canUseAccountSwitcher'
     INNER JOIN     wcf1_user_group_option new_option
     ON             new_option.optionName = 'user.profile.canSwitchAccounts'
     ON DUPLICATE KEY UPDATE optionValue = VALUES(optionValue)"
)->execute();
UserGroupEditor::resetCache();
