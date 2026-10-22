<?php

namespace forum\system\interaction\admin;

use forum\data\rss\feed\RssFeed;
use wcf\system\interaction\AbstractInteractionProvider;
use wcf\system\interaction\DeleteInteraction;
use wcf\system\interaction\InteractionEffect;
use wcf\system\interaction\RpcInteraction;
use wcf\system\WCF;

/**
 * Interaction provider for RSS feeds.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class RssFeedInteractions extends AbstractInteractionProvider
{
    public function __construct()
    {
        if (!WCF::getSession()->getPermission('admin.forum.canManageRssFeed')) {
            return;
        }

        $this->addInteractions([
            new RpcInteraction(
                'import',
                'forum/rss/feeds/%s/import',
                'forum.acp.rssFeed.import',
                interactionEffect: InteractionEffect::ReloadItem
            ),
            new DeleteInteraction('forum/rss/feeds/%s'),
        ]);
    }

    #[\Override]
    public function getObjectClassName(): string
    {
        return RssFeed::class;
    }
}
