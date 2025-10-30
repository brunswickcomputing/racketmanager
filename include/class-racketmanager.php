<?php
/**
 * RacketManager API: RacketManager class
 *
 * @author Paul Moffat
 * @package RacketManager
 */

namespace Racketmanager;

// Legacy shim: delegate to PSR-4 implementation
if (!\class_exists('Racketmanager\\RacketManager', false)) {
    $pluginRoot = \dirname(__DIR__) . '/';
    require_once $pluginRoot . 'src/php/RacketManager.php';
}
return;
