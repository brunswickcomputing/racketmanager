<?php
/**
 * Admin_Finances shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\admin\\Admin_Finances', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/admin/Admin_Finances.php';
    return;
}
