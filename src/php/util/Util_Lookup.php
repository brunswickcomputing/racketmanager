<?php
/**
 * PSR-4 bridge for Racketmanager\util\Util_Lookup
 * Phase 1: expose legacy class via PSR-4 while code remains under include/.
 */

namespace Racketmanager\util;

// Ensure plugin path constant exists (defined by plugin bootstrap)
if (!\defined('RACKETMANAGER_PATH')) {
    $pluginRoot = \dirname(__DIR__, 2) . '/';
    if (!\defined('RACKETMANAGER_PATH')) {
        \define('RACKETMANAGER_PATH', $pluginRoot);
    }
}

// Load the legacy implementation which declares class Racketmanager\\util\\Util_Lookup
require_once RACKETMANAGER_PATH . 'include/util/class-util-lookup.php';
