<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

/**
 * Query for the [club-invoices] shortcode.
 */
final readonly class Get_Club_Invoices_Query {
    /**
     * @param string      $club_name   Club shortcode/name.
     * @param string|null $invoice_ref Single invoice reference, when viewing one invoice.
     * @param string      $template    Template suffix.
     * @param int|null    $user_id     Current user id; null when logged out.
     * @param bool        $is_admin    Whether the user holds the manage_racketmanager capability.
     */
    public function __construct(
        public string $club_name = '',
        public ?string $invoice_ref = null,
        public string $template = '',
        public ?int $user_id = null,
        public bool $is_admin = false
    ) {}
}
