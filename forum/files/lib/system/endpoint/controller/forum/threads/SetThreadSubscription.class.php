<?php

namespace forum\system\endpoint\controller\forum\threads;

use forum\data\thread\Thread;
use forum\system\thread\ThreadStatusHandler;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\http\Helper;
use wcf\system\endpoint\IController;
use wcf\system\endpoint\PostRequest;
use wcf\system\exception\PermissionDeniedException;

/**
 * Changes the subscription status of the active user for a thread.
 * `status` is one of `watching`, `ignoring` or empty to reset it.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
#[PostRequest('/forum/threads/{id:\d+}/subscription')]
final class SetThreadSubscription implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $thread = Helper::fetchObjectFromRequestParameter($variables['id'], Thread::class);
        if (!$thread->canRead() || !$thread->canSubscribe()) {
            throw new PermissionDeniedException();
        }

        $parameters = Helper::mapApiParameters($request, SetThreadSubscriptionParameters::class);
        $status = match ($parameters->status) {
            'watching' => ThreadStatusHandler::STATUS_WATCHING,
            'ignoring' => ThreadStatusHandler::STATUS_IGNORING,
            default => null,
        };

        ThreadStatusHandler::saveStatus($thread->threadID, $status, $parameters->enableNotification);

        return new JsonResponse([
            'status' => $status ?? '',
        ]);
    }
}

/** @internal */
final class SetThreadSubscriptionParameters
{
    public function __construct(
        public readonly string $status = '',
        public readonly bool $enableNotification = true,
    ) {}
}
