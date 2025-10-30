<?php
/**
 * Racketmanager_Match shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\models\\Racketmanager_Match', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/models/Racketmanager_Match.php';
    return;
}
