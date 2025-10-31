<?php
/**
 * Login shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\Services\\Login', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/Services/Login.php';
    return;
}
