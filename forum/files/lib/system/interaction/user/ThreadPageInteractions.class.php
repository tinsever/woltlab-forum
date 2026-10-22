<?php

namespace forum\system\interaction\user;

/**
 * Thread interactions for the thread page, reloads the page after the thread
 * has been moved because the breadcrumbs change.
 *
 * The provider is instantiated by its class name when the context menu is
 * refreshed, therefore the setting is bound to a separate class.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class ThreadPageInteractions extends ThreadInteractions
{
    public function __construct()
    {
        parent::__construct(true);
    }
}
