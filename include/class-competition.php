<?php
/**
 * Competition shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\Domain\\Competition', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/Domain/Competition.php';
    return;
}
