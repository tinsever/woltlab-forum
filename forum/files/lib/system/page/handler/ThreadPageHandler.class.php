<?php

namespace forum\system\page\handler;

use forum\system\cache\runtime\ThreadRuntimeCache;
use wcf\data\page\Page;
use wcf\data\user\online\UserOnline;
use wcf\system\page\handler\AbstractMenuPageHandler;
use wcf\system\page\handler\IOnlineLocationPageHandler;
use wcf\system\page\handler\TOnlineLocationPageHandler;
use wcf\system\WCF;

/**
 * Page handler for threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ThreadPageHandler extends AbstractMenuPageHandler implements IOnlineLocationPageHandler
{
    use TOnlineLocationPageHandler;

    #[\Override]
    public function getOnlineLocation(Page $page, UserOnline $user)
    {
        if ($user->pageObjectID === null) {
            return '';
        }

        $thread = ThreadRuntimeCache::getInstance()->getObject($user->pageObjectID);
        if ($thread === null || !$thread->canRead()) {
            return '';
        }

        return WCF::getLanguage()->getDynamicVariable('wcf.page.onlineLocation.' . $page->identifier, [
            'thread' => $thread,
        ]);
    }

    #[\Override]
    public function prepareOnlineLocation(Page $page, UserOnline $user)
    {
        if ($user->pageObjectID !== null) {
            ThreadRuntimeCache::getInstance()->cacheObjectID($user->pageObjectID);
        }
    }

    #[\Override]
    public function isVisible($objectID = null)
    {
        if ($objectID === null) {
            return true;
        }

        $thread = ThreadRuntimeCache::getInstance()->getObject($objectID);

        return $thread !== null && $thread->canRead();
    }

    #[\Override]
    public function cacheObject(int $objectID): void
    {
        ThreadRuntimeCache::getInstance()->cacheObjectID($objectID);
    }
}
