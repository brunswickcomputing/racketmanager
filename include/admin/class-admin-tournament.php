<?php
/**
 * Admin_Tournament shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\admin\\Admin_Tournament', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/admin/Admin_Tournament.php';
    return;
}
