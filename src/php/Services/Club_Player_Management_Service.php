<?php

namespace Racketmanager\Services;

use Racketmanager\Domain\Club_Player;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Already_Registered_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Registration_Not_Found_Exception;
use Racketmanager\Repositories\Club_Player_Repository;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Player_Repository;
use stdClass;
use WP_Error;
use function Racketmanager\debug_to_console;

class Club_Player_Management_Service {
    private Club_Player_Repository $club_player_repository;
    private Player_Repository $player_repository;
    private Club_Repository $club_repository;
    private Player_Management_Service $player_service;

    public function __construct( Club_Player_Repository $club_player_repository, Player_Repository $player_repository, Club_Repository $club_repository, Player_Management_Service $player_service ) {
        $this->club_player_repository = $club_player_repository;
        $this->player_repository      = $player_repository;
        $this->club_repository        = $club_repository;
        $this->player_service         = $player_service;
    }

    /**
     * Register player to club
     *
     * @param int|null $club_id
     * @param int|null $registered_by_userId
     *
     * @return Club_Player|WP_Error
     */
    public function register_player_to_club( ?int $club_id, ?int $registered_by_userId = null ): Club_Player|WP_Error {
        debug_to_console( 'in register_player_to_club');
        debug_to_console( $club_id);
        $club = $this->club_repository->find( $club_id );
        if ( ! $club ) {
            throw new Club_Not_Found_Exception( __( 'Club not found', 'racketmanager' ) );
        }
        $player = $this->player_service->validate_player();
        if ( is_wp_error( $player ) ) {
            return $player;
        }
        if ( empty( $player->id ) ) {
            $player = $this->player_service->add_player( $player );
        }
        $player = $this->player_repository->find( $player->id );
        if ( ! $player ) {
            throw new Player_Not_Found_Exception( __('Player not found or inactive', 'racketmanager' ) );
        }

        if ( $this->club_player_repository->find_by_club_and_player( $club_id, $player->id ) ) {
            throw new Player_Already_Registered_Exception( __( 'Player already registered to this club','racketmanager' ) );
        }

        $club_player                 = new stdClass();
        $club_player->club_id        = $club_id;
        $club_player->player_id      = $player->id;
        $club_player->requested_date = gmdate('Y-m-d');
        $club_player->requested_user = $registered_by_userId;
        $registration                = new Club_Player( $club_player );
        $this->club_player_repository->save( $registration );
        return $registration;
    }

    public function approve_registration($registrationId, $approvingUserId) {
        $registration = $this->club_player_repository->find($registrationId);
        if (!$registration) {
            throw new Registration_Not_Found_Exception( "Registration not found." );
        }

        $registration->approve($approvingUserId);
        $this->club_player_repository->save($registration);
        return $registration;
    }

    public function get_clubs_for_player( $player_id ): array {
        $player = $this->player_repository->find( $player_id );
        if (!$player) {
            throw new Player_Not_Found_Exception( sprintf( __( 'Player with ID %s not found or is inactive.', 'racketmanager' ), $player_id ) );
        }

        $registrations = $this->club_player_repository->find_by_player( $player_id );
        $clubs = [];
        foreach ($registrations as $registration) {
            $club = $this->club_repository->find( $registration->getClubId() );
            if ($club) {
                $clubs[] = $club;
            }
        }
        return $clubs;
    }

}
