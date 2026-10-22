<?php

namespace forum\page;

/**
 * Shows the 'participated' thread list.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class ParticipatedThreadListPage extends AbstractThreadListPage
{
    /**
     * @inheritDoc
     */
    public $loginRequired = true;

    protected string $filter = 'participated';
}
