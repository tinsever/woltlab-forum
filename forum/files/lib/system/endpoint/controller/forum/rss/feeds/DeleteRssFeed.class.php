<?php

namespace forum\system\endpoint\controller\forum\rss\feeds;

use forum\data\rss\feed\RssFeed;
use forum\data\rss\feed\RssFeedAction;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\http\Helper;
use wcf\system\endpoint\DeleteRequest;
use wcf\system\endpoint\IController;
use wcf\system\WCF;

/**
 * Deletes an RSS feed.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
#[DeleteRequest('/forum/rss/feeds/{id:\d+}')]
final class DeleteRssFeed implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        WCF::getSession()->checkPermissions(['admin.forum.canManageRssFeed']);

        $feed = Helper::fetchObjectFromRequestParameter($variables['id'], RssFeed::class);
        (new RssFeedAction([$feed], 'delete'))->executeAction();

        return new JsonResponse([]);
    }
}
