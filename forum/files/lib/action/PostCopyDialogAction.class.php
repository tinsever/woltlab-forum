<?php

namespace forum\action;

use wcf\system\WCF;

/**
 * Dialog to copy posts into an existing or a new thread.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostCopyDialogAction extends AbstractPostTransferDialogAction
{
    #[\Override]
    protected function getActionName(): string
    {
        return 'copy';
    }

    #[\Override]
    protected function getTitle(): string
    {
        return WCF::getLanguage()->get('forum.post.edit.copy');
    }
}
