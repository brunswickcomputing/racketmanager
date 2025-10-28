<?php
namespace Racketmanager\ajax;

// Legacy shim for Racketmanager\ajax\Ajax_Frontend (PSR-4 relocation)
if (\class_exists('Racketmanager\\ajax\\Ajax_Frontend', false)) {
    return;
}

$pluginRoot = \dirname(__DIR__, 2) . '/';
require_once $pluginRoot . 'src/php/ajax/Ajax_Frontend.php';
return;
