<?php
namespace Racketmanager\ajax;

// Legacy shim for Racketmanager\ajax\Ajax_Match (PSR-4 relocation)
if (\class_exists('Racketmanager\\ajax\\Ajax_Match', false)) {
    return;
}

$pluginRoot = \dirname(__DIR__, 2) . '/';
require_once $pluginRoot . 'src/php/ajax/Ajax_Match.php';
return;
