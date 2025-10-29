<?php
namespace Racketmanager;

// Legacy shim for Racketmanager\Charges (PSR-4 relocation)
if (\class_exists( 'Racketmanager\\models\\Charges', false)) {
    return;
}
$pluginRoot = \dirname(__DIR__) . '/';
require_once $pluginRoot . 'src/php/models/Charges.php';
return;
