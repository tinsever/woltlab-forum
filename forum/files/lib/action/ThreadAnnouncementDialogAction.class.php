<?php

namespace forum\action;

use CuyZ\Valinor\Mapper\MappingError;
use forum\data\board\Board;
use forum\data\board\BoardNodeTree;
use forum\data\thread\Thread;
use forum\data\thread\ThreadAction;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use wcf\http\Helper;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\form\builder\field\MultipleSelectionFormField;
use wcf\system\form\builder\Psr15DialogForm;
use wcf\system\WCF;

/**
 * Dialog to turn a thread into an announcement and to select the boards it is shown in.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ThreadAnnouncementDialogAction implements RequestHandlerInterface
{
    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $parameters = Helper::mapQueryParameters(
                $request->getQueryParams(),
                <<<'EOT'
                    array {
                        id: positive-int
                    }
                    EOT
            );
        } catch (MappingError) {
            throw new IllegalLinkException();
        }

        $thread = Helper::fetchObjectFromRequestParameter($parameters['id'], Thread::class);
        if (!$thread->canRead() || !$thread->canSetAnnouncement() || $thread->isLink()) {
            throw new PermissionDeniedException();
        }

        $form = $this->getForm($thread);
        if ($request->getMethod() === 'GET') {
            return $form->toResponse();
        }

        $response = $form->validateRequest($request);
        if ($response !== null) {
            return $response;
        }

        $boardIDs = \array_map('intval', $form->getData()['boardIDs'] ?? []);
        (new ThreadAction([$thread], 'setAnnouncement', ['boardIDs' => $boardIDs]))->executeAction();

        return new JsonResponse([]);
    }

    private function getForm(Thread $thread): Psr15DialogForm
    {
        $sql = "SELECT  boardID
                FROM    forum1_thread_announcement
                WHERE   threadID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$thread->threadID]);
        $selected = $statement->fetchAll(\PDO::FETCH_COLUMN) ?: [$thread->boardID];

        $form = new Psr15DialogForm(static::class, WCF::getLanguage()->get('forum.thread.edit.announcement'));
        $form->appendChildren([
            MultipleSelectionFormField::create('boardIDs')
                ->label('forum.thread.announcement.boards')
                ->description('forum.thread.announcement.boards.description')
                ->options((new BoardNodeTree(0, static fn(Board $board) => $board->canEnter()))->getNestedOptions(
                    static fn(Board $board) => $board->isBoard() && $board->getPermission('canStartAnnouncement')
                ), true, false)
                ->filterable()
                ->value($selected)
                ->required(),
        ]);
        $form->markRequiredFields(false);
        $form->build();

        return $form;
    }
}
