<?php
/**
 * Rest_Resources shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\Rest\\Rest_Resources', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/Rest/Rest_Resources.php';
    return;
}
