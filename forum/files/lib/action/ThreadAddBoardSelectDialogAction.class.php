<?php

namespace forum\action;

use forum\data\board\Board;
use forum\data\board\BoardCache;
use forum\data\board\BoardNodeTree;
use forum\form\ThreadAddForm;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\form\builder\field\SelectFormField;
use wcf\system\form\builder\Psr15DialogForm;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;

/**
 * Asks for the board in which a new thread should be created.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ThreadAddBoardSelectDialogAction implements RequestHandlerInterface
{
    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = $this->getForm();
        if ($request->getMethod() === 'GET') {
            return $form->toResponse();
        }

        $response = $form->validateRequest($request);
        if ($response !== null) {
            return $response;
        }

        $board = BoardCache::getInstance()->getBoard(\intval($form->getData()['data']['boardID']));
        if ($board === null || !self::canStartThreadIn($board)) {
            throw new PermissionDeniedException();
        }

        return new JsonResponse([
            'result' => [
                'redirectUrl' => LinkHandler::getInstance()->getControllerLink(ThreadAddForm::class, [
                    'id' => $board->boardID,
                    'application' => 'forum',
                ]),
            ],
        ]);
    }

    /**
     * Returns true if the active user can start a thread in at least one board.
     */
    public static function canStartAnyThread(): bool
    {
        foreach (BoardCache::getInstance()->getBoards() as $board) {
            if (self::canStartThreadIn($board)) {
                return true;
            }
        }

        return false;
    }

    private static function canStartThreadIn(Board $board): bool
    {
        return $board->isBoard() && $board->canEnter() && $board->canStartThread();
    }

    private function getForm(): Psr15DialogForm
    {
        $form = new Psr15DialogForm(static::class, WCF::getLanguage()->get('forum.thread.add'));
        $form->appendChild(
            SelectFormField::create('boardID')
                ->label('forum.thread.add.board')
                ->description('forum.thread.add.board.description')
                ->options((new BoardNodeTree(0, static fn(Board $board) => $board->canEnter()))->getNestedOptions(
                    static fn(Board $board) => self::canStartThreadIn($board)
                ), true, false)
                ->required()
        );
        $form->markRequiredFields(false);
        $form->build();

        return $form;
    }
}
