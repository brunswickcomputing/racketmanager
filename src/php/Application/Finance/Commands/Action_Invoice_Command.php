<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Finance\Commands;

use function sanitize_text_field;
use function wp_unslash;

/**
 * Command DTO for performing an action or status update on an invoice.
 */
readonly class Action_Invoice_Command {
    public function __construct(
        public int $invoice_id,
        public string $action
    ) {
    }

    /**
     * @param int $invoice_id
     * @param string $action
     * @return self
     */
    public static function create( int $invoice_id, string $action ): self {
        return new self(
            invoice_id: $invoice_id,
            action: sanitize_text_field( wp_unslash( $action ) )
        );
    }
}
