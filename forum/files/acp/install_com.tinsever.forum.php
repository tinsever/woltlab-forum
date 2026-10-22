<?php

/**
 * Creates the default board structure after the installation.
 *
 * The classes of the application are not available while it is being installed,
 * therefore the boards are created with plain queries.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */

use wcf\system\WCF;

$sql = "INSERT INTO forum1_board
                    (parentID, position, boardType, title, description, time)
        VALUES      (?, ?, ?, ?, ?, ?)";
$statement = WCF::getDB()->prepare($sql);

// category
$statement->execute([null, 1, 1, 'Default Category', '', TIME_NOW]);
$categoryID = WCF::getDB()->getInsertID('forum1_board', 'boardID');

// board
$statement->execute([$categoryID, 1, 0, 'Default Board', '', TIME_NOW]);
