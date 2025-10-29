<?php
/**
 * Legacy shim for Racketmanager\Season (PSR-4 relocation)
 * This file delegates to src/php/Season.php and remains for backward compatibility.
 */

namespace Racketmanager;

if (class_exists( 'Racketmanager\\models\\Season', false)) {
    return;
}

$pluginRoot = \dirname(__DIR__) . '/';
require_once $pluginRoot . 'src/php/models/Season.php';
return;
