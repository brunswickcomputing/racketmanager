<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\DTOs;

final readonly class Order_Of_Play_Result {
    /**
     * @param array<string, mixed> $finals_order
     */
    public function __construct(
        public array $finals_order = [],
        public string $template = '',
        public ?string $error_message = null
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
