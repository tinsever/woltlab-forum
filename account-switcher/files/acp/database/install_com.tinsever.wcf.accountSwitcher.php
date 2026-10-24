<?php

/**
 * Database tables of the account switcher.
 *
 * Linked accounts form a group: every account belongs to at most one group,
 * all members of a group can switch to each other.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */

use wcf\system\database\table\column\NotNullInt10DatabaseTableColumn;
use wcf\system\database\table\column\ObjectIdDatabaseTableColumn;
use wcf\system\database\table\DatabaseTable;
use wcf\system\database\table\index\DatabaseTableForeignKey;
use wcf\system\database\table\index\DatabaseTableIndex;
use wcf\system\database\table\index\DatabaseTablePrimaryIndex;

return [
    DatabaseTable::create('wcf1_account_switcher_group')
        ->columns([
            ObjectIdDatabaseTableColumn::create('groupID'),
            NotNullInt10DatabaseTableColumn::create('time'),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['groupID']),
        ]),

    DatabaseTable::create('wcf1_account_switcher_member')
        ->columns([
            NotNullInt10DatabaseTableColumn::create('userID'),
            NotNullInt10DatabaseTableColumn::create('groupID'),
            NotNullInt10DatabaseTableColumn::create('time'),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['userID']),
            DatabaseTableIndex::create('groupID')
                ->columns(['groupID']),
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['userID'])
                ->referencedTable('wcf1_user')
                ->referencedColumns(['userID'])
                ->onDelete('CASCADE'),
            DatabaseTableForeignKey::create()
                ->columns(['groupID'])
                ->referencedTable('wcf1_account_switcher_group')
                ->referencedColumns(['groupID'])
                ->onDelete('CASCADE'),
        ]),
];
