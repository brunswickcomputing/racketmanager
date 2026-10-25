<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Finance\Commands;

/**
 * Command DTO for setting a purchase order on an invoice.
 */
readonly class Set_Purchase_Order_Command {
    public function __construct(
        public ?int $invoice_id,
        public ?string $purchase_order,
        public ?string $modal = null
    ) {
    }

    /**
     * @param array<string, mixed> $post
     * @return self
     */
    public static function from_post( array $post ): self {
        $invoice_id     = isset( $post['invoiceId'] ) ? (int) $post['invoiceId'] : null;
        $purchase_order = isset( $post['purchaseOrder'] ) ? sanitize_text_field( wp_unslash( (string) $post['purchaseOrder'] ) ) : null;
        $modal          = isset( $post['modal'] ) ? sanitize_text_field( wp_unslash( (string) $post['modal'] ) ) : null;

        return new self(
            invoice_id: $invoice_id,
            purchase_order: $purchase_order,
            modal: $modal
        );
    }
}
