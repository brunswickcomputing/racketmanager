<?php
/**
 * Club_Role shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\Club_Role', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/models/Club_Role.php';
    return;
}
