<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Club;

/**
 * Command DTO for removing club player registrations.
 */
readonly class Club_Players_Remove_Command {
    /**
     * @param int[] $club_player_ids
     */
    public function __construct(
        public array $club_player_ids,
        public int $user_id
    ) {
    }

    /**
     * @param array<string, mixed> $post
     */
    public static function from_post( array $post, int $user_id ): self {
        $raw = empty( $post['clubPlayer'] ) ? array() : $post['clubPlayer'];
        $ids = array();
        foreach ( (array) $raw as $club_player_id ) {
            $ids[] = (int) $club_player_id;
        }

        return new self( $ids, $user_id );
    }
}
