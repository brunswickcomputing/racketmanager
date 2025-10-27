<?php
namespace Racketmanager;

// Legacy shim for Racketmanager\Player (PSR-4 relocation)
if (\class_exists('Racketmanager\\Player', false)) {
    return;
}
$pluginRoot = \dirname(__DIR__) . '/';
require_once $pluginRoot . 'src/php/Player.php';
return;
