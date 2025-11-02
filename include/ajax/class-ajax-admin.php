<?php
/**
 * Ajax_Admin shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\ajax\\Ajax_Admin', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/Ajax/Ajax_Admin.php';
    return;
}
