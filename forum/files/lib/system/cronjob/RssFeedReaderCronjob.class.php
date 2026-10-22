<?php

namespace forum\system\cronjob;

use forum\data\rss\feed\RssFeed;
use forum\data\rss\feed\RssFeedList;
use forum\system\rss\RssFeedImporter;
use wcf\data\cronjob\Cronjob;
use wcf\system\cronjob\AbstractCronjob;

/**
 * Imports new entries of RSS feeds whose cycle time has elapsed.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class RssFeedReaderCronjob extends AbstractCronjob
{
    #[\Override]
    public function execute(Cronjob $cronjob)
    {
        parent::execute($cronjob);

        $feedList = new RssFeedList();
        $feedList->getConditionBuilder()->add('isDisabled = ?', [0]);
        $feedList->getConditionBuilder()->add('lastRun + cycleTime <= ?', [TIME_NOW]);
        $feedList->sqlOrderBy = 'lastRun ASC';
        $feedList->sqlLimit = 10;
        $feedList->readObjects();

        $importer = new RssFeedImporter();
        foreach ($feedList as $feed) {
            \assert($feed instanceof RssFeed);
            $importer->import($feed);
        }
    }
}
