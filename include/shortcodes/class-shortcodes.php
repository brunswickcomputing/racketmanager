<?php

/**
 * Shortcodes API: RacketManagerShortcodes class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerShortcodes
 */

namespace Racketmanager\shortcodes;

// PSR-4 shim for relocated class during transition.
if ( ! class_exists('Racketmanager\\shortcodes\\Shortcodes', false) ) {
    require_once RACKETMANAGER_PATH . 'src/php/shortcodes/Shortcodes.php';
    return;
}

// Compute plugin root from include/ directory
$pluginRoot = \dirname(__DIR__, 2) . '/';

// Require the PSR-4 implementation
require_once $pluginRoot . 'src/php/shortcodes/Shortcodes.php';

return;
