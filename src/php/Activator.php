<?php
/**
 * PSR-4 bridge for Racketmanager\Activator
 * Phase 1: Keep implementation in legacy include/ while exposing class via PSR-4.
 */

namespace Racketmanager;

// Ensure plugin path constant exists
if (!defined('RACKETMANAGER_PATH')) {
    // Attempt to infer path relative to this file
    define('RACKETMANAGER_PATH', plugin_dir_path(__DIR__ . '/../racketmanager.php'));
}

// Load the legacy implementation which declares class Racketmanager\Activator
require_once RACKETMANAGER_PATH . 'include/class-activator.php';
