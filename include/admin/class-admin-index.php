<?php
/**
 * Admin_Index shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\admin\\Admin_Index', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/admin/Admin_Index.php';
    return;
}
