<?php
/**
 * Results_Report shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\Domain\\Results_Report', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/Domain/Results_Report.php';
    return;
}
