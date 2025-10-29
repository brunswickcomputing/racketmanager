<?php
/**
 * League_Team shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\League_Team', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/League_Team.php';
    return;
}
