<?php

/**
 * Integration regression for label imports using a read-only source fixture
 * and the real local Core importers. All target writes are rolled back.
 * Usage: php scripts/check-importer-labels.php [WCF_DIR] [EXPORTER_FILE]
 */
if (PHP_SAPI !== 'cli') {
    exit(1);
}

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/wsc/';
$_SERVER['SCRIPT_NAME'] = '/wsc/index.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$core = $argv[1] ?? __DIR__ . '/../dev/www/wsc';
require $core . '/global.php';
require $argv[2] ?? $core . '/lib/system/exporter/WBB4xExporter.class.php';

final class LabelSourceStatement
{
    private int $offset = 0;

    public function __construct(private array $rows)
    {
    }

    public function execute(array $parameters = []): void
    {
        $this->offset = 0;
    }

    public function fetchArray(): array|false
    {
        return $this->rows[$this->offset++] ?? false;
    }
}

final class LabelSourceFixture
{
    public function prepareUnmanaged(string $sql, int $limit = 0): LabelSourceStatement
    {
        return new LabelSourceStatement(match (true) {
            str_contains($sql, '_object_type') => [['objectTypeID' => 9901]],
            str_contains($sql, '_label_group_to_object') => [
                ['groupID' => 860001, 'objectID' => 870001],
                ['groupID' => 860001, 'objectID' => 870002], // Not imported: skip this association.
            ],
            str_contains($sql, '_label_group') => [
                ['groupID' => 860001, 'groupName' => 'Importer regression fixture'],
                ['groupID' => 860002, 'groupName' => 'Unassigned label group'],
            ],
            str_contains($sql, '_label') => [
                ['labelID' => 880001, 'groupID' => 860001, 'label' => 'Regression label', 'cssClassName' => ''],
            ],
            default => throw new RuntimeException('Unexpected source query: ' . $sql),
        });
    }
}

$db = wcf\system\WCF::getDB();
$db->beginTransaction();
$exitCode = 0;
try {
    $handler = wcf\system\importer\ImportHandler::getInstance();
    $handler->setImportHash(bin2hex(random_bytes(4)));
    $boardID = $handler->getImporter('com.woltlab.wbb.board')->import(870001, ['title' => 'Importer regression board']);
    if ($boardID === 870001) {
        throw new RuntimeException('Fixture must exercise a changed board ID.');
    }

    $exporter = new wcf\system\exporter\WBB4xExporter();
    (new ReflectionProperty($exporter, 'database'))->setValue($exporter, new LabelSourceFixture());
    (new ReflectionProperty($exporter, 'dbNo'))->setValue($exporter, 1);
    $exporter->exportLabels(0, 1000);

    $groupID = $handler->getNewID('com.woltlab.wcf.label.group', 860001);
    $objectTypeID = wcf\data\object\type\ObjectTypeCache::getInstance()->getObjectTypeIDByName(
        'com.woltlab.wcf.label.objectType',
        'com.tinsever.forum.board'
    );
    $statement = $db->prepare('SELECT objectTypeID, objectID FROM wcf1_label_group_to_object WHERE groupID = ?');
    $statement->execute([$groupID]);
    $assignment = $statement->fetchArray();
    if (!$assignment || (int)$assignment['objectTypeID'] !== (int)$objectTypeID || (int)$assignment['objectID'] !== (int)$boardID) {
        throw new RuntimeException('Label group was not assigned to the imported board: ' . json_encode([$assignment, $groupID, $objectTypeID, $boardID]));
    }
    if ($statement->fetchArray() !== false) {
        throw new RuntimeException('Unexpected assignment for a board that was not imported.');
    }

    $labelID = $handler->getNewID('com.woltlab.wcf.label', 880001);
    $label = new wcf\data\label\Label($labelID);
    if ($label->label !== 'Regression label' || (int)$label->groupID !== (int)$groupID) {
        throw new RuntimeException('Label or label-group mapping is incorrect.');
    }
    $unassignedGroupID = $handler->getNewID('com.woltlab.wcf.label.group', 860002);
    if (!$unassignedGroupID) {
        throw new RuntimeException('Group without board assignments was not imported.');
    }
    $statement->execute([$unassignedGroupID]);
    if ($statement->fetchArray() !== false) {
        throw new RuntimeException('Unassigned group received a board assignment.');
    }
    echo "OK: label groups, labels, remapped board IDs and missing-board handling.\n";
} catch (Throwable $e) {
    fwrite(STDERR, get_class($e) . ': ' . $e->getMessage() . "\n");
    $exitCode = 1;
} finally {
    $db->rollBackTransaction();
}
exit($exitCode);
