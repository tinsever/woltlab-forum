<?php

namespace forum\data\modification\log;

use wcf\data\DatabaseObjectDecorator;
use wcf\data\ITitledLinkObject;
use wcf\data\modification\log\IViewableModificationLog;
use wcf\data\modification\log\ModificationLog;
use wcf\data\object\type\ObjectTypeCache;
use wcf\data\user\UserProfile;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\WCF;

/**
 * Viewable modification log entry for threads and posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @mixin ModificationLog
 * @extends DatabaseObjectDecorator<ModificationLog>
 */
class ViewableForumModificationLog extends DatabaseObjectDecorator implements IViewableModificationLog
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = ModificationLog::class;

    protected ?ITitledLinkObject $affectedObject = null;

    public function setAffectedObject(ITitledLinkObject $object): void
    {
        $this->affectedObject = $object;
    }

    #[\Override]
    public function getAffectedObject()
    {
        return $this->affectedObject;
    }

    public function getUserProfile(): UserProfile
    {
        if ($this->userID) {
            return UserProfileRuntimeCache::getInstance()->getObject($this->userID)
                ?? UserProfile::getGuestUserProfile($this->username);
        }

        return UserProfile::getGuestUserProfile($this->username);
    }

    /**
     * Returns `thread` or `post`.
     */
    public function getObjectTypeName(): string
    {
        $objectType = ObjectTypeCache::getInstance()->getObjectType($this->objectTypeID);

        return $objectType->objectType === 'com.tinsever.forum.post' ? 'post' : 'thread';
    }

    public function __toString(): string
    {
        return WCF::getLanguage()->getDynamicVariable(
            'forum.' . $this->getObjectTypeName() . '.log.' . $this->action,
            [
                'additionalData' => $this->additionalData,
                'log' => $this,
            ]
        );
    }
}
