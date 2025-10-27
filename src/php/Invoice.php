<?php
/**
 * PSR-4 bridge for Racketmanager\Invoice
 * Phase B: expose legacy class via PSR-4 while implementation remains under include/.
 */

namespace Racketmanager;

if (!\defined('RACKETMANAGER_PATH')) {
    $pluginRoot = \dirname(__DIR__) . '/';
    if (!\defined('RACKETMANAGER_PATH')) {
        \define('RACKETMANAGER_PATH', $pluginRoot);
    }
}

require_once RACKETMANAGER_PATH . 'include/class-invoice.php';
