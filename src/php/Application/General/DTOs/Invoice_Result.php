<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\DTOs;

/**
 * Result DTO returned by Get_Invoice_Handler.
 */
final readonly class Invoice_Result {
    /**
     * @param array<string, mixed> $billing
     */
    public function __construct(
        public ?object $invoice = null,
        public array $billing = [],
        public string $organisation_name = '',
        public string $invoice_number = '',
        public string $site_url = '',
        public string $date_format = 'd-m-Y',
        public bool $is_user_logged_in = false,
        public string $login_url = '',
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
