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
            tournament_id: isset( $post['tournament_id'] ) ? intval( $post['tournament_id'] ) : null,
            player_id: isset( $post['playerId'] ) ? intval( $post['playerId'] ) : null
        );
    }
}
