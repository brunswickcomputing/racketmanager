<?php
/**
 * Tournament contact action result DTO
 *
 * @package RacketManager
 * @subpackage Domain/DTO/Admin
 */

namespace Racketmanager\Domain\DTO\Admin;

readonly class Tournament_Contact_Action_Result_DTO {
    public const INTENT_NONE = 'none';
    public const INTENT_PREVIEW = 'preview';
    public const INTENT_SEND = 'send';
    public const INTENT_SEND_ACTIVE = 'send_active';

    public function __construct(
        public string $intent = self::INTENT_NONE,
        public ?string $message = null,
        public ?Admin_Message_Type $message_type = null,
    ) {
    }
}
