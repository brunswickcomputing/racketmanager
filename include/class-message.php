<?php
/**
 * Legacy shim for Racketmanager\Message (PSR-4 relocation)
 * This file delegates to src/php/Message.php and remains for backward compatibility.
 */

namespace Racketmanager;

if (class_exists('Racketmanager\\Message', false)) {
    return;
}

$pluginRoot = \dirname(__DIR__) . '/';
require_once $pluginRoot . 'src/php/Message.php';
return;
