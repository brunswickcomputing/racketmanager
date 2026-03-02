<?php
/**
 * Draw page action response DTO
 *
 * @package RacketManager
 * @subpackage Domain/DTO/Admin/Championship
 */

namespace Racketmanager\Domain\DTO\Admin\Championship;

use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;

final readonly class Draw_Action_Response_DTO {
    public function __construct(
        public ?string $message = null,
        public ?Admin_Message_Type $message_type = null,
        public ?string $tab_override = null,
    ) {
    }
}
