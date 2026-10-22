<?php

namespace forum\system\importer;

use wcf\system\importer\ImportHandler;
use wcf\system\WCF;

/**
 * Shared helpers of the forum importers.
 *
 * The importers are registered with the data type names used by the exporters
 * of WoltLab Suite (`com.woltlab.wbb.*`) so that existing exporters can be used
 * to migrate other forum software into this forum.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
trait TForumImporter
{
    /**
     * @var array<string, array<string, true>>
     */
    private static array $tableColumns = [];

    /**
     * Removes values that do not correspond to a column of the given table.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function filterColumns(string $tableName, array $data): array
    {
        if (!isset(self::$tableColumns[$tableName])) {
            self::$tableColumns[$tableName] = [];
            foreach (WCF::getDB()->getEditor()->getColumns($tableName) as $column) {
                self::$tableColumns[$tableName][$column['name']] = true;
            }
        }

        return \array_intersect_key($data, self::$tableColumns[$tableName]);
    }

    /**
     * Maps the old id of an object of the given type to its new id or `null`.
     */
    protected function getNewID(string $type, mixed $oldID): ?int
    {
        if ($oldID === null || $oldID === '' || $oldID === 0 || $oldID === '0') {
            return null;
        }

        $newID = ImportHandler::getInstance()->getNewID($type, $oldID);

        return $newID ? (int)$newID : null;
    }

    /**
     * Returns true if the given old id is numeric and not used by an existing row.
     */
    protected function canReuseID(string $tableName, string $indexName, mixed $oldID): bool
    {
        if (!\ctype_digit((string)$oldID) || !(int)$oldID) {
            return false;
        }

        $sql = "SELECT  COUNT(*)
                FROM    {$tableName}
                WHERE   {$indexName} = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$oldID]);

        return !$statement->fetchSingleColumn();
    }
}
