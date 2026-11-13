<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

/**
 * Command carrying the data required to add a user as a player to a club.
 */
final readonly class Add_Club_Player_Command {
    /**
     * Initialises the object with the specified club and user identifiers.
     *
     * @param int|null $club_id The club identifier, or null if not associated with a club.
     * @param int      $user_id The user identifier.
     *
     * @return void
     */
    public function __construct(
        public ?int $club_id,
        public int $user_id,
    ) {
    }
}
