<?php
/**
 * Admin_Import shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\admin\\Admin_Import', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/Admin/Admin_Import.php';
    return;
}
