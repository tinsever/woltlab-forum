<?php

namespace forum\system\interaction\user;

use forum\action\ThreadAnnouncementDialogAction;
use forum\action\ThreadEditDialogAction;
use forum\action\ThreadMergeDialogAction;
use forum\action\ThreadMoveDialogAction;
use forum\data\thread\Thread;
use forum\event\interaction\user\ThreadInteractionCollecting;
use wcf\system\event\EventHandler;
use wcf\system\interaction\AbstractInteractionProvider;
use wcf\system\interaction\DeleteInteraction;
use wcf\system\interaction\Divider;
use wcf\system\interaction\FormBuilderDialogInteraction;
use wcf\system\interaction\InteractionConfirmationType;
use wcf\system\interaction\InteractionEffect;
use wcf\system\interaction\RestoreInteraction;
use wcf\system\interaction\RpcInteraction;
use wcf\system\interaction\SoftDeleteWithReasonInteraction;
use wcf\system\request\LinkHandler;

/**
 * Interactions for threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class ThreadInteractions extends AbstractInteractionProvider
{
    /**
     * @param bool $isStandalone true if the interactions are shown on the thread page itself,
     *                           where moving the thread changes the breadcrumbs
     */
    public function __construct(bool $isStandalone = false)
    {
        $this->addInteractions([
            new FormBuilderDialogInteraction(
                'edit',
                LinkHandler::getInstance()->getControllerLink(ThreadEditDialogAction::class, ['id' => '%s']),
                'forum.thread.edit',
                static fn(Thread $thread) => !$thread->isLink() && $thread->canEdit(),
            ),
            new Divider(),
            new RpcInteraction(
                'close',
                'forum/threads/%s/close',
                'forum.thread.edit.close',
                isAvailableCallback: static fn(Thread $thread) => !$thread->isLink() && !$thread->isClosed && $thread->canClose(),
            ),
            new RpcInteraction(
                'open',
                'forum/threads/%s/open',
                'forum.thread.edit.open',
                isAvailableCallback: static fn(Thread $thread) => !$thread->isLink() && $thread->isClosed && $thread->canClose(),
            ),
            new RpcInteraction(
                'stick',
                'forum/threads/%s/stick',
                'forum.thread.edit.sticky',
                isAvailableCallback: static fn(Thread $thread) => !$thread->isLink() && !$thread->isSticky && !$thread->isAnnouncement && $thread->canPin(),
                interactionEffect: InteractionEffect::ReloadList,
            ),
            new RpcInteraction(
                'unstick',
                'forum/threads/%s/unstick',
                'forum.thread.edit.unsticky',
                isAvailableCallback: static fn(Thread $thread) => !$thread->isLink() && $thread->isSticky && $thread->canPin(),
                interactionEffect: InteractionEffect::ReloadList,
            ),
            new FormBuilderDialogInteraction(
                'announcement',
                LinkHandler::getInstance()->getControllerLink(ThreadAnnouncementDialogAction::class, ['id' => '%s']),
                'forum.thread.edit.announcement',
                static fn(Thread $thread) => !$thread->isLink() && $thread->canSetAnnouncement(),
                InteractionEffect::ReloadPage,
            ),
            new RpcInteraction(
                'unset-announcement',
                'forum/threads/%s/unset-announcement',
                'forum.thread.edit.unannouncement',
                isAvailableCallback: static fn(Thread $thread) => !$thread->isLink() && $thread->isAnnouncement && $thread->canSetAnnouncement(),
                interactionEffect: InteractionEffect::ReloadPage,
            ),
            new RpcInteraction(
                'mark-as-done',
                'forum/threads/%s/mark-as-done',
                'forum.thread.edit.done',
                isAvailableCallback: static fn(Thread $thread) => !$thread->isDone && $thread->canMarkAsDone(),
            ),
            new RpcInteraction(
                'mark-as-undone',
                'forum/threads/%s/mark-as-undone',
                'forum.thread.edit.undone',
                isAvailableCallback: static fn(Thread $thread) => $thread->isDone && $thread->canMarkAsDone(),
            ),
            new Divider(),
            new FormBuilderDialogInteraction(
                'move',
                LinkHandler::getInstance()->getControllerLink(ThreadMoveDialogAction::class, ['id' => '%s']),
                'forum.thread.edit.move',
                static fn(Thread $thread) => $thread->canMove(),
                $isStandalone ? InteractionEffect::ReloadPage : InteractionEffect::ReloadList,
            ),
            new FormBuilderDialogInteraction(
                'merge',
                LinkHandler::getInstance()->getControllerLink(ThreadMergeDialogAction::class, ['id' => '%s']),
                'forum.thread.edit.merge',
                static fn(Thread $thread) => !$thread->isLink() && $thread->canMerge(),
                InteractionEffect::ReloadPage,
            ),
            new Divider(),
            new RpcInteraction(
                'enable',
                'forum/threads/%s/enable',
                'forum.thread.edit.enable',
                isAvailableCallback: static fn(Thread $thread) => !$thread->isLink() && $thread->isDisabled && $thread->canEnable(),
            ),
            new RpcInteraction(
                'disable',
                'forum/threads/%s/disable',
                'forum.thread.edit.disable',
                InteractionConfirmationType::Disable,
                isAvailableCallback: static fn(Thread $thread) => !$thread->isLink() && !$thread->isDisabled && $thread->canEnable(),
            ),
            new SoftDeleteWithReasonInteraction(
                'forum/threads/%s/soft-delete',
                static fn(Thread $thread) => !$thread->isLink() && $thread->canTrash(),
            ),
            new RestoreInteraction(
                'forum/threads/%s/restore',
                static fn(Thread $thread) => $thread->canRestore(),
            ),
            new DeleteInteraction(
                'forum/threads/%s',
                static fn(Thread $thread) => ($thread->isDeleted || $thread->isLink()) && $thread->canDelete(),
            ),
        ]);

        EventHandler::getInstance()->fire(new ThreadInteractionCollecting($this));
    }

    #[\Override]
    public function getObjectClassName(): string
    {
        return Thread::class;
    }
}
