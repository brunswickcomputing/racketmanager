<?php
/**
 * Racketmanager_Match shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\Domain\\Racketmanager_Match', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/Domain/Racketmanager_Match.php';
    return;
}
