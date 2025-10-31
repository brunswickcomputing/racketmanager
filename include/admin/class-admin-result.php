<?php
/**
 * Admin_Result shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\admin\\Admin_Result', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/admin/Admin_Result.php';
    return;
}
