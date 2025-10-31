<?php
/**
 * League_Tennis class
 *
 * @package Racketmanager/Classes/Sports/Tennis
 */

namespace Racketmanager;

// PSR-4 shim: prefer the new location under src/php/sports/.
if ( ! class_exists( 'Racketmanager\\sports\\League_Tennis', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/sports/League_Tennis.php';
    return;
}
