<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Application\General\DTOs\Memberships_Result;
use Racketmanager\Application\Player\Ports\Player_Service_Interface;
use Racketmanager\Application\Player\Ports\Registration_Service_Interface;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use function __;

/**
 * Handles retrieval of membership data for a logged-in player.
 *
 * Validates that the query represents an authenticated user, resolves the
 * associated player, and loads both active and archived club memberships from
 * the registration service. Returns an error result when authentication fails,
 * required services are unavailable, or the player cannot be found.
 */
readonly class Get_Memberships_Handler {
    /**
     * Initialises the instance with optional player and registration service dependencies.
     *
     * @param Player_Service_Interface|null       $player_service Optional player service dependency.
     * @param Registration_Service_Interface|null $registration_service Optional registration service dependency.
     *
     * @return void
     */
    public function __construct(
        private ?Player_Service_Interface $player_service = null,
        private ?Registration_Service_Interface $registration_service = null
    ) {}

    /**
     * Handles a memberships query for the current user and returns active and archived club memberships.
     *
     * @param Get_Memberships_Query $query Query containing the current user context and template information.
     *
     * @return Memberships_Result Membership data result, or an error result if the user is not logged in, services are unavailable, or the player cannot be found.
     */
    public function handle( Get_Memberships_Query $query ): Memberships_Result {
        if ( ! $query->is_logged_in || ! $query->user_id ) {
            $result = Memberships_Result::error( __( 'You must be logged in to view memberships', 'racketmanager' ) );
        } elseif ( ! $this->player_service || ! $this->registration_service ) {
            $result = Memberships_Result::error( __( 'Player not found', 'racketmanager' ) );
        } else {
            try {
                $player            = $this->player_service->get_player( $query->user_id );
                $raw_clubs         = $this->registration_service->get_clubs_for_player( $player->get_id() );
                $raw_clubs_archive = $this->registration_service->get_clubs_for_player( $player->get_id(), 'removed' );

                $result = new Memberships_Result(
                    clubs: $raw_clubs,
                    clubs_archive: $raw_clubs_archive,
                    template: $query->template
                );
            } catch ( Player_Not_Found_Exception $e ) {
                $result = Memberships_Result::error( $e->getMessage() );
            }
        }

        return $result;
    }
}
