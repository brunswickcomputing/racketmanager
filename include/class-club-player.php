<?php
/**
 * Club_Player shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\Club_Player', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/models/Club_Player.php';
    return;
}
