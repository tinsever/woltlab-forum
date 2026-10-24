<?php

namespace forum\system\importer;

use forum\data\board\Board;
use forum\data\board\BoardEditor;
use wcf\system\importer\AbstractImporter;
use wcf\system\importer\ImportHandler;

/**
 * Imports boards.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class BoardImporter extends AbstractImporter
{
    use TForumImporter;

    /**
     * @inheritDoc
     */
    protected $className = Board::class;

    #[\Override]
    public function import($oldID, array $data, array $additionalData = [])
    {
        $data['parentID'] = $this->getNewID('com.woltlab.wbb.board', $data['parentID'] ?? null);
        $data['time'] = $data['time'] ?? TIME_NOW;
        $data['title'] = (string)($data['title'] ?? '');
        if ($data['title'] === '') {
            $data['title'] = 'Board ' . $oldID;
        }
        $data['description'] ??= '';

        if (!isset($data['boardType']) || !\in_array((int)$data['boardType'], [Board::TYPE_BOARD, Board::TYPE_CATEGORY, Board::TYPE_LINK], true)) {
            $data['boardType'] = Board::TYPE_BOARD;
        }

        if (!empty($data['sortField']) && !\in_array($data['sortField'], Board::SORT_FIELDS, true)) {
            $data['sortField'] = '';
        }

        foreach (['sortOrder', 'postSortOrder'] as $column) {
            if (isset($data[$column]) && !\in_array($data[$column], ['', 'ASC', 'DESC'], true)) {
                $data[$column] = '';
            }
        }

        $data = $this->filterColumns('forum1_board', $data);
        unset($data['boardID'], $data['styleID']);

        $board = BoardEditor::create($data);

        ImportHandler::getInstance()->saveNewID('com.woltlab.wbb.board', $oldID, $board->boardID);
        if (!empty($additionalData['i18n'])) {
            $items = $updateData = [];
            foreach (['title' => '', 'description' => '.description'] as $property => $suffix) {
                if (empty($additionalData['i18n'][$property])) {
                    continue;
                }
                $item = 'forum.board.board' . $board->boardID . $suffix;
                $updateData[$property] = $item;
                foreach ($additionalData['i18n'][$property] as $languageID => $value) {
                    $items[] = ['languageID' => $languageID, 'languageItem' => $item, 'languageItemValue' => $value];
                }
            }
            if ($items !== []) {
                $this->importI18nValues($items, 'forum.board', 'com.tinsever.forum');
                (new BoardEditor($board))->update($updateData);
            }
        }
        BoardEditor::resetCache();

        return $board->boardID;
    }
}
