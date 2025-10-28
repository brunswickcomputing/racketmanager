<?php
namespace Racketmanager\ajax;

// Legacy shim for Racketmanager\ajax\Ajax_Finance (PSR-4 relocation)
if (\class_exists('Racketmanager\\ajax\\Ajax_Finance', false)) {
    return;
}

$pluginRoot = \dirname(__DIR__, 2) . '/';
require_once $pluginRoot . 'src/php/ajax/Ajax_Finance.php';
return;
