<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Message\DTOs;

use Racketmanager\Domain\Communication\Message;

/**
 * Result DTO returned by Get_Message_Handler.
 */
final readonly class Message_Detail_Result {
    public function __construct(
        public ?object $message = null,
        public ?string $error_message = null,
        public string $template = ''
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
