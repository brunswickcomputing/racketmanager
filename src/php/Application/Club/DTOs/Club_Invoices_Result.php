<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\DTOs;

use stdClass;

/**
 * Result DTO returned by Get_Club_Invoices_Handler.
 */
final readonly class Club_Invoices_Result {
    /**
     * @param array<int, mixed>|null $invoices_data
     */
    public function __construct(
        public ?object $club = null,
        public ?stdClass $user_can_manage = null,
        public ?object $invoice = null,
        public ?array $invoices_data = null,
        public string $currency_code = '',
        public string $date_format = '',
        public string $template_ref = 'invoices',
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
