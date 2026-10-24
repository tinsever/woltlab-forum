<?php

/**
 * Database schema of the forum.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */

use wcf\system\database\table\column\DefaultFalseBooleanDatabaseTableColumn;
use wcf\system\database\table\column\DefaultTrueBooleanDatabaseTableColumn;
use wcf\system\database\table\column\EnumDatabaseTableColumn;
use wcf\system\database\table\column\IntDatabaseTableColumn;
use wcf\system\database\table\column\MediumintDatabaseTableColumn;
use wcf\system\database\table\column\MediumtextDatabaseTableColumn;
use wcf\system\database\table\column\NotNullInt10DatabaseTableColumn;
use wcf\system\database\table\column\NotNullVarchar255DatabaseTableColumn;
use wcf\system\database\table\column\ObjectIdDatabaseTableColumn;
use wcf\system\database\table\column\SmallintDatabaseTableColumn;
use wcf\system\database\table\column\TextDatabaseTableColumn;
use wcf\system\database\table\column\TinyintDatabaseTableColumn;
use wcf\system\database\table\column\VarcharDatabaseTableColumn;
use wcf\system\database\table\DatabaseTable;
use wcf\system\database\table\index\DatabaseTableForeignKey;
use wcf\system\database\table\index\DatabaseTableIndex;
use wcf\system\database\table\index\DatabaseTablePrimaryIndex;
use wcf\system\database\table\PartialDatabaseTable;

return [
    PartialDatabaseTable::create('wcf1_user')
        ->columns([
            NotNullInt10DatabaseTableColumn::create('forumPosts')
                ->defaultValue(0),
        ])
        ->indices([
            DatabaseTableIndex::create('forumPosts')
                ->columns(['forumPosts']),
        ]),

    DatabaseTable::create('forum1_board')
        ->columns([
            ObjectIdDatabaseTableColumn::create('boardID'),
            IntDatabaseTableColumn::create('parentID')
                ->length(10),
            SmallintDatabaseTableColumn::create('position')
                ->notNull()
                ->defaultValue(0),
            // 0 = board, 1 = category, 2 = external link
            TinyintDatabaseTableColumn::create('boardType')
                ->length(1)
                ->notNull()
                ->defaultValue(0),
            NotNullVarchar255DatabaseTableColumn::create('title')
                ->defaultValue(''),
            TextDatabaseTableColumn::create('description'),
            DefaultFalseBooleanDatabaseTableColumn::create('descriptionUseHtml'),
            NotNullVarchar255DatabaseTableColumn::create('externalURL')
                ->defaultValue(''),
            VarcharDatabaseTableColumn::create('iconName')
                ->length(80)
                ->notNull()
                ->defaultValue(''),
            NotNullVarchar255DatabaseTableColumn::create('metaDescription')
                ->defaultValue(''),
            NotNullInt10DatabaseTableColumn::create('time')
                ->defaultValue(0),
            IntDatabaseTableColumn::create('styleID')
                ->length(10),
            DefaultFalseBooleanDatabaseTableColumn::create('isClosed'),
            DefaultFalseBooleanDatabaseTableColumn::create('isInvisible'),
            DefaultFalseBooleanDatabaseTableColumn::create('isPrivate'),
            DefaultTrueBooleanDatabaseTableColumn::create('countUserPosts'),
            DefaultTrueBooleanDatabaseTableColumn::create('searchable'),
            DefaultTrueBooleanDatabaseTableColumn::create('ignorable'),
            DefaultTrueBooleanDatabaseTableColumn::create('showSubBoards'),
            DefaultFalseBooleanDatabaseTableColumn::create('enableMarkingAsDone'),
            DefaultFalseBooleanDatabaseTableColumn::create('enableBestAnswer'),
            DefaultFalseBooleanDatabaseTableColumn::create('requireLabels'),
            SmallintDatabaseTableColumn::create('daysPrune')
                ->notNull()
                ->defaultValue(0),
            VarcharDatabaseTableColumn::create('sortField')
                ->length(20)
                ->notNull()
                ->defaultValue(''),
            VarcharDatabaseTableColumn::create('sortOrder')
                ->length(4)
                ->notNull()
                ->defaultValue(''),
            VarcharDatabaseTableColumn::create('postSortOrder')
                ->length(4)
                ->notNull()
                ->defaultValue(''),
            SmallintDatabaseTableColumn::create('threadsPerPage')
                ->notNull()
                ->defaultValue(0),
            SmallintDatabaseTableColumn::create('postsPerPage')
                ->notNull()
                ->defaultValue(0),
            NotNullInt10DatabaseTableColumn::create('clicks')
                ->defaultValue(0),
            NotNullInt10DatabaseTableColumn::create('threads')
                ->defaultValue(0),
            NotNullInt10DatabaseTableColumn::create('posts')
                ->defaultValue(0),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['boardID']),
            DatabaseTableIndex::create('parentID')
                ->columns(['parentID', 'position']),
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['parentID'])
                ->referencedTable('forum1_board')
                ->referencedColumns(['boardID'])
                ->onDelete('SET NULL'),
            DatabaseTableForeignKey::create()
                ->columns(['styleID'])
                ->referencedTable('wcf1_style')
                ->referencedColumns(['styleID'])
                ->onDelete('SET NULL'),
        ]),

    DatabaseTable::create('forum1_thread')
        ->columns([
            ObjectIdDatabaseTableColumn::create('threadID'),
            NotNullInt10DatabaseTableColumn::create('boardID'),
            IntDatabaseTableColumn::create('languageID')
                ->length(10),
            NotNullVarchar255DatabaseTableColumn::create('topic')
                ->defaultValue(''),
            IntDatabaseTableColumn::create('firstPostID')
                ->length(10),
            NotNullInt10DatabaseTableColumn::create('time')
                ->defaultValue(0),
            IntDatabaseTableColumn::create('userID')
                ->length(10),
            NotNullVarchar255DatabaseTableColumn::create('username')
                ->defaultValue(''),
            IntDatabaseTableColumn::create('lastPostID')
                ->length(10),
            NotNullInt10DatabaseTableColumn::create('lastPostTime')
                ->defaultValue(0),
            IntDatabaseTableColumn::create('lastPosterID')
                ->length(10),
            NotNullVarchar255DatabaseTableColumn::create('lastPoster')
                ->defaultValue(''),
            MediumintDatabaseTableColumn::create('replies')
                ->notNull()
                ->defaultValue(0),
            NotNullInt10DatabaseTableColumn::create('views')
                ->defaultValue(0),
            SmallintDatabaseTableColumn::create('attachments')
                ->notNull()
                ->defaultValue(0),
            SmallintDatabaseTableColumn::create('polls')
                ->notNull()
                ->defaultValue(0),
            DefaultFalseBooleanDatabaseTableColumn::create('isAnnouncement'),
            DefaultFalseBooleanDatabaseTableColumn::create('isSticky'),
            DefaultFalseBooleanDatabaseTableColumn::create('isDisabled'),
            DefaultFalseBooleanDatabaseTableColumn::create('isClosed'),
            DefaultFalseBooleanDatabaseTableColumn::create('isDeleted'),
            NotNullInt10DatabaseTableColumn::create('deleteTime')
                ->defaultValue(0),
            IntDatabaseTableColumn::create('movedThreadID')
                ->length(10),
            NotNullInt10DatabaseTableColumn::create('movedTime')
                ->defaultValue(0),
            DefaultFalseBooleanDatabaseTableColumn::create('isDone'),
            IntDatabaseTableColumn::create('bestAnswerPostID')
                ->length(10),
            DefaultFalseBooleanDatabaseTableColumn::create('hasLabels'),
            MediumintDatabaseTableColumn::create('cumulativeLikes')
                ->notNull()
                ->defaultValue(0),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['threadID']),
            DatabaseTableIndex::create('boardID')
                ->columns(['boardID', 'isAnnouncement', 'isSticky', 'lastPostTime', 'isDeleted', 'isDisabled']),
            DatabaseTableIndex::create('boardID_2')
                ->columns(['boardID', 'isDeleted', 'isDisabled', 'movedThreadID']),
            DatabaseTableIndex::create('boardID_3')
                ->columns(['boardID', 'isDeleted', 'isDisabled', 'lastPostTime']),
            DatabaseTableIndex::create('lastPostTime')
                ->columns(['lastPostTime']),
            DatabaseTableIndex::create('time')
                ->columns(['time']),
            DatabaseTableIndex::create('userID')
                ->columns(['userID']),
            DatabaseTableIndex::create('isDeleted')
                ->columns(['isDeleted', 'deleteTime']),
            DatabaseTableIndex::create('movedTime')
                ->columns(['movedThreadID', 'movedTime']),
            DatabaseTableIndex::create('topic')
                ->columns(['topic'])
                ->type(DatabaseTableIndex::FULLTEXT_TYPE),
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['boardID'])
                ->referencedTable('forum1_board')
                ->referencedColumns(['boardID'])
                ->onDelete('CASCADE'),
            DatabaseTableForeignKey::create()
                ->columns(['languageID'])
                ->referencedTable('wcf1_language')
                ->referencedColumns(['languageID'])
                ->onDelete('SET NULL'),
            DatabaseTableForeignKey::create()
                ->columns(['userID'])
                ->referencedTable('wcf1_user')
                ->referencedColumns(['userID'])
                ->onDelete('SET NULL'),
            DatabaseTableForeignKey::create()
                ->columns(['lastPosterID'])
                ->referencedTable('wcf1_user')
                ->referencedColumns(['userID'])
                ->onDelete('SET NULL'),
            DatabaseTableForeignKey::create()
                ->columns(['movedThreadID'])
                ->referencedTable('forum1_thread')
                ->referencedColumns(['threadID'])
                ->onDelete('CASCADE'),
        ]),

    DatabaseTable::create('forum1_post')
        ->columns([
            ObjectIdDatabaseTableColumn::create('postID'),
            NotNullInt10DatabaseTableColumn::create('threadID'),
            IntDatabaseTableColumn::create('userID')
                ->length(10),
            NotNullVarchar255DatabaseTableColumn::create('username')
                ->defaultValue(''),
            NotNullVarchar255DatabaseTableColumn::create('subject')
                ->defaultValue(''),
            MediumtextDatabaseTableColumn::create('message')
                ->notNull(),
            NotNullInt10DatabaseTableColumn::create('time')
                ->defaultValue(0),
            DefaultFalseBooleanDatabaseTableColumn::create('isDeleted'),
            NotNullInt10DatabaseTableColumn::create('deleteTime')
                ->defaultValue(0),
            DefaultFalseBooleanDatabaseTableColumn::create('isDisabled'),
            DefaultFalseBooleanDatabaseTableColumn::create('isClosed'),
            IntDatabaseTableColumn::create('editorID')
                ->length(10),
            NotNullVarchar255DatabaseTableColumn::create('editor')
                ->defaultValue(''),
            NotNullInt10DatabaseTableColumn::create('lastEditTime')
                ->defaultValue(0),
            MediumintDatabaseTableColumn::create('editCount')
                ->notNull()
                ->defaultValue(0),
            TextDatabaseTableColumn::create('editReason'),
            SmallintDatabaseTableColumn::create('attachments')
                ->notNull()
                ->defaultValue(0),
            IntDatabaseTableColumn::create('pollID')
                ->length(10),
            DefaultTrueBooleanDatabaseTableColumn::create('enableHtml'),
            VarcharDatabaseTableColumn::create('ipAddress')
                ->length(39)
                ->notNull()
                ->defaultValue(''),
            MediumintDatabaseTableColumn::create('cumulativeLikes')
                ->notNull()
                ->defaultValue(0),
            DefaultFalseBooleanDatabaseTableColumn::create('hasEmbeddedObjects'),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['postID']),
            DatabaseTableIndex::create('threadID')
                ->columns(['threadID', 'userID']),
            DatabaseTableIndex::create('threadID_2')
                ->columns(['threadID', 'isDeleted', 'isDisabled', 'time']),
            DatabaseTableIndex::create('userID')
                ->columns(['userID', 'time']),
            DatabaseTableIndex::create('time')
                ->columns(['time']),
            DatabaseTableIndex::create('isDeleted')
                ->columns(['isDeleted', 'deleteTime']),
            DatabaseTableIndex::create('ipAddress')
                ->columns(['ipAddress']),
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['threadID'])
                ->referencedTable('forum1_thread')
                ->referencedColumns(['threadID'])
                ->onDelete('CASCADE'),
            DatabaseTableForeignKey::create()
                ->columns(['userID'])
                ->referencedTable('wcf1_user')
                ->referencedColumns(['userID'])
                ->onDelete('SET NULL'),
            DatabaseTableForeignKey::create()
                ->columns(['editorID'])
                ->referencedTable('wcf1_user')
                ->referencedColumns(['userID'])
                ->onDelete('SET NULL'),
            DatabaseTableForeignKey::create()
                ->columns(['pollID'])
                ->referencedTable('wcf1_poll')
                ->referencedColumns(['pollID'])
                ->onDelete('SET NULL'),
        ]),

    PartialDatabaseTable::create('forum1_thread')
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['firstPostID'])
                ->referencedTable('forum1_post')
                ->referencedColumns(['postID'])
                ->onDelete('SET NULL'),
            DatabaseTableForeignKey::create()
                ->columns(['lastPostID'])
                ->referencedTable('forum1_post')
                ->referencedColumns(['postID'])
                ->onDelete('SET NULL'),
            DatabaseTableForeignKey::create()
                ->columns(['bestAnswerPostID'])
                ->referencedTable('forum1_post')
                ->referencedColumns(['postID'])
                ->onDelete('SET NULL'),
        ]),

    DatabaseTable::create('forum1_board_last_post')
        ->columns([
            NotNullInt10DatabaseTableColumn::create('boardID'),
            NotNullInt10DatabaseTableColumn::create('languageID')
                ->defaultValue(0),
            NotNullInt10DatabaseTableColumn::create('threadID'),
        ])
        ->indices([
            DatabaseTableIndex::create('boardID')
                ->columns(['boardID', 'languageID'])
                ->type(DatabaseTableIndex::UNIQUE_TYPE),
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['boardID'])
                ->referencedTable('forum1_board')
                ->referencedColumns(['boardID'])
                ->onDelete('CASCADE'),
            DatabaseTableForeignKey::create()
                ->columns(['threadID'])
                ->referencedTable('forum1_thread')
                ->referencedColumns(['threadID'])
                ->onDelete('CASCADE'),
        ]),

    DatabaseTable::create('forum1_board_user_status')
        ->columns([
            NotNullInt10DatabaseTableColumn::create('boardID'),
            NotNullInt10DatabaseTableColumn::create('userID'),
            EnumDatabaseTableColumn::create('status')
                ->enumValues(['watching', 'ignoring'])
                ->notNull()
                ->defaultValue('watching'),
            DefaultTrueBooleanDatabaseTableColumn::create('enableNotification'),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['boardID', 'userID']),
            DatabaseTableIndex::create('userID')
                ->columns(['userID', 'status']),
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['boardID'])
                ->referencedTable('forum1_board')
                ->referencedColumns(['boardID'])
                ->onDelete('CASCADE'),
            DatabaseTableForeignKey::create()
                ->columns(['userID'])
                ->referencedTable('wcf1_user')
                ->referencedColumns(['userID'])
                ->onDelete('CASCADE'),
        ]),

    DatabaseTable::create('forum1_thread_user_status')
        ->columns([
            NotNullInt10DatabaseTableColumn::create('threadID'),
            NotNullInt10DatabaseTableColumn::create('userID'),
            EnumDatabaseTableColumn::create('status')
                ->enumValues(['watching', 'ignoring'])
                ->notNull()
                ->defaultValue('watching'),
            DefaultTrueBooleanDatabaseTableColumn::create('enableNotification'),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['threadID', 'userID']),
            DatabaseTableIndex::create('userID')
                ->columns(['userID', 'status']),
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['threadID'])
                ->referencedTable('forum1_thread')
                ->referencedColumns(['threadID'])
                ->onDelete('CASCADE'),
            DatabaseTableForeignKey::create()
                ->columns(['userID'])
                ->referencedTable('wcf1_user')
                ->referencedColumns(['userID'])
                ->onDelete('CASCADE'),
        ]),

    DatabaseTable::create('forum1_thread_announcement')
        ->columns([
            NotNullInt10DatabaseTableColumn::create('boardID'),
            NotNullInt10DatabaseTableColumn::create('threadID'),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['boardID', 'threadID']),
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['boardID'])
                ->referencedTable('forum1_board')
                ->referencedColumns(['boardID'])
                ->onDelete('CASCADE'),
            DatabaseTableForeignKey::create()
                ->columns(['threadID'])
                ->referencedTable('forum1_thread')
                ->referencedColumns(['threadID'])
                ->onDelete('CASCADE'),
        ]),

    DatabaseTable::create('forum1_rss_feed')
        ->columns([
            ObjectIdDatabaseTableColumn::create('feedID'),
            NotNullInt10DatabaseTableColumn::create('boardID'),
            NotNullVarchar255DatabaseTableColumn::create('title')
                ->defaultValue(''),
            TextDatabaseTableColumn::create('url'),
            IntDatabaseTableColumn::create('userID')
                ->length(10),
            NotNullVarchar255DatabaseTableColumn::create('username')
                ->defaultValue(''),
            IntDatabaseTableColumn::create('languageID')
                ->length(10),
            NotNullInt10DatabaseTableColumn::create('cycleTime')
                ->defaultValue(1800),
            SmallintDatabaseTableColumn::create('maxResults')
                ->notNull()
                ->defaultValue(0),
            TextDatabaseTableColumn::create('searchKeywords'),
            TextDatabaseTableColumn::create('threadTags'),
            DefaultFalseBooleanDatabaseTableColumn::create('useCategoriesAsTags'),
            DefaultFalseBooleanDatabaseTableColumn::create('closeThread'),
            DefaultFalseBooleanDatabaseTableColumn::create('disableThread'),
            DefaultFalseBooleanDatabaseTableColumn::create('isDisabled'),
            NotNullInt10DatabaseTableColumn::create('lastRun')
                ->defaultValue(0),
            TextDatabaseTableColumn::create('lastError'),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['feedID']),
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['boardID'])
                ->referencedTable('forum1_board')
                ->referencedColumns(['boardID'])
                ->onDelete('CASCADE'),
            DatabaseTableForeignKey::create()
                ->columns(['userID'])
                ->referencedTable('wcf1_user')
                ->referencedColumns(['userID'])
                ->onDelete('SET NULL'),
            DatabaseTableForeignKey::create()
                ->columns(['languageID'])
                ->referencedTable('wcf1_language')
                ->referencedColumns(['languageID'])
                ->onDelete('SET NULL'),
        ]),

    DatabaseTable::create('forum1_rss_feed_log')
        ->columns([
            NotNullInt10DatabaseTableColumn::create('feedID'),
            VarcharDatabaseTableColumn::create('hash')
                ->length(40)
                ->notNull()
                ->defaultValue(''),
            NotNullInt10DatabaseTableColumn::create('time')
                ->defaultValue(0),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['feedID', 'hash']),
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['feedID'])
                ->referencedTable('forum1_rss_feed')
                ->referencedColumns(['feedID'])
                ->onDelete('CASCADE'),
        ]),

    DatabaseTable::create('forum1_article_thread')
        ->columns([
            NotNullInt10DatabaseTableColumn::create('articleContentID'),
            IntDatabaseTableColumn::create('threadID')
                ->length(10),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['articleContentID']),
            DatabaseTableIndex::create('threadID')
                ->columns(['threadID']),
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['articleContentID'])
                ->referencedTable('wcf1_article_content')
                ->referencedColumns(['articleContentID'])
                ->onDelete('CASCADE'),
            DatabaseTableForeignKey::create()
                ->columns(['threadID'])
                ->referencedTable('forum1_thread')
                ->referencedColumns(['threadID'])
                ->onDelete('SET NULL'),
        ]),
];
