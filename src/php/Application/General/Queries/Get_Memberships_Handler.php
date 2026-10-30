<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Application\General\DTOs\Memberships_Result;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use function __;

class Get_Memberships_Handler {
    public function __construct(
        private readonly ?Player_Service $player_service = null,
        private readonly ?Registration_Service $registration_service = null
    ) {}

    public function handle( Get_Memberships_Query $query ): Memberships_Result {
        if ( ! $query->is_logged_in || ! $query->user_id ) {
            return Memberships_Result::error( __( 'You must be logged in to view memberships', 'racketmanager' ) );
        }

        if ( ! $this->player_service || ! $this->registration_service ) {
            return Memberships_Result::error( __( 'Player not found', 'racketmanager' ) );
        }

        try {
            $player            = $this->player_service->get_player( $query->user_id );
            $raw_clubs         = $this->registration_service->get_clubs_for_player( $player->get_id() );
            $raw_clubs_archive = $this->registration_service->get_clubs_for_player( $player->get_id(), 'removed' );

            return new Memberships_Result(
                clubs: $raw_clubs,
                clubs_archive: $raw_clubs_archive,
                template: $query->template
            );
        } catch ( Player_Not_Found_Exception $e ) {
            return Memberships_Result::error( $e->getMessage() );
        }
    }
}
