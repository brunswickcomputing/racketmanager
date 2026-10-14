<?php

namespace Racketmanager\Domain\DTO\Finance;

/**
 * Command for setting a purchase order on an invoice via AJAX.
 */
class Set_Purchase_Order_Command {
    public function __construct(
        public readonly ?int $invoice_id,
        public readonly ?string $purchase_order,
        public readonly ?string $modal = null
    ) {
    }

    public static function from_post( array $post ): self {
        $invoice_id     = isset( $post['invoiceId'] ) ? (int) $post['invoiceId'] : null;
        $purchase_order = isset( $post['purchaseOrder'] ) ? sanitize_text_field( wp_unslash( $post['purchaseOrder'] ) ) : null;
        $modal          = isset( $post['modal'] ) ? sanitize_text_field( wp_unslash( $post['modal'] ) ) : null;

        return new self(
            invoice_id: $invoice_id,
            purchase_order: $purchase_order,
            modal: $modal
        );
    }
}
