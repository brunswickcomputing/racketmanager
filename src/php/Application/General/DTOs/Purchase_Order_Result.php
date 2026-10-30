<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\DTOs;

/**
 * Result DTO returned by Get_Purchase_Order_Handler.
 */
final readonly class Purchase_Order_Result {
    public function __construct(
        public ?object $invoice = null,
        public ?string $modal = null,
        public ?string $error_message = null,
        public ?string $template = null
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
