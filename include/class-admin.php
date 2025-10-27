<?php
/**
 * Legacy shim for Racketmanager\Admin
 * This file remains for backward compatibility after migrating the implementation to src/php/Admin.php.
 */

namespace Racketmanager;

// If the class is already loaded (via Composer PSR-4), do nothing.
if (class_exists('Racketmanager\\Admin', false)) {
    return;
}

// Compute plugin root from include/ directory
$pluginRoot = \dirname(__DIR__) . '/';

// Require the PSR-4 implementation
require_once $pluginRoot . 'src/php/Admin.php';
