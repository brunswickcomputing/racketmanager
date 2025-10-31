<?php
/**
 * Rewrites shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\Services\\Rewrites', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/Rewrites.php';
    return;
}
