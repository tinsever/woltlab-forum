<?php

namespace forum\system\user\content\provider;

use forum\data\thread\Thread;
use wcf\system\user\content\provider\AbstractDatabaseUserContentProvider;

/**
 * User content provider for threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ThreadUserContentProvider extends AbstractDatabaseUserContentProvider
{
    #[\Override]
    public static function getDatabaseObjectClass()
    {
        return Thread::class;
    }
}
