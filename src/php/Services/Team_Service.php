<?php
/**
 * Team_Service class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\DTO\Team\Team_Fixture_Settings_DTO;
use Racketmanager\Domain\Team;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use Racketmanager\Util\Util_Messages;
use WP_Error;

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
    public function __construct( Team_Repository $team_repository, Club_Repository $club_repository, Event_Repository $event_repository, Player_Service $player_service ) {
        $this->team_repository  = $team_repository;
        $this->club_repository  = $club_repository;
        $this->event_repository = $event_repository;
        $this->player_service   = $player_service;
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
     * @param string|null $type
     *
     * @return array
     */
    public function get_player_teams( ?string $type ): array {
        $team_types = Util_Lookup::get_event_types();
        if ( empty( $team_types[ $type ] ) ) {
            throw new Invalid_Argument_Exception( __( 'Team type not found', 'racketmanager' ) );
        }
        return $this->team_repository->find_for_players( $type );
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
            throw new Team_Not_Found_Exception( Util_Messages::team_not_found( (string) $team_id ) );
        }

        if ( - 1 === (int) $team->get_id() ) {
            return new Team_Details_DTO( $team, null, null );
        }

        $club = $this->club_repository->find( $team->get_club_id() );
        if ( ! $club ) {
            throw new Club_Not_Found_Exception( Util_Messages::club_not_found( $team->get_club_id() ) );
        }
        $match_secretary = $this->player_service->get_match_secretary_details( $club->get_id() );

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

    public function amend_team_for_club( ?string $team_id, ?int $club_id, ?string $type ): bool|WP_Error {
        try {
            $club = $this->club_repository->find( $club_id );
            $team = $this->get_team_by_id( $team_id );
        } catch ( Club_Not_Found_Exception $e ) {
            throw new Club_Not_Found_Exception( $e->getMessage() );
        } catch ( Team_Not_Found_Exception $e ) {
            throw new Team_Not_Found_Exception( $e->getMessage() );
        }
        $type_name = match ( substr( $type, 0, 1 ) ) {
            'B' => __( 'Boys', 'racketmanager' ),
            'G' => __( 'Girls', 'racketmanager' ),
            'W' => __( 'Ladies', 'racketmanager' ),
            'M' => __( 'Mens', 'racketmanager' ),
            'X' => __( 'Mixed', 'racketmanager' ),
            default => null,
        };
        if ( empty( $type_name ) ) {
            throw new Invalid_Argument_Exception( Util_Messages::invalid_team_type() );
        }
        $name_parts = explode( ' ', $team->get_name() );
        if ( $type === $team->get_type() ) {
            $sequence_number = end( $name_parts );
        } else {
            $sequence_number = $this->team_repository->find_next_sequence_number( $club->get_shortcode(), $type_name );
        }
        $name = $club->get_shortcode() . ' ' . $type_name . ' ' . $sequence_number;
        $team->set_name( $name );
        $team->set_type( $type );
        $team->set_club_id( $club_id );
        $team->set_stadium( $club->get_shortcode() );

        return $this->team_repository->save( $team );
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
     * Get club teams for a given league type.
     *
     * @param string $league_type
     * @return array<int,object>
     */
    public function get_club_teams( string $league_type ): array {
        $teams = array();

        $clubs = $this->club_repository->find_all(
            array(
                'type' => 'affiliated',
            )
        );

        if ( is_array( $clubs ) ) {
            foreach ( $clubs as $club_id ) {
                $club_obj = $this->club_repository->find( $club_id );
                if ( ! $club_obj ) {
                    continue;
                }

                $club_teams = $this->team_repository->find_by_club( $club_obj->get_id(), $league_type );
                if ( $club_teams ) {
                    foreach ( $club_teams as $team ) {
                        $teams[] = $team;
                    }
                }
            }
        }

        return $teams;
    }

    public function derive_team_details( string $team_ref ): null|Team_Details_DTO {
        $team  = explode( '_', $team_ref );
        if ( empty( $team ) ) {
            return null;
        }
        if ( count( $team ) === 2 ) {
            $team_name = sprintf( __( 'Team rank %s', 'racketmanager' ), $team[0] );
        } else {
            $final = $team[1] ?? null;
            if ( empty( $final ) ) {
                // Handle case like 1_semi-final_1 where 1 means Winner, 2 means Loser
                // Or cases where it's not a round key but just something else
                return null;
            }

            $type = match ( $team[0] ) {
                '1' => __( 'Winner', 'racketmanager' ),
                '2' => __( 'Loser', 'racketmanager' ),
                default => null
            };
            $round_name = Util::get_final_name( $final );
            $match_num  = $team[2] ?? '';

            /* translators: %1$s: type (Winner/Loser), %2$s: round name, %3$s: match number */
            $team_name = sprintf( __( '%1$s %2$s %3$s', 'racketmanager' ), $type, $round_name, $match_num );
        }
        $new_team = new Team();
        $new_team->set_name( $team_name );
        return new Team_Details_DTO( $new_team, null, null );

    }

}
