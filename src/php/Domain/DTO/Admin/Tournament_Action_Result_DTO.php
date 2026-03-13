<?php
/**
 * Tournament action result DTO
 *
 * @package RacketManager
 * @subpackage Domain/DTO/Admin
 */

namespace Racketmanager\Domain\DTO\Admin;

readonly class Tournament_Action_Result_DTO {
    public const string INTENT_NONE = 'none';
    public const string INTENT_ADD  = 'add';
    public const string INTENT_EDIT = 'edit';

    public function __construct(
        public string $intent = self::INTENT_NONE,
        public ?int $tournament_id = null,
        public ?string $message = null,
        public ?Admin_Message_Type $message_type = null,
        public mixed $raw_error = null,
    ) {
    }
}
