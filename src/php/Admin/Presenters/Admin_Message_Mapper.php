<?php
/**
 * Admin message mapper (domain -> legacy bridge)
 *
 * @package RacketManager
 * @subpackage Admin/Presenters
 */

namespace Racketmanager\Admin\Presenters;

use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;

final class Admin_Message_Mapper {

    /**
     * Bridge expects bool|string currently:
     * - true => error
     * - 'warning' => warning
     * - 'info' => info
     * - false => success
     *
     * @param Admin_Message_Type|null $type
     * @return bool|string
     */
    public static function to_legacy( ?Admin_Message_Type $type ): bool|string {
        return match ( $type ) {
            Admin_Message_Type::ERROR   => true,
            Admin_Message_Type::WARNING => 'warning',
            Admin_Message_Type::INFO    => 'info',
            Admin_Message_Type::SUCCESS => false,
            default => false,
        };
    }
}
