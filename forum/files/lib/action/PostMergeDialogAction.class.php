<?php

namespace forum\action;

use CuyZ\Valinor\Mapper\MappingError;
use forum\data\post\Post;
use forum\data\post\PostAction;
use forum\data\post\PostList;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use wcf\http\Helper;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\NamedUserException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\form\builder\field\SelectFormField;
use wcf\system\form\builder\Psr15DialogForm;
use wcf\system\WCF;

/**
 * Dialog to merge posts of the same thread into a single post.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostMergeDialogAction implements RequestHandlerInterface
{
    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $posts = $this->getPosts($request);

        $form = $this->getForm($posts);
        if ($request->getMethod() === 'GET') {
            return $form->toResponse();
        }

        $response = $form->validateRequest($request);
        if ($response !== null) {
            return $response;
        }

        $targetPostID = \intval($form->getData()['data']['targetPostID']);
        (new PostAction($posts, 'merge', ['targetPostID' => $targetPostID]))->executeAction();

        return new JsonResponse([
            'result' => [
                'redirectUrl' => $posts[$targetPostID]->getLink(),
            ],
        ]);
    }

    /**
     * @return array<int, Post>
     */
    private function getPosts(ServerRequestInterface $request): array
    {
        try {
            $parameters = Helper::mapQueryParameters(
                $request->getQueryParams(),
                <<<'EOT'
                    array {
                        ids: positive-int[]
                    }
                    EOT
            );
        } catch (MappingError) {
            throw new IllegalLinkException();
        }

        $postList = new PostList();
        $postList->setObjectIDs($parameters['ids']);
        $postList->sqlOrderBy = 'post.time ASC, post.postID ASC';
        $postList->readObjects();
        $posts = $postList->getObjects();

        $threadIDs = [];
        foreach ($posts as $post) {
            if (!$post->canRead() || !$post->canMerge()) {
                throw new PermissionDeniedException();
            }
            $threadIDs[$post->threadID] = true;
        }

        if (\count($posts) < 2 || \count($threadIDs) !== 1) {
            throw new NamedUserException(WCF::getLanguage()->get('forum.post.merge.error.sameThread'));
        }

        return $posts;
    }

    /**
     * @param array<int, Post> $posts
     */
    private function getForm(array $posts): Psr15DialogForm
    {
        $options = [];
        foreach ($posts as $post) {
            $options[$post->postID] = WCF::getLanguage()->getDynamicVariable('forum.post.merge.option', [
                'post' => $post,
            ]);
        }

        $form = new Psr15DialogForm(static::class, WCF::getLanguage()->get('forum.post.edit.merge'));
        $form->appendChildren([
            SelectFormField::create('targetPostID')
                ->label('forum.post.merge.target')
                ->description('forum.post.merge.target.description')
                ->options($options, false, false)
                ->defaultValue((string)\array_key_first($options))
                ->required(),
        ]);
        $form->markRequiredFields(false);
        $form->build();

        return $form;
    }
}
