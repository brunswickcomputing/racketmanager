<?php
/**
 * PSR-4 bridge for Racketmanager\Competition_Tennis
 * Loads the legacy implementation from sports/class-competition-tennis.php.
 * This allows Composer PSR-4 autoloading to resolve the class without duplicating code.
 */

// Do not declare the class here; include the legacy file that declares it.
require_once RACKETMANAGER_PATH . 'sports/class-competition-tennis.php';
