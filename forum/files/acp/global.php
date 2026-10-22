<?php

// phpcs:disable PSR1.Files.SideEffects

if (!\defined('RELATIVE_FORUM_DIR')) {
    \define('RELATIVE_FORUM_DIR', '../');
}

// include config
require_once(__DIR__ . '/../app.config.inc.php');

// starting wcf acp
require_once(RELATIVE_WCF_DIR . 'acp/global.php');
