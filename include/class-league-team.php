<?php
/**
 * League_Team shim: loads PSR-4 class if not already loaded.
 */

namespace Racketmanager;

if ( ! class_exists( 'Racketmanager\\models\\League_Team', false ) ) {
    require_once RACKETMANAGER_PATH . 'src/php/models/League_Team.php';
    return;
}
