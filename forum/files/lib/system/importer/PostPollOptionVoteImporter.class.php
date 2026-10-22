<?php

namespace forum\system\importer;

use wcf\system\importer\AbstractPollOptionVoteImporter;

/**
 * Imports the votes of polls in posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostPollOptionVoteImporter extends AbstractPollOptionVoteImporter
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
