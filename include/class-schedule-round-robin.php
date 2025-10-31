<?php
/**
 * Schedule_Round_Robin shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\Services\\Schedule_Round_Robin', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/Services/Schedule_Round_Robin.php';
    return;
}
