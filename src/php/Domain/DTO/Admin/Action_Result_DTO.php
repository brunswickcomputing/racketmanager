<?php
declare( strict_types=1 );
/**
 * Standard admin action result DTO
 *
 * @package RacketManager
 * @subpackage Domain/DTO/Admin
 */

namespace Racketmanager\Domain\DTO\Admin;

readonly class Action_Result_DTO {
    public function __construct(
        public ?string $message = null,
        public ?Admin_Message_Type $message_type = null,
        public ?string $tab_override = null,
        public ?int $entity_id = null,
        public array $query_params = array(),
        /** @var array<string, mixed> Data to preserve in a PRG flash payload. */
        public array $flash_data = array(),
    ) {
    }
}
