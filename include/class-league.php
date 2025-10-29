<?php
/**
 * League shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\League', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/League.php';
    return;
}
