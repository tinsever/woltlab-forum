<?php

namespace forum\action;

use wcf\system\WCF;

/**
 * Dialog to move posts into an existing thread or to split them off into a new thread.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostMoveDialogAction extends AbstractPostTransferDialogAction
{
    #[\Override]
    protected function getActionName(): string
    {
        return 'move';
    }

    #[\Override]
    protected function getTitle(): string
    {
        return WCF::getLanguage()->get('forum.post.edit.move');
    }
}
