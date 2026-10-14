<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Club;

/**
 * Command DTO for requesting a player registration against a club.
 */
readonly class Club_Player_Request_Command {
    public function __construct(
        public ?int $club_id,
        public int $user_id
    ) {
    }

    /**
     * @param array<string, mixed> $post
     */
    public static function from_post( array $post, int $user_id ): self {
        return new self(
            club_id: isset( $post['club'] ) ? intval( $post['club'] ) : null,
            user_id: $user_id
        );
    }
}
