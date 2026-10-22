<?php

namespace forum\system\stat;

use wcf\system\stat\AbstractStatDailyHandler;

/**
 * Daily statistics for posts.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class PostStatDailyHandler extends AbstractStatDailyHandler
{
    #[\Override]
    public function getData($date)
    {
        return [
            'counter' => $this->getCounter($date, 'forum1_post', 'time'),
            'total' => $this->getTotal($date, 'forum1_post', 'time'),
        ];
    }
}
