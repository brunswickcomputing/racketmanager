<?php
/**
 * Results_Checker shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\models\\Results_Checker', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/models/Results_Checker.php';
    return;
}
