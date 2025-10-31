<?php
/**
 * Util_Lookup shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\util\\Util_Lookup', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/util/Util_Lookup.php';
    return;
}
