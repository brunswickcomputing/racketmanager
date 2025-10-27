<?php
/**
 * PSR-4 bridge for Racketmanager\util\Util
 * Phase 1: expose legacy class via PSR-4 while code remains under include/.
 */

namespace Racketmanager\util;

// Ensure plugin path constant exists (WordPress normally defines this in plugin bootstrap)
if (!\defined('RACKETMANAGER_PATH')) {
    // Attempt to infer path relative to the plugin root
    // Adjust upwards from src/php/util/ to plugin root
    $pluginRoot = \dirname(__DIR__, 2) . '/';
    if (!\defined('RACKETMANAGER_PATH')) {
        \define('RACKETMANAGER_PATH', $pluginRoot);
    }
}

// Load the legacy implementation which declares class Racketmanager\\util\\Util
require_once RACKETMANAGER_PATH . 'include/util/class-util.php';
