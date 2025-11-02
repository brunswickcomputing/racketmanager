<?php
/**
 * Admin_Competition shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\admin\\Admin_Competition', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/Admin/Admin_Competition.php';
    return;
}
