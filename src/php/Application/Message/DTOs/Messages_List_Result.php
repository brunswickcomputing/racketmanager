<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Message\DTOs;

/**
 * Result DTO returned by Get_Messages_Handler.
 */
final readonly class Messages_List_Result {
    /**
     * @param array<string, mixed> $messages
     */
    public function __construct(
        public array $messages = [],
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
