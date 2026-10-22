<?php

namespace forum\system\box;

use forum\system\cache\tolerant\ForumStatisticsCache;
use wcf\system\box\AbstractBoxController;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\WCF;

/**
 * Box with the forum statistics.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ForumStatisticsBoxController extends AbstractBoxController
{
    /**
     * @inheritDoc
     */
    protected static $supportedPositions = ['sidebarLeft', 'sidebarRight', 'contentTop', 'contentBottom', 'footerBoxes'];

    #[\Override]
    protected function loadContent()
    {
        $stats = (new ForumStatisticsCache())->getCache();

        $this->content = WCF::getTPL()->render('forum', 'boxForumStatistics', [
            'stats' => $stats,
            'newestMember' => $stats['newestMemberID']
                ? UserProfileRuntimeCache::getInstance()->getObject($stats['newestMemberID'])
                : null,
        ]);
    }
}
