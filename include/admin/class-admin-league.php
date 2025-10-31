<?php
/**
 * Admin_League shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\admin\\Admin_League', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/admin/Admin_League.php';
    return;
}
