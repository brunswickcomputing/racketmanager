<?php
/**
 * Shortcodes_Event API: Shortcodes_Event class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Shortcodes/Event
 */

namespace Racketmanager\shortcodes;

// PSR-4 shim for relocated class. Prefer PSR-4 copy during transition.
if ( ! class_exists('Racketmanager\\shortcodes\\Shortcodes_Event', false) ) {
    require_once RACKETMANAGER_PATH . 'src/php/shortcodes/Shortcodes_Event.php';
    return;
}
