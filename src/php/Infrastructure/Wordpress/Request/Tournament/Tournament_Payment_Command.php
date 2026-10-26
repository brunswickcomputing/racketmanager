<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Request\Tournament;

/**
 * Command DTO for creating a tournament entry payment intent.
 */
readonly class Tournament_Payment_Command {
    public function __construct(
        public ?int $invoice_id,
        public ?int $tournament_id,
        public ?int $player_id
    ) {
    }

    /**
     * @param array<string, mixed> $post
     */
    public static function from_post( array $post ): self {
        return new self(
            invoice_id: self::parse_int_fallback( $post['invoiceId'] ?? null, $post['invoice_id'] ?? null ),
            tournament_id: self::parse_int_fallback( $post['tournamentId'] ?? null, $post['tournament_id'] ?? null ),
            player_id: self::parse_int_fallback( $post['playerId'] ?? null, $post['player_id'] ?? null )
        );
    }

    /**
     * Return the integer value of the first non-null and non-empty string argument.
     *
     * @param mixed ...$values
     * @return int|null
     */
    private static function parse_int_fallback( mixed ...$values ): ?int {
        foreach ( $values as $value ) {
            if ( null !== $value && '' !== $value ) {
                return intval( $value );
            }
        }

        return null;
    }
}
