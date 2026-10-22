<?php

namespace forum\system\importer;

use wcf\system\importer\AbstractPollOptionImporter;

/**
 * Imports the options of polls in posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostPollOptionImporter extends AbstractPollOptionImporter
{
    /**
     * @inheritDoc
     */
    protected $objectTypeName = 'com.woltlab.wbb.poll.option';

    /**
     * @inheritDoc
     */
    protected $pollObjectTypeName = 'com.woltlab.wbb.poll';
}
