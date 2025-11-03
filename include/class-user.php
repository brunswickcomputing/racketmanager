<?php
/**
 * User shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\Domain\\User', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/Domain/User.php';
    return;
}
