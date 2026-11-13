<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use Racketmanager\Application\Club\Ports\Club_Service_Interface;
use Racketmanager\Application\Player\Ports\Player_Service_Interface;

/**
 * Provides club selection data for entry workflows.
 *
 * Determines which clubs are available to a user by delegating to club and player services according to the user's administrative status, selected club, and entry level.
 */
readonly class Club_Entry_Selection_Service {
    /**
     * Initialises the service with dependencies for club and player operations.
     *
     * @param Club_Service_Interface   $club_service Service used to handle club-related operations.
     * @param Player_Service_Interface $player_service Service used to handle player-related operations.
     *
     * @return void
     */
    public function __construct(
        private Club_Service_Interface $club_service,
        private Player_Service_Interface $player_service
    ) {}

    /**
     * Retrieves the clubs available to a user based on administrative access, club selection, and entry level.
     *
     * @param bool        $is_admin Whether the user has administrative access.
     * @param int         $user_id The ID of the user whose associated clubs should be retrieved.
     * @param int|null    $club_id Optional club ID used to filter results.
     * @param string|null $entry_level Optional entry level used to find associated clubs for non-admin users.
     *
     * @return object|int|bool|array Available club data, a count, or false when no clubs are available or required criteria are missing.
     */
    public function available_clubs( bool $is_admin, int $user_id, ?int $club_id, ?string $entry_level ): object|int|bool|array {
        if ( $is_admin ) {
            $args = [ 'type' => 'affiliated' ];
            if ( $club_id ) {
                $args['club']  = $club_id;
                $args['count'] = true;
            }
            return $this->club_service->get_clubs( $args ) ?: false;
        }

        if ( ! $entry_level ) {
            return false;
        }

        return $this->player_service->find_all_associated_clubs( $user_id, $entry_level, $club_id ) ?: false;
    }
}
