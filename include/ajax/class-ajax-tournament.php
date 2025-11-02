<?php
namespace Racketmanager\Ajax;

// Legacy shim for Racketmanager\ajax\Ajax_Tournament (PSR-4 relocation)
if (\class_exists('Racketmanager\\ajax\\Ajax_Tournament', false)) {
    return;
}

$pluginRoot = \dirname(__DIR__, 2) . '/';
require_once $pluginRoot . 'src/php/Ajax/Ajax_Tournament.php';
return;
