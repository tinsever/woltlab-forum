<?php

namespace forum\system\option;

use forum\data\board\BoardCache;
use forum\data\board\BoardNodeTree;
use wcf\data\option\Option;
use wcf\system\exception\UserInputException;
use wcf\system\option\AbstractOptionType;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Option type for selecting a single board, `0` means none.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ForumBoardOptionType extends AbstractOptionType
{
    #[\Override]
    public function getFormElement(Option $option, $value)
    {
        $html = \sprintf(
            '<select id="%s" name="values[%s]"><option value="0">%s</option>',
            StringUtil::encodeHTML($option->optionName),
            StringUtil::encodeHTML($option->optionName),
            WCF::getLanguage()->get('wcf.global.noSelection')
        );

        foreach ((new BoardNodeTree(0, null, -1))->getNestedOptions() as $node) {
            $html .= \sprintf(
                '<option value="%d"%s%s>%s%s</option>',
                $node['value'],
                $node['value'] == $value ? ' selected' : '',
                $node['isSelectable'] ? '' : ' disabled',
                \str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $node['depth']),
                StringUtil::encodeHTML($node['label'])
            );
        }

        return $html . '</select>';
    }

    #[\Override]
    public function validate(Option $option, $newValue)
    {
        $boardID = \intval($newValue);
        if (!$boardID) {
            return;
        }

        $board = BoardCache::getInstance()->getBoard($boardID);
        if ($board === null || !$board->isBoard()) {
            throw new UserInputException($option->optionName, 'validationFailed');
        }
    }

    #[\Override]
    public function getData(Option $option, $newValue)
    {
        return \intval($newValue);
    }
}
