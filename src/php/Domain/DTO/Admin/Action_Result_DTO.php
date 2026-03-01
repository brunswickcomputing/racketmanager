<?php
/**
 * Standard admin action result DTO
 *
 * @package RacketManager
 * @subpackage Domain/DTO/Admin
 */

namespace Racketmanager\Domain\DTO\Admin;

final class Action_Result_DTO {
    public function __construct(
        public readonly ?string $message = null,
        public readonly ?Admin_Message_Type $message_type = null,
        public readonly ?string $tab_override = null,
    ) {
    }
}
