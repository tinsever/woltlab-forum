<?php

namespace forum\system;

use forum\page\BoardListPage;
use wcf\system\application\AbstractApplication;

/**
 * Application core of the forum.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class FORUMCore extends AbstractApplication
{
    /**
     * @inheritDoc
     */
    protected $primaryController = BoardListPage::class;
}
