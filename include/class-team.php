<?php
/**
 * Legacy shim for Racketmanager\Team (PSR-4 relocation)
 * This file delegates to src/php/Team.php and remains for backward compatibility.
 */

namespace Racketmanager;

if (class_exists( 'Racketmanager\\models\\Team', false)) {
    return;
}

$pluginRoot = \dirname(__DIR__) . '/';
require_once $pluginRoot . 'src/php/Domain/Team.php';
return;
