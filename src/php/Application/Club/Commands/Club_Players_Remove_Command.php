<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Commands;

/**
 * Command DTO for removing club player registrations.
 */
readonly class Club_Players_Remove_Command {
    /**
     * Initialises the object with the provided club player identifiers and user identifier.
     *
     * @param array $club_player_ids Club player identifiers to be removed.
     * @param int   $user_id Identifier of the user performing the removal.
     *
     * @return void
     */
    public function __construct(
        public array $club_player_ids,
        public int $user_id
    ) {
    }

    /**
     * Creates an instance from submitted post data and a user identifier.
     *
     * @param array $post Posted form data for the current request.
     * @param int   $user_id Identifier of the user performing the removal.
     *
     * @return self
     */
    public static function from_post( array $post, int $user_id ): self {
        $fallback = empty( $post['clubPlayer'] ) ? array() : $post['clubPlayer'];
        $raw      = empty( $post['club_player'] ) ? $fallback : $post['club_player'];
        $ids      = array();
        foreach ( (array) $raw as $club_player_id ) {
            $ids[] = (int) $club_player_id;
        }

        return new self( $ids, $user_id );
    }
}
