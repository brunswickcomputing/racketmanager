<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

/**
 * Represents a command requesting deletion of one or more club player records.
 *
 * The provided club player identifiers are validated during construction to
 * ensure they are suitable for command handling.
 */
final readonly class Delete_Club_Players_Command {
    /**
     * Initialises the command with club player identifiers and a user identifier.
     *
     * @param array $club_player Club player identifiers to validate.
     * @param int   $user_id User identifier.
     *
     * @return void
     */
    public function __construct(
        public array $club_player,
        public int $user_id,
    ) {
        Command_Validation::ids( $this->club_player, 'clubPlayer' );
    }
}
