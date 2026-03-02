<?php
/**
 * Admin message types
 *
 * @package RacketManager
 * @subpackage Domain/DTO/Admin
 */

namespace Racketmanager\Domain\DTO\Admin;

enum Admin_Message_Type: string {
    case SUCCESS = 'success';
    case WARNING = 'warning';
    case ERROR   = 'error';
    case INFO    = 'info';
}
