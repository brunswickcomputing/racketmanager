<?php
/**
 * Privacy_Exporters shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\Services\\Privacy_Exporters', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/Services/Privacy_Exporters.php';
    return;
}
