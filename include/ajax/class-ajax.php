<?php
/**
 * Legacy shim for Racketmanager\ajax\Ajax (PSR-4 relocation)
 * This file delegates to src/php/Ajax/Ajax.php and remains for backward compatibility.
 */

namespace Racketmanager\Ajax;

// If already loaded via Composer PSR-4, exit early.
if (\class_exists('Racketmanager\\ajax\\Ajax', false)) {
    return;
}

// Compute plugin root from include/ directory
$pluginRoot = \dirname(__DIR__, 2) . '/';

// Require the PSR-4 implementation
require_once $pluginRoot . 'src/php/Ajax/Ajax.php';

return;
