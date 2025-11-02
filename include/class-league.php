<?php
/**
 * League shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\models\\League', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/Domain/League.php';
    return;
}
