<?php

/**
 * Runs the real WBB exporter, ImportWorker and rebuild workers against a seeded
 * Core 5.2 schema. Forum source tables model the fields read by WBB4xExporter;
 * this is not a copy of the proprietary WoltLab Forum distribution.
 * Use a disposable local target named importcheck*. Database and file writes
 * remain there; restore its baseline before repeating this test.
 * Usage: php scripts/check-import.php CORE_DIR CORE_52_INSTALL_SQL
 */
if (PHP_SAPI !== 'cli' || count($argv) < 3) {
    exit(1);
}
$core = realpath($argv[1]);
if (!$core || !str_starts_with($core, realpath(__DIR__ . '/../dev/www') . '/') || !str_starts_with(basename($core), 'importcheck')) {
    throw new RuntimeException('Use a disposable target under dev/www.');
}
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/' . basename($core) . '/';
$_SERVER['SCRIPT_NAME'] = $_SERVER['REQUEST_URI'] . 'index.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require $core . '/global.php';
require $core . '/config.inc.php';

use \wcf\system\WCF;
use \wcf\system\importer\ImportHandler;
use \wcf\data\object\type\ObjectTypeCache;

function check(bool $condition, string $message): void
{
    $GLOBALS['checks']++;
    if (!$condition) {
        $GLOBALS['failures'][] = $message;
        echo 'FAIL: ' . $message . "\n";
    }
}

function insertRow(PDO $db, string $table, array $data): void
{
    $columns = implode(', ', array_map(static fn ($key) => '`' . $key . '`', array_keys($data)));
    $db->prepare('INSERT INTO ' . $table . ' (' . $columns . ') VALUES (' . implode(', ', array_fill(0, count($data), '?')) . ')')->execute(array_values($data));
}

function targetRow(string $table, string $key, int $id): array
{
    $statement = WCF::getDB()->prepare('SELECT * FROM ' . $table . ' WHERE ' . $key . ' = ?');
    $statement->execute([$id]);
    return $statement->fetchArray() ?: [];
}

function runWorker(string $class, array $parameters = []): void
{
    for ($loop = 0; $loop < 100; $loop++) {
        $worker = new $class($parameters);
        $worker->setLoopCount($loop);
        $worker->validate();
        $progress = $worker->getProgress();
        $worker->execute();
        $worker->finalize();
        $parameters = $worker->getParameters();
        if ($progress >= 100) {
            echo 'OK: ' . ($parameters['objectType'] ?? $class) . ' (' . ($loop + 1) . " batches)\n";
            return;
        }
    }
    throw new RuntimeException('Worker did not finish: ' . $class);
}

function removeFixture(string $directory): void
{
    foreach (new FilesystemIterator($directory) as $entry) {
        if ($entry->isDir() && !$entry->isLink()) {
            removeFixture($entry->getPathname());
        } else {
            unlink($entry->getPathname());
        }
    }
    rmdir($directory);
}

$failures = [];
$checks = 0;
$suffix = bin2hex(random_bytes(4));
$sourceName = 'wcf_import52_' . $suffix;
$sourceRoot = __DIR__ . '/../build/import-source-' . $suffix;
$sourcePath = $sourceRoot . '/suite';
mkdir($sourcePath . '/lib', 0777, true);
file_put_contents($sourcePath . '/lib/core.functions.php', '<?php');
$pdo = new PDO('mysql:host=' . $dbHost . ';port=' . ($dbPort ?: 3306), $dbUser, $dbPassword, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE DATABASE `' . $sourceName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec('USE `' . $sourceName . '`');
// Fixture columns with required application-defined values are explicitly seeded below.
$pdo->exec("SET SESSION sql_mode = ''");
$db = WCF::getDB();
$exitCode = 0;
try {
    preg_match_all('/CREATE TABLE wcf1_[a-z_]+\s*\(.*?\n\);/s', file_get_contents($argv[2]), $matches);
    check(count($matches[0]) > 100, 'Original Core 5.2 schema was loaded');
    foreach ($matches[0] as $sql) {
        $pdo->exec(str_replace('wcf1_', 'wcf7_', $sql));
    }
    foreach (['board', 'thread', 'post', 'thread_announcement'] as $table) {
        $pdo->exec('CREATE TABLE wbb7_' . $table . ' LIKE `' . $dbName . '`.forum1_' . $table);
    }
    $pdo->exec('ALTER TABLE wbb7_board ADD searchableForSimilarThreads TINYINT NOT NULL DEFAULT 1');

    foreach (['com.woltlab.wcf', 'com.woltlab.wbb'] as $package) {
        insertRow($pdo, 'wcf7_package', ['package' => $package, 'packageVersion' => '5.2.0', 'packageDir' => $package === 'com.woltlab.wbb' ? '../' : '']);
    }
    insertRow($pdo, 'wcf7_language', ['languageID' => 7, 'languageCode' => 'de', 'languageName' => 'Deutsch']);
    insertRow($pdo, 'wcf7_language', ['languageID' => 99, 'languageCode' => 'en', 'languageName' => 'English']);
    foreach ([1 => 1, 2 => 2, 3 => 3, 6 => 4] as $id => $type) {
        insertRow($pdo, 'wcf7_user_group', ['groupID' => $id, 'groupType' => $type, 'groupName' => $id === 6 ? 'Import team' : 'Group ' . $id]);
    }
    $password = (new \wcf\system\user\authentication\password\algorithm\DoubleBcrypt())->hash('import-password-52');
    foreach ([1 => 'ImportAlice52', 2 => 'ImportBob52', 101 => 'ImportSparse52'] as $id => $name) {
        insertRow($pdo, 'wcf7_user', ['userID' => $id, 'username' => $name, 'email' => strtolower($name) . '@example.test', 'password' => $password, 'registrationDate' => 1600000000, 'signature' => '<p>Ältere Signatur</p>', 'signatureEnableHtml' => 1, 'languageID' => 7]);
        insertRow($pdo, 'wcf7_user_option_value', ['userID' => $id]);
        insertRow($pdo, 'wcf7_user_to_group', ['userID' => $id, 'groupID' => 3]);
        insertRow($pdo, 'wcf7_user_to_language', ['userID' => $id, 'languageID' => 7]);
    }
    insertRow($pdo, 'wcf7_user_to_group', ['userID' => 1, 'groupID' => 6]);
    insertRow($pdo, 'wcf7_user_rank', ['groupID' => 6, 'rankTitle' => 'Imported rank']);
    insertRow($pdo, 'wcf7_user_follow', ['userID' => 2, 'followUserID' => 1, 'time' => 1600000010]);

    $objectTypes = [];
    foreach ([
        ['com.woltlab.wcf.label.objectType', 'com.woltlab.wbb.board'],
        ['com.woltlab.wcf.label.object', 'com.woltlab.wbb.thread'],
        ['com.woltlab.wcf.tagging.taggableObject', 'com.woltlab.wbb.thread'],
        ['com.woltlab.wcf.attachment.objectType', 'com.woltlab.wbb.post'],
        ['com.woltlab.wcf.poll', 'com.woltlab.wbb.post'],
        ['com.woltlab.wcf.like.likeableObject', 'com.woltlab.wbb.likeablePost'],
        ['com.woltlab.wcf.user.objectWatch', 'com.woltlab.wbb.thread'],
        ['com.woltlab.wcf.acl', 'com.woltlab.wbb.board'],
        ['com.woltlab.wcf.comment.commentableContent', 'com.woltlab.wcf.user.profileComment'],
    ] as [$definition, $type]) {
        insertRow($pdo, 'wcf7_object_type_definition', ['definitionName' => $definition, 'packageID' => 1]);
        $definitionID = (int)$pdo->lastInsertId();
        insertRow($pdo, 'wcf7_object_type', ['definitionID' => $definitionID, 'packageID' => 2, 'objectType' => $type]);
        $objectTypes[$definition] = (int)$pdo->lastInsertId();
    }

    // Existing target IDs deliberately collide with old user, thread, post and attachment IDs.
    $sentinelBoard = forum\data\board\BoardEditor::create(['title' => 'Existing target board']);
    $sentinelThread = forum\data\thread\ThreadEditor::create(['boardID' => $sentinelBoard->boardID, 'topic' => 'Existing target thread', 'threadID' => 1]);
    forum\data\post\PostEditor::create(['threadID' => $sentinelThread->threadID, 'postID' => 1, 'message' => '<p>Existing target post</p>']);
    insertRow($pdo, 'wbb7_board', ['boardID' => 1, 'title' => 'Kategorie', 'boardType' => 1, 'parentID' => null]);
    insertRow($pdo, 'wbb7_board', ['boardID' => 2, 'title' => 'wbb.board.board2', 'description' => 'wbb.board.boardDescription2', 'parentID' => 1, 'enableBestAnswer' => 1]);
    insertRow($pdo, 'wbb7_board', ['boardID' => 3, 'title' => 'Zweites Forum', 'parentID' => 1]);
    insertRow($pdo, 'wbb7_board', ['boardID' => 4, 'title' => 'Externer Link', 'boardType' => 2, 'externalURL' => 'https://example.test/']);
    foreach (['wbb.board.board2' => 'Älteres Forum', 'wbb.board.boardDescription2' => 'Übernommene Beschreibung'] as $item => $value) {
        insertRow($pdo, 'wcf7_language_item', ['languageID' => 7, 'languageItem' => $item, 'languageItemValue' => $value, 'languageCategoryID' => 1]);
        insertRow($pdo, 'wcf7_language_item', ['languageID' => 99, 'languageItem' => $item, 'languageItemValue' => 'English ' . $value, 'languageCategoryID' => 1]);
    }
    $pdo->exec("UPDATE wcf7_user_group SET groupName = 'wcf.acp.group.group6', groupDescription = 'wcf.acp.group.groupDescription6' WHERE groupID = 6");
    foreach (['wcf.acp.group.group6' => 'Übernommenes Team', 'wcf.acp.group.groupDescription6' => 'Beschreibung des Teams'] as $item => $value) {
        insertRow($pdo, 'wcf7_language_item', ['languageID' => 7, 'languageItem' => $item, 'languageItemValue' => $value, 'languageCategoryID' => 1]);
    }
    foreach ([1 => 'Älteres Thema', 2 => 'Ankündigung', 3 => 'Gelöschtes Thema', 4 => 'Verschobener Verweis', 201 => 'Weiteres Thema nach ID-Lücke'] as $id => $topic) {
        insertRow($pdo, 'wbb7_thread', ['threadID' => $id, 'boardID' => 2, 'topic' => $topic, 'userID' => 1, 'username' => 'ImportAlice52', 'languageID' => 7, 'time' => 1600000000, 'lastPostTime' => 1600000300, 'hasLabels' => $id === 1 ? 1 : 0, 'isAnnouncement' => $id === 2 ? 1 : 0, 'isDeleted' => $id === 3 ? 1 : 0, 'movedThreadID' => $id === 4 ? 1 : null]);
    }
    insertRow($pdo, 'wbb7_thread_announcement', ['threadID' => 2, 'boardID' => 3]);
    foreach ([
        [1, 1, 1, '<p>Übernommener Beitrag mit <strong>Formatierung</strong> [attach]1[/attach]</p>', 0, 0],
        [2, 1, 2, '<p>Antwort von Bob</p>', 0, 0],
        [3, 1, null, '[b]Gastbeitrag[/b]', 0, 0],
        [4, 1, 1, '<p>Moderierter Beitrag</p>', 1, 0],
        [5, 1, 1, '<p>Gelöschter Beitrag</p>', 0, 1],
        [6, 2, 1, '<p>Ankündigungstext</p>', 0, 0],
        [7, 3, 1, '<p>Text im gelöschten Thema</p>', 0, 0],
        [1001, 201, 101, '<p>Beitrag nach einer ID-Lücke</p>', 0, 0],
    ] as [$id, $thread, $user, $message, $disabled, $deleted]) {
        insertRow($pdo, 'wbb7_post', ['postID' => $id, 'threadID' => $thread, 'userID' => $user, 'username' => [1 => 'ImportAlice52', 2 => 'ImportBob52', 101 => 'ImportSparse52'][$user ?? 0] ?? 'Gast', 'message' => $message, 'enableHtml' => $id === 3 ? 0 : 1, 'time' => 1600000000 + $id * 10, 'isDisabled' => $disabled, 'isDeleted' => $deleted, 'editorID' => $id === 2 ? 1 : null, 'editor' => $id === 2 ? 'ImportAlice52' : '', 'editCount' => $id === 2 ? 1 : 0, 'lastEditTime' => $id === 2 ? 1600000400 : 0]);
    }

    insertRow($pdo, 'wcf7_label_group', ['groupID' => 1, 'groupName' => 'Status', 'forceSelection' => 1]);
    insertRow($pdo, 'wcf7_label', ['labelID' => 1, 'groupID' => 1, 'label' => 'Erledigt', 'cssClassName' => 'green', 'showOrder' => 3]);
    insertRow($pdo, 'wcf7_label_group_to_object', ['groupID' => 1, 'objectTypeID' => $objectTypes['com.woltlab.wcf.label.objectType'], 'objectID' => 2]);
    insertRow($pdo, 'wcf7_label_object', ['labelID' => 1, 'objectTypeID' => $objectTypes['com.woltlab.wcf.label.object'], 'objectID' => 1]);
    insertRow($pdo, 'wcf7_tag', ['tagID' => 1, 'name' => 'Übernahme', 'languageID' => 7]);
    insertRow($pdo, 'wcf7_tag_to_object', ['tagID' => 1, 'objectTypeID' => $objectTypes['com.woltlab.wcf.tagging.taggableObject'], 'objectID' => 1, 'languageID' => 7]);
    foreach ([1 => 0, 2 => 1] as $user => $notification) {
        insertRow($pdo, 'wcf7_user_object_watch', ['objectTypeID' => $objectTypes['com.woltlab.wcf.user.objectWatch'], 'objectID' => 1, 'userID' => $user, 'notification' => $notification]);
    }
    insertRow($pdo, 'wcf7_poll', ['pollID' => 1, 'objectTypeID' => $objectTypes['com.woltlab.wcf.poll'], 'objectID' => 1, 'question' => 'Klappt die Übernahme?', 'votes' => 1]);
    insertRow($pdo, 'wcf7_poll_option', ['optionID' => 1, 'pollID' => 1, 'optionValue' => 'Ja', 'votes' => 1]);
    insertRow($pdo, 'wcf7_poll_option', ['optionID' => 2, 'pollID' => 1, 'optionValue' => 'Nein']);
    insertRow($pdo, 'wcf7_poll_option_vote', ['pollID' => 1, 'optionID' => 1, 'userID' => 2]);
    insertRow($pdo, 'wcf7_reaction_type', ['reactionTypeID' => 2, 'title' => 'Danke', 'iconFile' => 'thanks.svg']);
    insertRow($pdo, 'wcf7_like', ['objectID' => 1, 'objectTypeID' => $objectTypes['com.woltlab.wcf.like.likeableObject'], 'objectUserID' => 1, 'userID' => 2, 'time' => 1600000040, 'likeValue' => 1, 'reactionTypeID' => 2]);
    mkdir($sourcePath . '/images/reaction', 0777, true);
    file_put_contents($sourcePath . '/images/reaction/import-fixture.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path d="M0 0h16v16H0z"/></svg>');
    insertRow($pdo, 'wcf7_reaction_type', ['reactionTypeID' => 9, 'title' => 'Applaus', 'iconFile' => 'import-fixture.svg', 'showOrder' => 7]);
    insertRow($pdo, 'wcf7_like', ['objectID' => 2, 'objectTypeID' => $objectTypes['com.woltlab.wcf.like.likeableObject'], 'objectUserID' => 2, 'userID' => 1, 'time' => 1600000050, 'likeValue' => 1, 'reactionTypeID' => 9]);
    insertRow($pdo, 'wcf7_acl_option', ['optionID' => 1, 'packageID' => 2, 'objectTypeID' => $objectTypes['com.woltlab.wcf.acl'], 'optionName' => 'canReadThread', 'categoryName' => 'user.board']);
    insertRow($pdo, 'wcf7_acl_option_to_group', ['optionID' => 1, 'objectID' => 2, 'groupID' => 6, 'optionValue' => 0]);
    insertRow($pdo, 'wcf7_acl_option_to_user', ['optionID' => 1, 'objectID' => 2, 'userID' => 2, 'optionValue' => 1]);

    $contents = "Älterer Anhang\n";
    $hash = sha1($contents);
    mkdir($sourcePath . '/attachments/' . substr($hash, 0, 2), 0777, true);
    file_put_contents($sourcePath . '/attachments/' . substr($hash, 0, 2) . '/1-' . $hash . '.bin', $contents);
    insertRow($pdo, 'wcf7_attachment', ['attachmentID' => 1, 'objectTypeID' => $objectTypes['com.woltlab.wcf.attachment.objectType'], 'objectID' => 1, 'userID' => 1, 'filename' => 'anhang.txt', 'fileHash' => $hash]);
    $attachmentType = ObjectTypeCache::getInstance()->getObjectTypeIDByName('com.woltlab.wcf.attachment.objectType', 'com.tinsever.forum.post');
    $existingAttachment = \wcf\data\attachment\AttachmentEditor::create(['attachmentID' => 1, 'objectTypeID' => $attachmentType, 'objectID' => 1, 'filename' => 'existing.txt', 'fileHash' => sha1('existing')]);
    $image = imagecreatetruecolor(64, 64);
    ob_start();
    imagepng($image);
    $avatarData = ob_get_clean();
    $avatarHash = sha1($avatarData);
    mkdir($sourcePath . '/images/avatars/' . substr($avatarHash, 0, 2), 0777, true);
    file_put_contents($sourcePath . '/images/avatars/' . substr($avatarHash, 0, 2) . '/1-' . $avatarHash . '.png', $avatarData);
    insertRow($pdo, 'wcf7_user_avatar', ['avatarID' => 1, 'userID' => 1, 'avatarName' => 'avatar.png', 'fileHash' => $avatarHash, 'avatarExtension' => 'png']);
    $pdo->exec('UPDATE wcf7_user SET avatarID = 1 WHERE userID = 1');
    mkdir($sourcePath . '/attachments/' . substr($avatarHash, 0, 2), 0777, true);
    file_put_contents($sourcePath . '/attachments/' . substr($avatarHash, 0, 2) . '/2-' . $avatarHash . '.bin', $avatarData);
    insertRow($pdo, 'wcf7_attachment', ['attachmentID' => 2, 'objectTypeID' => $objectTypes['com.woltlab.wcf.attachment.objectType'], 'objectID' => 2, 'userID' => 2, 'filename' => 'bild.png', 'fileHash' => $avatarHash]);
    // Post 1 embeds three attachments. The colliding target attachment 1 used
    // to push the following IDs into the range of later source attachments.
    $extraImages = [];
    foreach ([3 => ['foto.jpg', 'imagejpeg', 40], 4 => ['animation.gif', 'imagegif', 24]] as $id => [$name, $writer, $size]) {
        $picture = imagecreatetruecolor($size, $size);
        imagefill($picture, 0, 0, imagecolorallocate($picture, 10 * $id, 120, 200));
        ob_start();
        $writer($picture);
        $bytes = ob_get_clean();
        $pictureHash = sha1($bytes);
        @mkdir($sourcePath . '/attachments/' . substr($pictureHash, 0, 2), 0777, true);
        file_put_contents($sourcePath . '/attachments/' . substr($pictureHash, 0, 2) . '/' . $id . '-' . $pictureHash . '.bin', $bytes);
        insertRow($pdo, 'wcf7_attachment', ['attachmentID' => $id, 'objectTypeID' => $objectTypes['com.woltlab.wcf.attachment.objectType'], 'objectID' => 1, 'userID' => 1, 'filename' => $name, 'fileHash' => $pictureHash, 'showOrder' => $id]);
        $extraImages[$name] = $size;
    }
    $pdo->prepare('UPDATE wbb7_post SET message = ? WHERE postID = 1')->execute(['<p>Übernommener Beitrag mit <strong>Formatierung</strong> [attach]1[/attach] [attach]3[/attach] <woltlab-metacode data-name="attach" data-attributes="' . base64_encode(json_encode([4, 'none', true])) . '"></woltlab-metacode> <woltlab-metacode data-name="attach" data-attributes="' . base64_encode(json_encode([1, 'none', false])) . '"></woltlab-metacode></p>']);
    $jpegAvatar = imagecreatetruecolor(48, 48);
    ob_start();
    imagejpeg($jpegAvatar);
    $jpegAvatarData = ob_get_clean();
    $jpegAvatarHash = sha1($jpegAvatarData);
    @mkdir($sourcePath . '/images/avatars/' . substr($jpegAvatarHash, 0, 2), 0777, true);
    file_put_contents($sourcePath . '/images/avatars/' . substr($jpegAvatarHash, 0, 2) . '/2-' . $jpegAvatarHash . '.jpg', $jpegAvatarData);
    insertRow($pdo, 'wcf7_user_avatar', ['avatarID' => 2, 'userID' => 2, 'avatarName' => 'avatar.jpg', 'fileHash' => $jpegAvatarHash, 'avatarExtension' => 'jpg']);
    $pdo->exec('UPDATE wcf7_user SET avatarID = 2 WHERE userID = 2');
    $pdo->prepare('UPDATE wbb7_post SET message = ? WHERE postID = 2')->execute(['<p>Antwort von Bob <woltlab-metacode data-name="attach" data-attributes="' . base64_encode(json_encode([2])) . '"></woltlab-metacode></p>']);

    $sourceChecksums = $pdo->query('CHECKSUM TABLE wcf7_user, wcf7_label, wbb7_board, wbb7_thread, wbb7_post, wcf7_attachment')->fetchAll(PDO::FETCH_ASSOC);
    WCF::getSession()->changeUser(new \wcf\data\user\User(1));
    $exporter = new \wcf\system\exporter\WBB4xExporter();
    $exporter->setData($dbHost . ($dbPort ? ':' . $dbPort : ''), $dbUser, $dbPassword, $sourceName, 'wcf7_', $sourcePath, []);
    $exporter->init();
    $selected = ['com.woltlab.wcf.user', 'com.woltlab.wcf.user.group', 'com.woltlab.wcf.user.rank', 'com.woltlab.wcf.user.follower', 'com.woltlab.wcf.user.avatar', 'com.woltlab.wbb.board', 'com.woltlab.wcf.label', 'com.woltlab.wbb.acl', 'com.woltlab.wbb.attachment', 'com.woltlab.wbb.watchedThread', 'com.woltlab.wbb.poll', 'com.woltlab.wbb.like'];
    check($exporter->validateSelectedData($selected), 'Import selection is accepted');
    check($exporter->validateFileAccess(), 'Files in the suite subdirectory are accepted');
    WCF::getSession()->register('importData', ['exporterName' => 'com.woltlab.wcf.exporter.wbb4x', 'dbHost' => $dbHost . ($dbPort ? ':' . $dbPort : ''), 'dbUser' => $dbUser, 'dbPassword' => $dbPassword, 'dbName' => $sourceName, 'dbPrefix' => 'wcf7_', 'fileSystemPath' => $sourcePath, 'userMergeMode' => 4, 'additionalData' => []]);
    foreach ($exporter->getQueue() as $type) {
        runWorker(\wcf\system\worker\ImportWorker::class, ['objectType' => $type]);
    }
    // Browser workers run in separate requests; refresh the process-local group cache.
    (new ReflectionProperty(\wcf\data\user\group\UserGroup::class, 'cache'))->setValue(null, \wcf\system\cache\builder\UserGroupCacheBuilder::getInstance()->rebuild([]));
    foreach ([\wcf\system\worker\LikeRebuildDataWorker::class, forum\system\worker\PostRebuildDataWorker::class, forum\system\worker\ThreadRebuildDataWorker::class, forum\system\worker\BoardRebuildDataWorker::class, forum\system\worker\UserPostCounterRebuildDataWorker::class, forum\system\worker\PostSearchIndexRebuildDataWorker::class] as $class) {
        runWorker($class);
    }
    $mapping = ImportHandler::getInstance();
    $user1 = $mapping->getNewID('com.woltlab.wcf.user', 1);
    $user2 = $mapping->getNewID('com.woltlab.wcf.user', 2);
    $board2 = $mapping->getNewID('com.woltlab.wbb.board', 2);
    $thread1 = $mapping->getNewID('com.woltlab.wbb.thread', 1);
    $post1 = $mapping->getNewID('com.woltlab.wbb.post', 1);
    check($user1 !== 1 && $thread1 !== 1 && $post1 !== 1, 'Colliding IDs are remapped');
    check(targetRow('wcf1_user', 'userID', $user1)['password'] === $password, 'Existing password hash is preserved');
    check((new \wcf\data\user\User($user1))->checkPassword('import-password-52'), 'Core authentication accepts the legacy 5.2 password hash');
    check((int)targetRow('wcf1_user', 'userID', $user1)['avatarFileID'] > 0, 'Avatar copied from the suite subdirectory');
    $board = targetRow('forum1_board', 'boardID', $board2);
    check((int)$board['parentID'] === (int)$mapping->getNewID('com.woltlab.wbb.board', 1), 'Nested board hierarchy');
    check(!str_starts_with($board['title'], 'wbb.board.'), 'Translated board title is imported');
    $thread = targetRow('forum1_thread', 'threadID', $thread1);
    $post = targetRow('forum1_post', 'postID', $post1);
    check((int)$thread['boardID'] === (int)$board2 && (int)$post['threadID'] === (int)$thread1, 'Threads and posts use target IDs');
    check((int)$post['userID'] === (int)$user1, 'Post author remapped');
    check((int)$thread['replies'] === 2, 'Visible reply counter excludes disabled and deleted posts');
    check((int)$post['attachments'] === 3 && (int)$post['pollID'] > 0, 'Attachments and poll connected to the post');
    check((int)$thread['hasLabels'] === 1, 'Thread label assignment');
    check((int)$thread['languageID'] === (int)\wcf\system\language\LanguageFactory::getInstance()->getLanguageByCode('de')->languageID, 'Thread language mapped by language code');
    $statement = $db->prepare('SELECT * FROM wcf1_attachment WHERE objectID = ? AND filename = ?');
    $statement->execute([$post1, 'anhang.txt']);
    $attachment = $statement->fetchArray();
    check($attachment !== false, 'Attachment was copied');
    if ($attachment) {
        $object = new \wcf\data\attachment\Attachment(null, $attachment);
        check(file_get_contents($object->getLocation()) === $contents, 'Attachment bytes match');
        $document = new DOMDocument();
        @$document->loadHTML($post['message']);
        $embeddedIDs = [];
        foreach ((new DOMXPath($document))->query('//woltlab-metacode[@data-name="attach"]') as $element) {
            $attributes = json_decode(base64_decode($element->getAttribute('data-attributes')), true);
            $embeddedIDs[] = (int)($attributes[0] ?? 0);
        }
        check(in_array((int)$attachment['attachmentID'], $embeddedIDs, true), 'Embedded attachment reference remapped');
        $statement = $db->prepare('SELECT COUNT(*) FROM wcf1_message_embedded_object WHERE messageID = ? AND embeddedObjectID = ?');
        $statement->execute([$post1, $attachment['attachmentID']]);
        check((int)$statement->fetchSingleColumn() === 1, 'Embedded attachment is registered for display');
    }
    $statement = $db->prepare('SELECT * FROM forum1_thread_user_status WHERE threadID = ? AND userID = ?');
    $statement->execute([$thread1, $user1]);
    check((int)$statement->fetchArray()['enableNotification'] === 0, 'Subscription without notifications stays silent');
    $statement = $db->prepare('SELECT * FROM wcf1_like WHERE objectTypeID = ? AND objectID = ? AND userID = ?');
    $statement->execute([ObjectTypeCache::getInstance()->getObjectTypeIDByName('com.woltlab.wcf.like.likeableObject', 'com.tinsever.forum.likeablePost'), $post1, $user2]);
    $reaction = $statement->fetchArray();
    check($reaction && (int)$reaction['objectUserID'] === (int)$user1, 'Reaction and its users imported');
    if ($reaction) {
        check(targetRow('wcf1_reaction_type', 'reactionTypeID', $reaction['reactionTypeID'])['iconFile'] === 'thanks.svg', 'Reaction type is preserved');
    }
    $customReactionID = $mapping->getNewID('com.woltlab.wcf.reactionType', 9);
    check($customReactionID && targetRow('wcf1_reaction_type', 'reactionTypeID', $customReactionID)['title'] === 'Applaus', 'Custom reaction type imported');
    check(is_file(WCF_DIR . 'images/reaction/import-fixture.svg'), 'Custom reaction icon copied');
    $post2 = $mapping->getNewID('com.woltlab.wbb.post', 2);
    $statement = $db->prepare('SELECT * FROM wcf1_attachment WHERE objectID = ? AND filename = ?');
    $statement->execute([$post2, 'bild.png']);
    $imageAttachment = $statement->fetchArray();
    check($imageAttachment && (int)$imageAttachment['isImage'] === 1 && (int)$imageAttachment['width'] === 64, 'Image attachment imported with dimensions');
    if ($imageAttachment) {
        $statement = $db->prepare('SELECT COUNT(*) FROM wcf1_message_embedded_object WHERE messageID = ? AND embeddedObjectID = ?');
        $statement->execute([$post2, $imageAttachment['attachmentID']]);
        check((int)$statement->fetchSingleColumn() === 1, 'Image attachment remains embedded');
    }
    check((int)$post['cumulativeLikes'] === 1, 'Reaction counter rebuilt');
    $statement = $db->prepare('SELECT COUNT(*) FROM wcf1_poll_option_vote WHERE pollID = ? AND userID = ?');
    $statement->execute([$post['pollID'], $user2]);
    check((int)$statement->fetchSingleColumn() === 1, 'Poll vote assigned to imported user');
    $labelGroup = targetRow('wcf1_label_group', 'groupID', $mapping->getNewID('com.woltlab.wcf.label.group', 1));
    check((int)$labelGroup['forceSelection'] === 1, 'Mandatory label-group setting preserved');
    $statement = $db->prepare('SELECT COUNT(*) FROM wcf1_label_group_to_object WHERE groupID = ? AND objectTypeID = ? AND objectID = ?');
    $statement->execute([$labelGroup['groupID'], ObjectTypeCache::getInstance()->getObjectTypeIDByName('com.woltlab.wcf.label.objectType', 'com.tinsever.forum.board'), $board2]);
    check((int)$statement->fetchSingleColumn() === 1, 'Label group assigned to the imported board');
    $announcementID = $mapping->getNewID('com.woltlab.wbb.thread', 2);
    $statement = $db->prepare('SELECT COUNT(*) FROM forum1_thread_announcement WHERE threadID = ? AND boardID = ?');
    $statement->execute([$announcementID, $mapping->getNewID('com.woltlab.wbb.board', 3)]);
    check((int)$statement->fetchSingleColumn() === 1, 'Announcement assigned to an additional imported board');
    check((int)targetRow('forum1_thread', 'threadID', $mapping->getNewID('com.woltlab.wbb.thread', 4))['movedThreadID'] === (int)$thread1, 'Moved thread points to the imported destination');
    check((int)targetRow('forum1_thread', 'threadID', $mapping->getNewID('com.woltlab.wbb.thread', 3))['isDeleted'] === 1, 'Deleted thread remains deleted');
    check((int)targetRow('forum1_post', 'postID', $mapping->getNewID('com.woltlab.wbb.post', 4))['isDisabled'] === 1, 'Moderated post remains hidden');
    check((int)targetRow('forum1_post', 'postID', $mapping->getNewID('com.woltlab.wbb.post', 5))['isDeleted'] === 1, 'Deleted post remains deleted');
    $aclTypeID = ObjectTypeCache::getInstance()->getObjectTypeIDByName('com.woltlab.wcf.acl', 'com.tinsever.forum.board');
    $statement = $db->prepare('SELECT value_table.optionValue FROM wcf1_acl_option_to_group value_table JOIN wcf1_acl_option option_table ON option_table.optionID = value_table.optionID WHERE option_table.objectTypeID = ? AND option_table.optionName = ? AND value_table.objectID = ? AND value_table.groupID = ?');
    $statement->execute([$aclTypeID, 'canReadThread', $board2, $mapping->getNewID('com.woltlab.wcf.user.group', 6)]);
    check($statement->fetchSingleColumn() === 0, 'Group permission mapped to the correct board and group');
    $statement = $db->prepare('SELECT value_table.optionValue FROM wcf1_acl_option_to_user value_table JOIN wcf1_acl_option option_table ON option_table.optionID = value_table.optionID WHERE option_table.objectTypeID = ? AND option_table.optionName = ? AND value_table.objectID = ? AND value_table.userID = ?');
    $statement->execute([$aclTypeID, 'canReadThread', $board2, $user2]);
    check($statement->fetchSingleColumn() === 1, 'User permission mapped to the correct board and user');
    check((int)targetRow('wcf1_user', 'userID', $user1)['forumPosts'] === 2, 'User post counter excludes hidden posts and deleted threads');
    $statement = $db->prepare('SELECT COUNT(*) FROM forum1_post_search_index WHERE objectID = ?');
    $statement->execute([$post1]);
    check((int)$statement->fetchSingleColumn() === 1, 'Imported post is included in the search index');
    $statement = $db->prepare('SELECT languageItemValue FROM wcf1_language_item WHERE languageItem = ? AND languageID = ?');
    $statement->execute([$board['title'], \wcf\system\language\LanguageFactory::getInstance()->getLanguageByCode('de')->languageID]);
    check($statement->fetchSingleColumn() === 'Älteres Forum', 'German board title translation is intact');
    $statement->execute([$board['description'], \wcf\system\language\LanguageFactory::getInstance()->getLanguageByCode('en')->languageID]);
    check($statement->fetchSingleColumn() === 'English Übernommene Beschreibung', 'English board description translation is intact');
    $group = targetRow('wcf1_user_group', 'groupID', $mapping->getNewID('com.woltlab.wcf.user.group', 6));
    $statement->execute([$group['groupName'], \wcf\system\language\LanguageFactory::getInstance()->getLanguageByCode('de')->languageID]);
    check($statement->fetchSingleColumn() === 'Übernommenes Team', 'Translated group name is not overwritten by its description');
    $statement->execute([$group['groupDescription'], \wcf\system\language\LanguageFactory::getInstance()->getLanguageByCode('de')->languageID]);
    check($statement->fetchSingleColumn() === 'Beschreibung des Teams', 'Group description translation is preserved');
    // Every embedded reference of post 1 points to its own attachment, each one once.
    $embeddedFilenames = static function () use ($db, $post1): array {
        $message = targetRow('forum1_post', 'postID', $post1)['message'];
        $document = new DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">' . $message);
        $names = [];
        foreach ((new DOMXPath($document))->query('//woltlab-metacode[@data-name="attach"]') as $element) {
            $attributes = json_decode(base64_decode($element->getAttribute('data-attributes')), true);
            $row = targetRow('wcf1_attachment', 'attachmentID', (int)($attributes[0] ?? 0));
            $names[] = ((int)($row['objectID'] ?? 0) === (int)$post1) ? $row['filename'] : 'foreign:' . ($row['filename'] ?? 'missing');
        }
        return $names;
    };
    // in the order of the message: [attach]1, [attach]3, metacode 4, metacode 1
    $expectedNames = ['anhang.txt', 'foto.jpg', 'animation.gif', 'anhang.txt'];
    check(
        forum\system\importer\PostAttachmentImporter::replaceAttachmentID('[attach]10[/attach] [attach=1,"left"][/attach] [attach]1[/attach]', 1, 70)
            === '[attach]10[/attach] [attach=70,"left"][/attach] [attach]70[/attach]',
        'Only the exact attachment ID is replaced'
    );
    check($embeddedFilenames() === $expectedNames, 'Embedded attachments of post 1 keep their own reference in order: ' . implode(', ', $embeddedFilenames()));
    $statement = $db->prepare('SELECT filename, attachmentID FROM wcf1_attachment WHERE objectID IN (?, ?) AND objectTypeID = ?');
    $statement->execute([$post1, $post2, $attachmentType]);
    $importedIDs = array_map('intval', $statement->fetchMap('filename', 'attachmentID'));
    $sourceMax = (int)$pdo->query('SELECT MAX(attachmentID) FROM wcf7_attachment')->fetchColumn();
    check(($importedIDs['bild.png'] ?? 0) === 2 && ($importedIDs['foto.jpg'] ?? 0) === 3 && ($importedIDs['animation.gif'] ?? 0) === 4, 'Free attachment IDs are kept: ' . json_encode($importedIDs));
    check(($importedIDs['anhang.txt'] ?? 0) > $sourceMax, 'The colliding attachment gets an ID above all source IDs');
    $jpegAvatarFile = targetRow('wcf1_user', 'userID', $user2)['avatarFileID'] ?? 0;
    check((int)$jpegAvatarFile > 0, 'JPEG avatar imported');

    // The Core migrates old attachments to files and converts images to WebP
    // when these rebuild jobs run; nothing may get lost or point elsewhere.
    \wcf\data\option\OptionEditor::import(['image_convert_format' => 'webp']);
    foreach ([\wcf\system\worker\AttachmentRebuildDataWorker::class, \wcf\system\worker\FileRebuildDataWorker::class] as $class) {
        runWorker($class);
    }
    $statement = $db->prepare('SELECT attachmentID, fileID FROM wcf1_attachment WHERE objectID IN (?, ?) AND objectTypeID = ?');
    $statement->execute([$post1, $post2, $attachmentType]);
    $afterRebuild = $statement->fetchMap('attachmentID', 'fileID');
    check(count($afterRebuild) === 4, 'No attachment is lost by the attachment and file rebuild (' . count($afterRebuild) . ' of 4)');
    check(!in_array(null, $afterRebuild, true), 'Every imported attachment was migrated to a file');
    foreach ($afterRebuild as $attachmentID => $fileID) {
        $file = new \wcf\data\file\File($fileID);
        check($file->fileID && is_file($file->getPathname()), "File of attachment {$attachmentID} exists after the rebuild");
    }
    check($embeddedFilenames() === $expectedNames, 'Embedded references still resolve after the rebuild');
    foreach ([$user1, $user2] as $userID) {
        $avatarFile = new \wcf\data\file\File((int)targetRow('wcf1_user', 'userID', $userID)['avatarFileID']);
        check($avatarFile->fileID && is_file($avatarFile->getPathname()), "Avatar of user {$userID} exists after the file rebuild ({$avatarFile->filename})");
    }

    check($pdo->query('CHECKSUM TABLE wcf7_user, wcf7_label, wbb7_board, wbb7_thread, wbb7_post, wcf7_attachment')->fetchAll(PDO::FETCH_ASSOC) === $sourceChecksums, 'Source database is unchanged');
    echo $failures ? count($failures) . " content checks failed.\n" : $checks . " content checks passed.\n";
    $exitCode = $failures ? 1 : 0;
} catch (Throwable $e) {
    fwrite(STDERR, get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n");
    $exitCode = 1;
} finally {
    WCF::getSession()->unregister('importData');
    $pdo->exec('DROP DATABASE `' . $sourceName . '`');
    removeFixture($sourceRoot);
}
exit($exitCode);
