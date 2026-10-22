<?php

namespace forum\system\interaction\user;

use forum\data\post\Post;
use forum\event\interaction\user\PostInteractionCollecting;
use forum\form\PostEditForm;
use wcf\system\event\EventHandler;
use wcf\system\interaction\AbstractInteractionProvider;
use wcf\system\interaction\DeleteInteraction;
use wcf\system\interaction\Divider;
use wcf\system\interaction\InteractionConfirmationType;
use wcf\system\interaction\InteractionEffect;
use wcf\system\interaction\LinkInteraction;
use wcf\system\interaction\RestoreInteraction;
use wcf\system\interaction\RpcInteraction;
use wcf\system\interaction\SoftDeleteWithReasonInteraction;

/**
 * Interactions for posts, shown in the dropdown of each post.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostInteractions extends AbstractInteractionProvider
{
    public function __construct()
    {
        $this->addInteractions([
            new LinkInteraction(
                'edit-extended',
                PostEditForm::class,
                'forum.post.edit.extended',
                static fn(Post $post) => $post->canEdit(),
            ),
            new RpcInteraction(
                'best-answer',
                'forum/posts/%s/best-answer',
                'forum.post.edit.bestAnswer',
                isAvailableCallback: static fn(Post $post) => !$post->isBestAnswer()
                    && !$post->isFirstPost()
                    && !$post->isDeleted
                    && !$post->isDisabled
                    && $post->getThread()->canMarkBestAnswer(),
                interactionEffect: InteractionEffect::ReloadPage,
            ),
            new Divider(),
            new RpcInteraction(
                'close',
                'forum/posts/%s/close',
                'forum.post.edit.close',
                isAvailableCallback: static fn(Post $post) => !$post->isClosed && $post->canClose(),
                interactionEffect: InteractionEffect::ReloadPage,
            ),
            new RpcInteraction(
                'open',
                'forum/posts/%s/open',
                'forum.post.edit.open',
                isAvailableCallback: static fn(Post $post) => $post->isClosed && $post->canClose(),
                interactionEffect: InteractionEffect::ReloadPage,
            ),
            new RpcInteraction(
                'enable',
                'forum/posts/%s/enable',
                'forum.post.edit.enable',
                isAvailableCallback: static fn(Post $post) => $post->isDisabled && !$post->isFirstPost() && $post->canEnable(),
                interactionEffect: InteractionEffect::ReloadPage,
            ),
            new RpcInteraction(
                'disable',
                'forum/posts/%s/disable',
                'forum.post.edit.disable',
                InteractionConfirmationType::Disable,
                isAvailableCallback: static fn(Post $post) => !$post->isDisabled && !$post->isFirstPost() && $post->canEnable(),
                interactionEffect: InteractionEffect::ReloadPage,
            ),
            new SoftDeleteWithReasonInteraction(
                'forum/posts/%s/soft-delete',
                static fn(Post $post) => !$post->isDeleted && ($post->isFirstPost() ? $post->getThread()->canTrash() : $post->canTrash()),
            ),
            new RestoreInteraction(
                'forum/posts/%s/restore',
                static fn(Post $post) => !$post->isFirstPost() && $post->canRestore(),
            ),
            new DeleteInteraction(
                'forum/posts/%s',
                static fn(Post $post) => $post->isDeleted && !$post->isFirstPost() && $post->canDelete(),
            ),
        ]);

        EventHandler::getInstance()->fire(new PostInteractionCollecting($this));
    }

    #[\Override]
    public function getObjectClassName(): string
    {
        return Post::class;
    }
}
