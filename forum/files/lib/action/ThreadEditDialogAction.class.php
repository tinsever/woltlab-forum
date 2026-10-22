<?php

namespace forum\action;

use CuyZ\Valinor\Mapper\MappingError;
use forum\data\thread\Thread;
use forum\data\thread\ThreadAction;
use forum\system\label\object\ThreadLabelObjectHandler;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use wcf\http\Helper;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\form\builder\field\label\LabelFormField;
use wcf\system\form\builder\field\tag\TagFormField;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\form\builder\Psr15DialogForm;
use wcf\system\WCF;

/**
 * Dialog to edit the topic, the labels and the tags of a thread.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ThreadEditDialogAction implements RequestHandlerInterface
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
        if (!$thread->canRead() || !$thread->canEdit() || $thread->isLink()) {
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

        $data = $form->getData();
        $actionParameters = [
            'data' => ['topic' => $data['data']['topic']],
            'labelIDs' => \array_filter($data['labelIDs'] ?? []),
        ];
        if ($this->canSetTags($thread)) {
            $actionParameters['tags'] = $data['tags'] ?? [];
        }

        (new ThreadAction([$thread], 'update', $actionParameters))->executeAction();

        return new JsonResponse([]);
    }

    private function canSetTags(Thread $thread): bool
    {
        return \MODULE_TAGGING
            && WCF::getSession()->getPermission('user.tag.canViewTag')
            && $thread->getPermission('canSetTags');
    }

    private function getForm(Thread $thread): Psr15DialogForm
    {
        $form = new Psr15DialogForm(static::class, WCF::getLanguage()->get('forum.thread.edit'));
        $form->appendChildren([
            TextFormField::create('topic')
                ->label('forum.thread.topic')
                ->maximumLength(255)
                ->censorship()
                ->required(),
        ]);

        $labelGroups = ThreadLabelObjectHandler::getLabelGroupsForBoard($thread->boardID);
        if ($labelGroups !== []) {
            $form->appendChildren(LabelFormField::createFields('com.tinsever.forum.thread', $labelGroups));
        }

        if ($this->canSetTags($thread)) {
            $form->appendChild(
                TagFormField::create()
                    ->objectType('com.tinsever.forum.thread')
            );
        }

        $form->markRequiredFields(false);
        $form->updatedObject($thread);
        $form->build();

        return $form;
    }
}
