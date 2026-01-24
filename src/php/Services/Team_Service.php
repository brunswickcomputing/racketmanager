<?php
/**
 * Team_Service class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use Racketmanager\Domain\DTO\Team_Details_DTO;
use Racketmanager\Domain\DTO\Team_Fixture_Settings_DTO;
use Racketmanager\Domain\Team;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Util\Util_Messages;

/**
 * Class to implement the Team Management Service
 */
class Team_Service {
    private Team_Repository $team_repository;
    private Club_Repository $club_repository;
    private Event_Repository $event_repository;
    private Player_Service $player_service;

    /**
     * Constructor
     *
     */
    public function __construct( Team_Repository $team_repository, Club_Repository $club_repository, Event_Repository $event_repository ,Player_Service $player_service ) {
        $this->team_repository  = $team_repository;
        $this->club_repository  = $club_repository;
        $this->event_repository = $event_repository;
        $this->player_service   = $player_service;
    }

    /**
     * Get a team by id
     *
     * @param string|int|null $team_id
     *
     * @return Team
     */
    public function get_team_by_id( null|string|int $team_id ): Team {
        $team = $this->team_repository->find_by_id( $team_id );
        if ( ! $team ) {
            throw new Team_Not_Found_Exception( Util_Messages::team_not_found( $team->$team_id ) );
        }
        return $team;
    }

    /**
     * Get teams for a club
     *
     * @param int|null $club_id
     * @param $type
     *
     * @return array
     */
    public function get_teams_for_club( ?int $club_id, $type = null ): array {
        if ( ! $this->club_repository->find( $club_id ) ) {
            throw new Club_Not_Found_Exception( Util_Messages::club_not_found( $club_id ) );
        }
        return $this->team_repository->find_by_club( $club_id, $type );
    }

    /**
     * Get player teams
     *
     * @return array
     */
    public function get_player_teams(): array {
        return $this->team_repository->find_for_players();
    }

    /**
     * Get team details
     *
     * @param int|string|null $team_id
     *
     * @return Team_Details_DTO
     */
    public function get_team_details( int|string|null $team_id ): Team_Details_DTO {
        if ( ! $team_id ) {
            throw new Invalid_Argument_Exception( Util_Messages::invalid_team_id() );
        }
        $team = $this->team_repository->find_by_id( $team_id );
        if ( ! $team ) {
            throw new Team_Not_Found_Exception( Util_Messages::team_not_found( $team->$team_id ) );
        }
        $club = $this->club_repository->find( $team->club_id );
        if ( ! $club ) {
            throw new Club_Not_Found_Exception( Util_Messages::club_not_found( $team->club_id ) );
        }
        $match_secretary = $this->player_service->get_match_secretary_details( $club->id );
        return new Team_Details_DTO( $team, $club, $match_secretary );
    }

    /**
     * Get the latest team fixture settings for an event
     *
     * @param int|null $team_id
     * @param $event_id
     *
     * @return Team_Fixture_Settings_DTO
     */
    public function get_latest_team_details_for_event( ?int $team_id, $event_id = null ): Team_Fixture_Settings_DTO {
        $team = $this->team_repository->find_by_id( $team_id );
        if ( ! $team ) {
            throw new Team_Not_Found_Exception( Util_Messages::team_not_found( $team->$team_id ) );
        }
        $event = $this->event_repository->find_by_id( $event_id );
        if ( ! $event ) {
            throw new Event_Not_Found_Exception( Util_Messages::event_not_found( $team->$event_id ) );
        }
        $team_info = $this->team_repository->find_team_settings_for_event( $team_id, $event_id );
        if ( ! $team_info ) {
            throw new Team_Not_Found_Exception( Util_Messages::team_not_found( $team->$team_id ) );
        }
        return $team_info;
    }
}
