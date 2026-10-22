<?php

namespace forum\action;

use CuyZ\Valinor\Mapper\MappingError;
use forum\data\board\Board;
use forum\data\board\BoardCache;
use forum\data\board\BoardNodeTree;
use forum\data\thread\ThreadAction;
use forum\data\thread\ThreadList;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use wcf\http\Helper;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\form\builder\field\BooleanFormField;
use wcf\system\form\builder\field\IntegerFormField;
use wcf\system\form\builder\field\SelectFormField;
use wcf\system\form\builder\field\dependency\NonEmptyFormFieldDependency;
use wcf\system\form\builder\Psr15DialogForm;
use wcf\system\WCF;

/**
 * Dialog to move one or more threads into another board.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ThreadMoveDialogAction implements RequestHandlerInterface
{
    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $threads = $this->getThreads($request);

        $form = $this->getForm();
        if ($request->getMethod() === 'GET') {
            return $form->toResponse();
        }

        $response = $form->validateRequest($request);
        if ($response !== null) {
            return $response;
        }

        $data = $form->getData()['data'];
        $board = BoardCache::getInstance()->getBoard(\intval($data['boardID']));
        if ($board === null || !$board->isBoard() || !$board->getPermission('canMoveThread')) {
            throw new PermissionDeniedException();
        }

        (new ThreadAction($threads, 'move', [
            'boardID' => $board->boardID,
            'leaveLink' => !empty($data['leaveLink']),
            'linkExpires' => \intval($data['linkExpires'] ?? 0),
        ]))->executeAction();

        return new JsonResponse([]);
    }

    /**
     * @return \forum\data\thread\Thread[]
     */
    private function getThreads(ServerRequestInterface $request): array
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

        $threadIDs = isset($parameters['id']) ? [$parameters['id']] : ($parameters['ids'] ?? []);
        if ($threadIDs === []) {
            throw new IllegalLinkException();
        }

        $threadList = new ThreadList();
        $threadList->setObjectIDs($threadIDs);
        $threadList->readObjects();
        $threads = $threadList->getObjects();
        if ($threads === []) {
            throw new IllegalLinkException();
        }

        foreach ($threads as $thread) {
            if (!$thread->canRead() || !$thread->canMove()) {
                throw new PermissionDeniedException();
            }
        }

        return $threads;
    }

    private function getForm(): Psr15DialogForm
    {
        $form = new Psr15DialogForm(static::class, WCF::getLanguage()->get('forum.thread.edit.move'));
        $form->appendChildren([
            SelectFormField::create('boardID')
                ->label('forum.board.board')
                ->options((new BoardNodeTree(0, static fn(Board $board) => $board->canEnter()))->getNestedOptions(
                    static fn(Board $board) => $board->isBoard() && $board->getPermission('canMoveThread')
                ), true, false)
                ->required(),
            BooleanFormField::create('leaveLink')
                ->label('forum.thread.move.leaveLink')
                ->description('forum.thread.move.leaveLink.description'),
            IntegerFormField::create('linkExpires')
                ->label('forum.thread.move.linkExpires')
                ->description('forum.thread.move.linkExpires.description')
                ->suffix('forum.thread.move.linkExpires.days')
                ->minimum(0)
                ->value(0)
                ->addDependency(NonEmptyFormFieldDependency::create('leaveLink')->fieldId('leaveLink')),
        ]);
        $form->markRequiredFields(false);
        $form->build();

        return $form;
    }
}
