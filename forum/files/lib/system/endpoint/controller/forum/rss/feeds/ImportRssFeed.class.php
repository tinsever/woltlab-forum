<?php

namespace forum\system\endpoint\controller\forum\rss\feeds;

use forum\data\rss\feed\RssFeed;
use forum\data\rss\feed\RssFeedAction;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\http\Helper;
use wcf\system\endpoint\IController;
use wcf\system\endpoint\PostRequest;
use wcf\system\WCF;

/**
 * Fetches an RSS feed immediately and imports its new entries.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
#[PostRequest('/forum/rss/feeds/{id:\d+}/import')]
final class ImportRssFeed implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        WCF::getSession()->checkPermissions(['admin.forum.canManageRssFeed']);

        $feed = Helper::fetchObjectFromRequestParameter($variables['id'], RssFeed::class);
        $result = (new RssFeedAction([$feed], 'import'))->executeAction();

        return new JsonResponse([
            'count' => $result['returnValues'][$feed->feedID] ?? 0,
        ]);
    }
}
