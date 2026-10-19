<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Tournament;

/**
 * Command DTO for confirming a tournament withdrawal.
 */
readonly class Tournament_Withdrawal_Command {
    public function __construct(
        public ?int $tournament_id,
        public ?int $player_id
    ) {
    }

    /**
     * @param array<string, mixed> $post
     */
    public static function from_post( array $post ): self {
        return new self(
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
