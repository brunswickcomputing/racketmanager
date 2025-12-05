<?php

namespace Racketmanager\Services;

use Racketmanager\Domain\Team;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Util\Util;
use stdClass;

class Team_Service {
    private Team_Repository $team_repository;
    private Club_Repository $club_repository;

    public function __construct( Team_Repository $team_repository, Club_Repository $club_repository ) {
        $this->team_repository = $team_repository;
        $this->club_repository = $club_repository;
    }

    /**
     * Creates a new team with an automatically generated name based on club shortcode and sequence number.
     *
     * @param int|null $club_id The ID of the parent club.
     * @param string $type The type of team (e.g. 'Boys', 'Girls', 'Ladies', 'Mens', 'Mixed').
     *
     * @return Team The newly created Team object.
     */
    public function create_team( ?int $club_id, string $type ): Team {
        $club = $this->club_repository->find( $club_id );
        if ( ! $club ) {
            throw new Club_Not_Found_Exception( Util::club_not_found( $club_id ) );
        }
        $type_name = match( substr( $type, 0, 1 ) ) {
            'B' => __( 'Boys', 'racketmanager' ),
            'G' => __( 'Girls', 'racketmanager' ),
            'W' => __( 'Ladies', 'racketmanager' ),
            'M' => __( 'Mens', 'racketmanager' ),
            'X' => __( 'Mixed', 'racketmanager' ),
            default => null,
        };

        if ( empty( $type_name ) ) {
            throw new Invalid_Argument_Exception( __( 'Invalid team type', 'racketmanager' ) );
        }

        // 1. Get the next available sequence number
        $next_sequence_number = $this->team_repository->find_next_sequence_number( $club->get_shortcode(), $type_name );

        // 2. Generate the team name
        $team_name = $club->get_shortcode() . ' ' . $type_name . ' ' . $next_sequence_number;

        // 3. Create the Domain Entity and Save it
        $team          = new stdClass();
        $team->title   = $team_name;
        $team->stadium = $club->get_shortcode();
        $team->club_id = $club->get_id();
        $team->type    = $type;
        $team          = new Team( $team );
        // We assume a save method exists in TeamRepository from a previous step
        $this->team_repository->save( $team );

        return $team;
    }

    public function get_teams_for_club( ?int $club_id, $type = null ): array {
        if ( ! $this->club_repository->find( $club_id ) ) {
            throw new Club_Not_Found_Exception( Util::club_not_found( $club_id ) );
        }
        return $this->team_repository->find_by_club( $club_id, $type );
    }

    public function get_player_teams(): array {
        return $this->team_repository->find_for_players();
    }

}
