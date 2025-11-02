<?php
/**
 * Player_Error shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\models\\Player_Error', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/Domain/Player_Error.php';
    return;
}
