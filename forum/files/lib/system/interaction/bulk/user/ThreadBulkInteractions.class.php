<?php

namespace forum\system\interaction\bulk\user;

use forum\action\ThreadMergeDialogAction;
use forum\action\ThreadMoveDialogAction;
use forum\data\thread\Thread;
use forum\data\thread\ThreadList;
use forum\event\interaction\bulk\user\ThreadBulkInteractionCollecting;
use wcf\system\event\EventHandler;
use wcf\system\interaction\bulk\AbstractBulkInteractionProvider;
use wcf\system\interaction\bulk\BulkDeleteInteraction;
use wcf\system\interaction\bulk\BulkFormBuilderDialogInteraction;
use wcf\system\interaction\bulk\BulkRestoreInteraction;
use wcf\system\interaction\bulk\BulkRpcInteraction;
use wcf\system\interaction\bulk\BulkSoftDeleteWithReasonInteraction;
use wcf\system\interaction\InteractionConfirmationType;

/**
 * Bulk interactions for threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ThreadBulkInteractions extends AbstractBulkInteractionProvider
{
    public function __construct()
    {
        $this->addInteractions([
            new BulkRpcInteraction(
                'close',
                'forum/threads/%s/close',
                'forum.thread.edit.close',
                isAvailableCallback: static fn(Thread $thread) => !$thread->isLink() && !$thread->isClosed && $thread->canClose(),
            ),
            new BulkRpcInteraction(
                'open',
                'forum/threads/%s/open',
                'forum.thread.edit.open',
                isAvailableCallback: static fn(Thread $thread) => !$thread->isLink() && $thread->isClosed && $thread->canClose(),
            ),
            new BulkRpcInteraction(
                'stick',
                'forum/threads/%s/stick',
                'forum.thread.edit.sticky',
                isAvailableCallback: static fn(Thread $thread) => !$thread->isLink() && !$thread->isSticky && !$thread->isAnnouncement && $thread->canPin(),
            ),
            new BulkRpcInteraction(
                'unstick',
                'forum/threads/%s/unstick',
                'forum.thread.edit.unsticky',
                isAvailableCallback: static fn(Thread $thread) => !$thread->isLink() && $thread->isSticky && $thread->canPin(),
            ),
            new BulkRpcInteraction(
                'mark-as-done',
                'forum/threads/%s/mark-as-done',
                'forum.thread.edit.done',
                isAvailableCallback: static fn(Thread $thread) => !$thread->isDone && $thread->canMarkAsDone(),
            ),
            new BulkRpcInteraction(
                'mark-as-undone',
                'forum/threads/%s/mark-as-undone',
                'forum.thread.edit.undone',
                isAvailableCallback: static fn(Thread $thread) => $thread->isDone && $thread->canMarkAsDone(),
            ),
            new BulkFormBuilderDialogInteraction(
                'move',
                ThreadMoveDialogAction::class,
                'forum.thread.edit.move',
                static fn(Thread $thread) => $thread->canMove(),
            ),
            new BulkFormBuilderDialogInteraction(
                'merge',
                ThreadMergeDialogAction::class,
                'forum.thread.edit.merge',
                static fn(Thread $thread) => !$thread->isLink() && $thread->canMerge(),
            ),
            new BulkRpcInteraction(
                'enable',
                'forum/threads/%s/enable',
                'forum.thread.edit.enable',
                isAvailableCallback: static fn(Thread $thread) => !$thread->isLink() && $thread->isDisabled && $thread->canEnable(),
            ),
            new BulkRpcInteraction(
                'disable',
                'forum/threads/%s/disable',
                'forum.thread.edit.disable',
                InteractionConfirmationType::Disable,
                isAvailableCallback: static fn(Thread $thread) => !$thread->isLink() && !$thread->isDisabled && $thread->canEnable(),
            ),
            new BulkSoftDeleteWithReasonInteraction(
                'forum/threads/%s/soft-delete',
                static fn(Thread $thread) => !$thread->isLink() && $thread->canTrash(),
            ),
            new BulkRestoreInteraction(
                'forum/threads/%s/restore',
                static fn(Thread $thread) => $thread->canRestore(),
            ),
            new BulkDeleteInteraction(
                'forum/threads/%s',
                static fn(Thread $thread) => ($thread->isDeleted || $thread->isLink()) && $thread->canDelete(),
            ),
        ]);

        EventHandler::getInstance()->fire(new ThreadBulkInteractionCollecting($this));
    }

    #[\Override]
    public function getObjectListClassName(): string
    {
        return ThreadList::class;
    }
}
