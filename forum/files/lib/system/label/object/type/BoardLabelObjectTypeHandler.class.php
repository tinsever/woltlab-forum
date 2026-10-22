<?php

namespace forum\system\label\object\type;

use forum\data\board\BoardEditor;
use forum\data\board\BoardNodeTree;
use wcf\data\object\type\ObjectType;
use wcf\system\label\object\type\AbstractLabelObjectTypeHandler;
use wcf\system\label\object\type\LabelObjectType;
use wcf\system\label\object\type\LabelObjectTypeContainer;

/**
 * Allows label groups to be assigned to boards.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class BoardLabelObjectTypeHandler extends AbstractLabelObjectTypeHandler
{
    #[\Override]
    public function getContainerForObjectType(ObjectType $objectType): LabelObjectTypeContainer
    {
        $container = new LabelObjectTypeContainer($objectType->objectTypeID);

        foreach ((new BoardNodeTree())->getFlatList() as $node) {
            $container->add(new LabelObjectType(
                $node->board->getTitle(),
                $node->board->boardID,
                $node->depth,
                !$node->board->isBoard()
            ));
        }

        return $container;
    }

    #[\Override]
    public function save()
    {
        BoardEditor::resetCache();
    }
}
