<?php
/**
 * PSR-4 bridge for Racketmanager\Charges
 * Phase B: expose legacy class via PSR-4 while implementation remains under include/.
 */

namespace Racketmanager;

// Ensure plugin path constant exists (WordPress bootstrap defines this)
if (!\defined('RACKETMANAGER_PATH')) {
    // Infer plugin root relative to this file: src/php/ -> plugin root
    $pluginRoot = \dirname(__DIR__) . '/';
    if (!\defined('RACKETMANAGER_PATH')) {
        \define('RACKETMANAGER_PATH', $pluginRoot);
    }
}

// Load the legacy implementation that declares class Racketmanager\\Charges
require_once RACKETMANAGER_PATH . 'include/class-charge.php';
