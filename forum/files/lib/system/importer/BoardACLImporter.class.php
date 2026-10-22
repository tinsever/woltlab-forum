<?php

namespace forum\system\importer;

use wcf\data\object\type\ObjectTypeCache;
use wcf\system\importer\AbstractACLImporter;

/**
 * Imports board permissions.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class BoardACLImporter extends AbstractACLImporter
{
    /**
     * @inheritDoc
     */
    protected $objectTypeName = 'com.woltlab.wbb.board';

    public function __construct()
    {
        $this->objectTypeID = ObjectTypeCache::getInstance()->getObjectTypeIDByName(
            'com.woltlab.wcf.acl',
            'com.tinsever.forum.board'
        );

        parent::__construct();
    }
}
