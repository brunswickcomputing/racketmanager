<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Finance\Commands;

/**
 * Command DTO for updating payment status on an invoice.
 */
final readonly class Update_Payment_Command {
    /**
     * Constructor.
     *
     * @param string $reference Payment reference or payment intent ID.
     * @param string $status    Payment status (e.g. 'paid', 'pending', 'failed').
     */
    public function __construct(
        public string $reference,
        public string $status = 'paid'
    ) {
    }
}
