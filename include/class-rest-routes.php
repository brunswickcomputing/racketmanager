<?php
/**
 * Rest_Routes shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\Rest\\Rest_Routes', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/Rest/Rest_Routes.php';
    return;
}
