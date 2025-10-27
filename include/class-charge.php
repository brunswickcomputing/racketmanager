<?php
namespace Racketmanager;

// Legacy shim for Racketmanager\Charges (PSR-4 relocation)
if (\class_exists('Racketmanager\\Charges', false)) {
    return;
}
$pluginRoot = \dirname(__DIR__) . '/';
require_once $pluginRoot . 'src/php/Charges.php';
return;
