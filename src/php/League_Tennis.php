<?php
/**
 * PSR-4 bridge for Racketmanager\League_Tennis
 * Loads the legacy implementation from sports/class-league-tennis.php.
 * This allows Composer PSR-4 autoloading to resolve the class without duplicating code.
 */

// Do not declare the class here; include the legacy file that declares it.
require_once RACKETMANAGER_PATH . 'sports/class-league-tennis.php';
