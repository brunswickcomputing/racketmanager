<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Finance\Commands;

/**
 * Command DTO for generating and sending invoices for a charge.
 */
readonly class Generate_Charge_Invoices_Command {
    public function __construct(
        public int $charge_id,
        public bool $send_email = true
    ) {
    }

    /**
     * @param array<string, mixed> $post
     * @return self
     */
    public static function from_post( array $post ): self {
        $charge_id = isset( $post['chargesId'] ) && '' !== (string) $post['chargesId']
            ? (int) $post['chargesId']
            : ( isset( $post['charges_id'] ) && '' !== (string) $post['charges_id']
                ? (int) $post['charges_id']
                : ( isset( $post['charge_id'] ) && '' !== (string) $post['charge_id'] ? (int) $post['charge_id'] : 0 )
            );

        $send_email = true;
        if ( array_key_exists( 'send_email', $post ) ) {
            $val = $post['send_email'];
            $send_email = ! ( $val === false || $val === 0 || $val === '0' || $val === 'false' || $val === 'no' );
        } elseif ( array_key_exists( 'send', $post ) ) {
            $val = $post['send'];
            $send_email = ! ( $val === false || $val === 0 || $val === '0' || $val === 'false' || $val === 'no' );
        }

        return new self(
            charge_id: $charge_id,
            send_email: $send_email
        );
    }
}
