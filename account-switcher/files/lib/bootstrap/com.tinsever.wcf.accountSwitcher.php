<?php

/**
 * Bootstrap script of the account switcher.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */

use wcf\system\event\EventHandler;

return static function (): void {
    EventHandler::getInstance()->register(
        \wcf\event\endpoint\ControllerCollecting::class,
        static function (\wcf\event\endpoint\ControllerCollecting $event) {
            $event->register(new \wcf\system\endpoint\controller\accountSwitcher\GetAccountSwitcher());
            $event->register(new \wcf\system\endpoint\controller\accountSwitcher\SwitchAccount());
            $event->register(new \wcf\system\endpoint\controller\accountSwitcher\UnlinkAccount());
        }
    );
};
