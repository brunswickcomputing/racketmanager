<?php
/**
 * Legacy shim for Racketmanager\Club (PSR-4 relocation)
 * This file delegates to src/php/Club.php and remains for backward compatibility.
 */

namespace Racketmanager;

if (class_exists( 'Racketmanager\\Domain\\Club', false)) {
    return;
}

$pluginRoot = \dirname(__DIR__) . '/';
require_once $pluginRoot . 'src/php/Domain/Club.php';
return;
