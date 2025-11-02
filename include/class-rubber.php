<?php
/**
 * Rubber shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\models\\Rubber', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/Domain/Rubber.php';
    return;
}
