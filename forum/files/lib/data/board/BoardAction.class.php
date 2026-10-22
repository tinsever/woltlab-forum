<?php

namespace forum\data\board;

use forum\data\thread\ThreadAction;
use forum\system\board\BoardPermissionHandler;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\ISortableAction;
use wcf\system\acl\ACLHandler;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\UserInputException;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;

/**
 * Executes board-related actions.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @extends AbstractDatabaseObjectAction<Board, BoardEditor>
 */
class BoardAction extends AbstractDatabaseObjectAction implements ISortableAction
{
    /**
     * @inheritDoc
     */
    protected $className = BoardEditor::class;

    /**
     * @inheritDoc
     */
    protected $permissionsCreate = ['admin.forum.canManageBoard'];

    /**
     * @inheritDoc
     */
    protected $permissionsDelete = ['admin.forum.canManageBoard'];

    /**
     * @inheritDoc
     */
    protected $permissionsUpdate = ['admin.forum.canManageBoard'];

    /**
     * @inheritDoc
     */
    protected $requireACP = ['create', 'delete', 'update', 'updatePosition'];

    #[\Override]
    public function create()
    {
        if (!isset($this->parameters['data']['time'])) {
            $this->parameters['data']['time'] = TIME_NOW;
        }

        if (empty($this->parameters['data']['position'])) {
            $sql = "SELECT  COALESCE(MAX(position), 0) + 1
                    FROM    forum1_board
                    WHERE   " . (empty($this->parameters['data']['parentID']) ? "parentID IS NULL" : "parentID = ?");
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute(empty($this->parameters['data']['parentID']) ? [] : [$this->parameters['data']['parentID']]);
            $this->parameters['data']['position'] = $statement->fetchSingleColumn();
        }

        return parent::create();
    }

    #[\Override]
    public function delete()
    {
        if (empty($this->objects)) {
            $this->readObjects();
        }

        // collect all affected boards including their descendants
        $boardIDs = [];
        foreach ($this->getObjects() as $board) {
            $boardIDs = \array_merge($boardIDs, BoardCache::getInstance()->getAllChildIDs($board->boardID));
        }
        $boardIDs = \array_unique($boardIDs);

        // threads are deleted through their action to clean up search index, attachments, etc.
        if ($boardIDs !== []) {
            $conditions = new PreparedStatementConditionBuilder();
            $conditions->add('boardID IN (?)', [$boardIDs]);
            $sql = "SELECT  threadID
                    FROM    forum1_thread
                    " . $conditions;
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute($conditions->getParameters());
            $threadIDs = $statement->fetchAll(\PDO::FETCH_COLUMN);

            foreach (\array_chunk($threadIDs, 100) as $chunk) {
                (new ThreadAction($chunk, 'delete', ['skipBoardUpdate' => true]))->executeAction();
            }

            // remove descendants that are not part of the action
            $this->objectIDs = $boardIDs;
            $this->objects = [];
            $this->readObjects();
        }

        $count = parent::delete();

        if ($boardIDs !== []) {
            ACLHandler::getInstance()->removeValues(
                ACLHandler::getInstance()->getObjectTypeID('com.tinsever.forum.board'),
                $boardIDs
            );

            foreach ($this->getObjects() as $board) {
                if (\str_starts_with($board->title, 'forum.board.board')) {
                    $this->removeLanguageItems($board);
                }
            }
        }

        return $count;
    }

    private function removeLanguageItems(BoardEditor $board): void
    {
        $sql = "DELETE FROM wcf1_language_item
                WHERE       languageItem IN (?, ?)";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([
            'forum.board.board' . $board->boardID,
            'forum.board.board' . $board->boardID . '.description',
        ]);

        LanguageFactory::getInstance()->deleteLanguageCache();
    }

    #[\Override]
    public function validateUpdatePosition()
    {
        WCF::getSession()->checkPermissions(['admin.forum.canManageBoard']);

        if (!isset($this->parameters['data']['structure']) || !\is_array($this->parameters['data']['structure'])) {
            throw new UserInputException('structure');
        }

        $newParentIDs = [];
        foreach ($this->parameters['data']['structure'] as $parentID => $boardIDs) {
            if (!\is_array($boardIDs)) {
                throw new UserInputException('structure');
            }

            $parentID = \intval($parentID);
            if ($parentID) {
                $parent = BoardCache::getInstance()->getBoard($parentID);
                if ($parent === null) {
                    throw new UserInputException('structure');
                }
                $this->objects[$parentID] = new BoardEditor($parent);
            }

            foreach ($boardIDs as $boardID) {
                $board = BoardCache::getInstance()->getBoard(\intval($boardID));
                if ($board === null) {
                    throw new UserInputException('structure');
                }

                $this->objects[$board->boardID] = new BoardEditor($board);
                $newParentIDs[$board->boardID] = $parentID;
            }
        }

        // reject cycles
        foreach (\array_keys($newParentIDs) as $boardID) {
            $visited = [];
            $current = $boardID;
            while ($current) {
                if (isset($visited[$current])) {
                    throw new UserInputException('structure');
                }
                $visited[$current] = true;

                if (\array_key_exists($current, $newParentIDs)) {
                    $current = $newParentIDs[$current];
                } else {
                    $current = BoardCache::getInstance()->getBoard($current)?->parentID ?: 0;
                }
            }
        }
    }

    #[\Override]
    public function updatePosition()
    {
        WCF::getDB()->beginTransaction();
        foreach ($this->parameters['data']['structure'] as $parentID => $boardIDs) {
            $position = 1;
            foreach ($boardIDs as $boardID) {
                $this->objects[\intval($boardID)]->update([
                    'parentID' => \intval($parentID) ?: null,
                    'position' => $position++,
                ]);
            }
        }
        WCF::getDB()->commitTransaction();

        BoardPermissionHandler::resetCache();
    }
}
