<?php
namespace Racketmanager;

// Legacy shim for Racketmanager\Invoice (PSR-4 relocation)
if (\class_exists( 'Racketmanager\\Domain\\Invoice', false)) {
    return;
}
$pluginRoot = \dirname(__DIR__) . '/';
require_once $pluginRoot . 'src/php/Domain/Invoice.php';
return;
