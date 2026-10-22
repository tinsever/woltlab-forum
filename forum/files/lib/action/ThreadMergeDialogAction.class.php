<?php

namespace forum\action;

use CuyZ\Valinor\Mapper\MappingError;
use forum\data\thread\Thread;
use forum\data\thread\ThreadAction;
use forum\data\thread\ThreadList;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use wcf\http\Helper;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\form\builder\field\RadioButtonFormField;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\form\builder\field\validation\FormFieldValidationError;
use wcf\system\form\builder\field\validation\FormFieldValidator;
use wcf\system\form\builder\Psr15DialogForm;
use wcf\system\WCF;

/**
 * Dialog to merge threads. With multiple selected threads one of them is chosen
 * as the target, a single thread is merged into a thread given by its id or link.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ThreadMergeDialogAction implements RequestHandlerInterface
{
    /**
     * @var Thread[]
     */
    private array $threads;

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->threads = $this->getThreads($request);

        $form = $this->getForm();
        if ($request->getMethod() === 'GET') {
            return $form->toResponse();
        }

        $response = $form->validateRequest($request);
        if ($response !== null) {
            return $response;
        }

        $data = $form->getData()['data'];
        $targetThreadID = \count($this->threads) > 1
            ? \intval($data['targetThreadID'])
            : self::parseThreadID((string)$data['targetThread']);

        $sourceThreads = \array_filter($this->threads, static fn(Thread $thread) => $thread->threadID !== $targetThreadID);
        (new ThreadAction($sourceThreads, 'merge', ['targetThreadID' => $targetThreadID]))->executeAction();

        return new JsonResponse([
            'redirectUrl' => (new Thread($targetThreadID))->getLink(),
        ]);
    }

    /**
     * Extracts a thread id from a plain id or a link to a thread.
     */
    public static function parseThreadID(string $value): int
    {
        $value = \trim($value);
        if (\preg_match('~^\d+$~', $value)) {
            return \intval($value);
        }

        if (\preg_match('~thread/(\d+)~', $value, $matches) || \preg_match('~[?&]id=(\d+)~', $value, $matches)) {
            return \intval($matches[1]);
        }

        return 0;
    }

    /**
     * @return Thread[]
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
        $threadList = new ThreadList();
        $threadList->setObjectIDs($threadIDs ?: [0]);
        $threadList->readObjects();
        $threads = $threadList->getObjects();
        if ($threads === []) {
            throw new IllegalLinkException();
        }

        foreach ($threads as $thread) {
            if (!$thread->canRead() || !$thread->canMerge() || $thread->isLink()) {
                throw new PermissionDeniedException();
            }
        }

        return $threads;
    }

    private function getForm(): Psr15DialogForm
    {
        $form = new Psr15DialogForm(static::class, WCF::getLanguage()->get('forum.thread.edit.merge'));

        if (\count($this->threads) > 1) {
            $options = [];
            foreach ($this->threads as $thread) {
                $options[$thread->threadID] = $thread->topic;
            }

            $form->appendChild(
                RadioButtonFormField::create('targetThreadID')
                    ->label('forum.thread.merge.target')
                    ->description('forum.thread.merge.target.description')
                    ->options($options, false, false)
                    ->value(\array_key_first($options))
                    ->required()
            );
        } else {
            $sourceThreadID = \array_key_first($this->threads);
            $form->appendChild(
                TextFormField::create('targetThread')
                    ->label('forum.thread.merge.targetThread')
                    ->description('forum.thread.merge.targetThread.description')
                    ->required()
                    ->addValidator(new FormFieldValidator('target', static function (TextFormField $field) use ($sourceThreadID) {
                        $threadID = ThreadMergeDialogAction::parseThreadID((string)$field->getValue());
                        $target = $threadID ? new Thread($threadID) : null;
                        if (
                            $target === null
                            || !$target->threadID
                            || $target->threadID === $sourceThreadID
                            || $target->isLink()
                            || !$target->canRead()
                            || !$target->canMerge()
                        ) {
                            $field->addValidationError(new FormFieldValidationError(
                                'invalid',
                                'forum.thread.merge.targetThread.error.invalid'
                            ));
                        }
                    }))
            );
        }

        $form->markRequiredFields(false);
        $form->build();

        return $form;
    }
}
