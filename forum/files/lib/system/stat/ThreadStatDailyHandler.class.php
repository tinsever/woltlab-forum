<?php

namespace forum\system\stat;

use wcf\system\stat\AbstractStatDailyHandler;

/**
 * Daily statistics for threads.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ThreadStatDailyHandler extends AbstractStatDailyHandler
{
    #[\Override]
    public function getData($date)
    {
        return [
            'counter' => $this->getCounter($date, 'forum1_thread', 'time'),
            'total' => $this->getTotal($date, 'forum1_thread', 'time'),
        ];
    }
}
