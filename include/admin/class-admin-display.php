<?php
/**
 * Admin_Event shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\admin\\Admin_Event', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/Admin/Admin_Event.php';
    return;
}
