<?php

namespace forum\action;

use CuyZ\Valinor\Mapper\MappingError;
use forum\data\board\Board;
use forum\data\board\BoardCache;
use forum\data\board\BoardNodeTree;
use forum\data\post\Post;
use forum\data\post\PostAction;
use forum\data\post\PostList;
use forum\data\thread\Thread;
use forum\data\thread\ThreadEditor;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use wcf\http\Helper;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\form\builder\field\dependency\ValueFormFieldDependency;
use wcf\system\form\builder\field\RadioButtonFormField;
use wcf\system\form\builder\field\SelectFormField;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\form\builder\field\validation\FormFieldValidationError;
use wcf\system\form\builder\field\validation\FormFieldValidator;
use wcf\system\form\builder\Psr15DialogForm;
use wcf\system\WCF;

/**
 * Base class for dialogs that transfer posts into an existing or a new thread.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
abstract class AbstractPostTransferDialogAction implements RequestHandlerInterface
{
    /**
     * name of the `PostAction` method that performs the transfer
     */
    abstract protected function getActionName(): string;

    abstract protected function getTitle(): string;

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $posts = $this->getPosts($request);

        $form = $this->getForm();
        if ($request->getMethod() === 'GET') {
            return $form->toResponse();
        }

        $response = $form->validateRequest($request);
        if ($response !== null) {
            return $response;
        }

        $data = $form->getData()['data'];
        if ($data['target'] === 'new') {
            $board = BoardCache::getInstance()->getBoard(\intval($data['boardID']));
            if ($board === null || !$board->isBoard() || !$board->getPermission('canMovePost')) {
                throw new PermissionDeniedException();
            }

            $thread = $this->createThread($board, $data['topic'], $posts);
        } else {
            $thread = self::getThreadFromInput($data['threadID']);
            if ($thread === null) {
                throw new IllegalLinkException();
            }
        }

        (new PostAction($posts, $this->getActionName(), ['thread' => $thread]))->executeAction();

        return new JsonResponse([
            'result' => [
                'redirectUrl' => (new Thread($thread->threadID))->getLink(),
            ],
        ]);
    }

    /**
     * Creates an empty thread whose data is derived from the oldest transferred post.
     *
     * @param Post[] $posts
     */
    private function createThread(Board $board, string $topic, array $posts): Thread
    {
        \usort($posts, static fn(Post $a, Post $b) => [$a->time, $a->postID] <=> [$b->time, $b->postID]);
        $firstPost = $posts[0];

        return ThreadEditor::create([
            'boardID' => $board->boardID,
            'languageID' => $firstPost->getThread()->languageID,
            'topic' => $topic,
            'time' => $firstPost->time,
            'userID' => $firstPost->userID,
            'username' => $firstPost->username,
            'lastPostTime' => $firstPost->time,
            'lastPosterID' => $firstPost->userID,
            'lastPoster' => $firstPost->username,
        ]);
    }

    /**
     * Accepts either a thread id or a link to a thread.
     */
    private static function getThreadFromInput(string $input): ?Thread
    {
        $input = \trim($input);
        if (\preg_match('~^\d+$~', $input)) {
            $threadID = \intval($input);
        } elseif (\preg_match('~thread/(\d+)~', $input, $matches)) {
            $threadID = \intval($matches[1]);
        } else {
            return null;
        }

        $thread = new Thread($threadID);
        if (!$thread->threadID || $thread->isLink() || !$thread->canRead()) {
            return null;
        }

        if (!$thread->getBoard()->getPermission('canMovePost')) {
            return null;
        }

        return $thread;
    }

    /**
     * @return Post[]
     */
    private function getPosts(ServerRequestInterface $request): array
    {
        try {
            $parameters = Helper::mapQueryParameters(
                $request->getQueryParams(),
                <<<'EOT'
                    array {
                        id?: positive-int,
                        ids?: positive-int[]
                    }
                    EOT
            );
        } catch (MappingError) {
            throw new IllegalLinkException();
        }

        $postIDs = isset($parameters['id']) ? [$parameters['id']] : ($parameters['ids'] ?? []);
        if ($postIDs === []) {
            throw new IllegalLinkException();
        }

        $postList = new PostList();
        $postList->setObjectIDs($postIDs);
        $postList->readObjects();
        $posts = $postList->getObjects();
        if ($posts === []) {
            throw new IllegalLinkException();
        }

        foreach ($posts as $post) {
            if (!$post->canRead() || !$post->canMove()) {
                throw new PermissionDeniedException();
            }
        }

        return $posts;
    }

    private function getForm(): Psr15DialogForm
    {
        $form = new Psr15DialogForm(static::class, $this->getTitle());
        $form->appendChildren([
            RadioButtonFormField::create('target')
                ->label('forum.post.transfer.target')
                ->options([
                    'existing' => 'forum.post.transfer.target.existing',
                    'new' => 'forum.post.transfer.target.new',
                ])
                ->value('existing')
                ->required(),
            TextFormField::create('threadID')
                ->label('forum.post.transfer.thread')
                ->description('forum.post.transfer.thread.description')
                ->required()
                ->addValidator(new FormFieldValidator('thread', static function (TextFormField $field) {
                    if (self::getThreadFromInput((string)$field->getValue()) === null) {
                        $field->addValidationError(new FormFieldValidationError(
                            'invalid',
                            'forum.post.transfer.thread.error.invalid'
                        ));
                    }
                }))
                ->addDependency(ValueFormFieldDependency::create('target')->fieldId('target')->values(['existing'])),
            SelectFormField::create('boardID')
                ->label('forum.board.board')
                ->options((new BoardNodeTree(0, static fn(Board $board) => $board->canEnter()))->getNestedOptions(
                    static fn(Board $board) => $board->isBoard() && $board->getPermission('canMovePost')
                ), true, false)
                ->required()
                ->addDependency(ValueFormFieldDependency::create('target')->fieldId('target')->values(['new'])),
            TextFormField::create('topic')
                ->label('forum.thread.topic')
                ->maximumLength(255)
                ->required()
                ->addDependency(ValueFormFieldDependency::create('target')->fieldId('target')->values(['new'])),
        ]);
        $form->markRequiredFields(false);
        $form->build();

        return $form;
    }
}
