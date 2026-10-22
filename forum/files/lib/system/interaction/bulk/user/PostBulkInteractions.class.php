<?php

namespace forum\system\interaction\bulk\user;

use forum\action\PostCopyDialogAction;
use forum\action\PostMergeDialogAction;
use forum\action\PostMoveDialogAction;
use forum\data\post\Post;
use forum\data\post\PostList;
use forum\event\interaction\bulk\user\PostBulkInteractionCollecting;
use wcf\system\event\EventHandler;
use wcf\system\interaction\bulk\AbstractBulkInteractionProvider;
use wcf\system\interaction\bulk\BulkDeleteInteraction;
use wcf\system\interaction\bulk\BulkFormBuilderDialogInteraction;
use wcf\system\interaction\bulk\BulkRestoreInteraction;
use wcf\system\interaction\bulk\BulkRpcInteraction;
use wcf\system\interaction\bulk\BulkSoftDeleteWithReasonInteraction;
use wcf\system\interaction\Divider;
use wcf\system\interaction\InteractionConfirmationType;

/**
 * Bulk interactions for the posts of a thread.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostBulkInteractions extends AbstractBulkInteractionProvider
{
    public function __construct()
    {
        $this->addInteractions([
            new BulkFormBuilderDialogInteraction(
                'move',
                PostMoveDialogAction::class,
                'forum.post.edit.move',
                static fn(Post $post) => $post->canMove(),
            ),
            new BulkFormBuilderDialogInteraction(
                'copy',
                PostCopyDialogAction::class,
                'forum.post.edit.copy',
                static fn(Post $post) => $post->canMove(),
            ),
            new BulkFormBuilderDialogInteraction(
                'merge',
                PostMergeDialogAction::class,
                'forum.post.edit.merge',
                static fn(Post $post) => $post->canMerge(),
            ),
            new Divider(),
            new BulkRpcInteraction(
                'close',
                'forum/posts/%s/close',
                'forum.post.edit.close',
                isAvailableCallback: static fn(Post $post) => !$post->isClosed && $post->canClose(),
            ),
            new BulkRpcInteraction(
                'open',
                'forum/posts/%s/open',
                'forum.post.edit.open',
                isAvailableCallback: static fn(Post $post) => $post->isClosed && $post->canClose(),
            ),
            new BulkRpcInteraction(
                'enable',
                'forum/posts/%s/enable',
                'forum.post.edit.enable',
                isAvailableCallback: static fn(Post $post) => $post->isDisabled && !$post->isFirstPost() && $post->canEnable(),
            ),
            new BulkRpcInteraction(
                'disable',
                'forum/posts/%s/disable',
                'forum.post.edit.disable',
                InteractionConfirmationType::Disable,
                isAvailableCallback: static fn(Post $post) => !$post->isDisabled && !$post->isFirstPost() && $post->canEnable(),
            ),
            new BulkSoftDeleteWithReasonInteraction(
                'forum/posts/%s/soft-delete',
                static fn(Post $post) => !$post->isDeleted && !$post->isFirstPost() && $post->canTrash(),
            ),
            new BulkRestoreInteraction(
                'forum/posts/%s/restore',
                static fn(Post $post) => !$post->isFirstPost() && $post->canRestore(),
            ),
            new BulkDeleteInteraction(
                'forum/posts/%s',
                static fn(Post $post) => $post->isDeleted && !$post->isFirstPost() && $post->canDelete(),
            ),
        ]);

        EventHandler::getInstance()->fire(new PostBulkInteractionCollecting($this));
    }

    #[\Override]
    public function getObjectListClassName(): string
    {
        return PostList::class;
    }
}
