<?php
/**
 * Legacy shim for Racketmanager\Tournament
 *
 * This file remains for backward compatibility. It forwards to the PSR-4
 * implementation located at src/php/Tournament.php and returns immediately
 * to avoid class re-declaration.
 */

namespace Racketmanager;

// If the class is already loaded (e.g., via Composer PSR-4), do nothing.
if (class_exists( 'Racketmanager\\models\\Tournament', false)) {
    return;
}

// Resolve plugin root (RACKETMANAGER_PATH is normally defined by bootstrap)
$pluginRoot = \dirname(__DIR__) . '/';

// Load the PSR-4 implementation
require_once $pluginRoot . 'src/php/Domain/Tournament.php';

return;
