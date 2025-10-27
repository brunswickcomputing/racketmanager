<?php
namespace Racketmanager;

// Legacy shim for Racketmanager\Tournament_Entry (PSR-4 relocation)
if (\class_exists('Racketmanager\\Tournament_Entry', false)) {
    return;
}
$pluginRoot = \dirname(__DIR__) . '/';
require_once $pluginRoot . 'src/php/Tournament_Entry.php';
return;
