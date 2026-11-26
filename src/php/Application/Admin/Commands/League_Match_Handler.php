<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

use InvalidArgumentException;
use Racketmanager\Domain\Competition\League;
use stdClass;
use Racketmanager\Application\Fixture\Ports\Fixture_Maintenance_Service_Interface;
use Racketmanager\Application\Fixture\Ports\Fixture_Result_Manager_Interface;
use Racketmanager\Application\Fixture\Ports\Fixture_Service_Interface;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Time_Parser;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Application\League\Ports\League_Query_Service_Interface;

/**
 * Coordinates league match administration actions, including deletion, creation or editing,
 * and result updates through fixture and league services.
 */
final readonly class League_Match_Handler {
    /**
     * Initialises the service with fixture, maintenance, result, fixture service, and league query dependencies.
     *
     * @param Fixture_Repository_Interface          $fixture_repository Fixture repository dependency.
     * @param Fixture_Maintenance_Service_Interface $fixture_maintenance_service Fixture maintenance service dependency.
     * @param Fixture_Result_Manager_Interface      $fixture_result_manager Fixture result manager dependency.
     * @param Fixture_Service_Interface             $fixture_service Fixture service dependency.
     * @param League_Query_Service_Interface        $league_query_service League query service dependency.
     *
     * @return void
     */
    public function __construct(
        private Fixture_Repository_Interface $fixture_repository,
        private Fixture_Maintenance_Service_Interface $fixture_maintenance_service,
        private Fixture_Result_Manager_Interface $fixture_result_manager,
        private Fixture_Service_Interface $fixture_service,
        private League_Query_Service_Interface $league_query_service
    ) {
    }

    /**
     * Handles deletion of selected league matches and returns the resulting action status.
     *
     * @param Delete_League_Matches_Command $command Command containing the match identifiers to delete.
     *
     * @return Action_Result_DTO Result containing status messaging and the matches tab override.
     */
    public function handle_delete_matches( Delete_League_Matches_Command $command ): Action_Result_DTO {
        $matches = isset( $command->match ) && is_array( $command->match ) ? $command->match : array();
        if ( empty( $matches ) ) {
            return new Action_Result_DTO( tab_override: 'matches' );
        }

        $messages = array();
        foreach ( $matches as $match_id ) {
            $this->fixture_maintenance_service->delete_fixture( (int) $match_id );
            /* translators: %d: Match id */
            $messages[] = sprintf( __( 'Match id %d deleted', 'racketmanager' ), (int) $match_id );
        }

        return new Action_Result_DTO(
            message: implode( '<br>', $messages ),
            message_type: Admin_Message_Type::SUCCESS,
            tab_override: 'matches',
        );
    }

    /**
     * Handles league match management by adding or editing matches based on the command mode.
     *
     * @param Manage_League_Matches_Command $command Command containing match management data and mode.
     * @param League                        $league League object whose matches are being managed.
     *
     * @return Action_Result_DTO Result of the add or edit match operation.
     */
    public function handle_manage_matches( Manage_League_Matches_Command $command, League $league ): Action_Result_DTO {
        $mode = $command->mode ?? '';
        if ( 'add' === $mode ) {
            return $this->add_matches( $command, $league );
        }

        return $this->edit_matches( $command, $league );
    }

    /**
     * Handles submitted league match result updates and returns the admin action response.
     *
     * @param Update_League_Results_Command $command Command containing match IDs, point values, custom data, and season context.
     * @param League                        $league League instance whose match results should be updated.
     *
     * @return Action_Result_DTO Result DTO containing the response message and target admin tab.
     */
    public function handle_update_results( Update_League_Results_Command $command, League $league ): Action_Result_DTO {
        $custom      = isset( $command->custom ) && is_array( $command->custom ) ? $command->custom : array();
        $matches     = isset( $command->matches ) && is_array( $command->matches ) ? $command->matches : array();
        $home_points = isset( $command->home_points ) && is_array( $command->home_points ) ? $command->home_points : array();
        $away_points = isset( $command->away_points ) && is_array( $command->away_points ) ? $command->away_points : array();
        $season      = $command->season ?? '';

        if ( ! empty( $matches ) && ! empty( $home_points ) && ! empty( $away_points ) && ! empty( $season ) ) {
            $league->set_finals( false );
            $num_matches = $this->fixture_result_manager->update_match_results( $league, $matches, $home_points, $away_points, $custom, $season, false );

            /* translators: %d: number of matches updated */
            return new Action_Result_DTO(
                message: sprintf( __( 'Updated Results of %d matches', 'racketmanager' ), intval( $num_matches ) ),
                message_type: Admin_Message_Type::SUCCESS,
                tab_override: 'matches',
            );
        }

        return new Action_Result_DTO( tab_override: 'matches' );
    }

    /**
     * Adds valid matches from the command to the specified league.
     *
     * @param Manage_League_Matches_Command $command Command containing match data and related options.
     * @param League                        $league League to which the matches are added.
     *
     * @return Action_Result_DTO Result describing the outcome of the add operation.
     */
    private function add_matches( Manage_League_Matches_Command $command, League $league ): Action_Result_DTO {
        $matches = isset( $command->match ) && is_array( $command->match ) ? $command->match : array();
        if ( empty( $matches ) ) {
            return new Action_Result_DTO( tab_override: 'matches' );
        }

        $season = $command->season ?? null;
        $final  = $command->final ?? null;

        if ( $final ) {
            $final_exists = $this->league_query_service->get_matches(
                $league,
                array(
                    'final'  => $final,
                    'season' => $season,
                )
            );
            if ( $final_exists ) {
                /* translators: %s: final round name */
                return new Action_Result_DTO(
                    message: sprintf( __( 'Matches already exist for %s', 'racketmanager' ), $final ),
                    message_type: Admin_Message_Type::ERROR,
                    tab_override: 'matches',
                );
            }
        }

        $num_matches = count( $matches );
        $new_matches = array();
        try {
            foreach ( $matches as $i => $match_id ) {
                if ( isset( $command->away_team[ $i ] ) && isset( $command->home_team[ $i ] ) && $command->away_team[ $i ] !== $command->home_team[ $i ] ) {
                    $index         = isset( $command->myDatePicker[ $i ] ) ? $i : 0;
                    $begin_hour    = isset( $command->begin_hour[ $i ] ) ? intval( $command->begin_hour[ $i ] ) : 0;
                    $begin_minutes = isset( $command->begin_minutes[ $i ] ) ? intval( $command->begin_minutes[ $i ] ) : 0;
                    $time          = sprintf( '%02d:%02d:00', $begin_hour, $begin_minutes );

                    if ( isset( $command->myDatePicker[ $index ] ) ) {
                        $date_raw     = $command->myDatePicker[ $index ];
                        $trimmed_date = trim( $date_raw );
                        if ( '' === $trimmed_date ) {
                            --$num_matches;
                            continue;
                        }
                        $date_time = $trimmed_date . ' ' . $time;
                        if ( ! Fixture_Time_Parser::is_valid_datetime( $date_time ) ) {
                            return new Action_Result_DTO(
                                message: sprintf( __( 'Invalid date format provided for match row %1$d: "%2$s"', 'racketmanager' ), $i + 1, $date_time ),
                                message_type: Admin_Message_Type::ERROR,
                                tab_override: 'matches',
                            );
                        }

                        $match            = new stdClass();
                        $match->date      = $date_time;
                        $match->match_day = '';
                        if ( isset( $command->match_day[ $i ] ) ) {
                            $match->match_day = $command->match_day[ $i ];
                        } elseif ( ! empty( $command->match_day ) ) {
                            $match->match_day = intval( $command->match_day );
                        }
                        $match->host        = isset( $command->host[ $i ] ) ? $command->host[ $i ] : null;
                        $match->home_team   = $command->home_team[ $i ];
                        $match->away_team   = $command->away_team[ $i ];
                        $match->location    = isset( $command->location[ $i ] ) ? $command->location[ $i ] : null;
                        $match->league_id   = isset( $command->league_id ) ? $command->league_id : null;
                        $match->season      = $season;
                        $match->group       = null;
                        $match->final_round = $final;
                        $match->num_rubbers = isset( $command->num_rubbers ) ? intval( $command->num_rubbers ) : null;
                        $new_matches[]      = $match;
                    }
                } else {
                    --$num_matches;
                }
            }
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO(
                message: sprintf( __( 'Match row %1$d: %2$s', 'racketmanager' ), $i + 1, $e->getMessage() ),
                message_type: Admin_Message_Type::ERROR,
                tab_override: 'matches',
            );
        }

        foreach ( $new_matches as $match ) {
            $this->fixture_service->add_match( $match, $league );
        }

        /* translators: %d: number of matches added */
        return new Action_Result_DTO(
            message: sprintf( _n( '%d Match added', '%d Matches added', $num_matches, 'racketmanager' ), $num_matches ),
            message_type: Admin_Message_Type::SUCCESS,
            tab_override: 'matches',
        );
    }

    /**
     * Updates existing league matches using values from the command.
     *
     * @param Manage_League_Matches_Command $command Command containing match identifiers, dates, teams, and related match data.
     * @param League                        $league League associated with the matches being edited.
     *
     * @return Action_Result_DTO Result describing the outcome of the edit operation.
     */
    private function edit_matches( Manage_League_Matches_Command $command, League $league ): Action_Result_DTO {
        $matches = isset( $command->match ) && is_array( $command->match ) ? $command->match : array();
        if ( empty( $matches ) ) {
            return new Action_Result_DTO( tab_override: 'matches' );
        }

        $num_matches = count( $matches );
        $updates     = array();
        try {
            foreach ( $matches as $i => $match_id ) {
                $match = $this->fixture_repository->find_by_id( (int) $match_id );
                if ( null === $match ) {
                    continue;
                }

                $begin_hour    = isset( $command->begin_hour[ $i ] ) ? intval( $command->begin_hour[ $i ] ) : 0;
                $begin_minutes = isset( $command->begin_minutes[ $i ] ) ? intval( $command->begin_minutes[ $i ] ) : 0;
                $time          = sprintf( '%02d:%02d:00', $begin_hour, $begin_minutes );

                if ( isset( $command->myDatePicker[ $i ] ) ) {
                    $date_raw     = $command->myDatePicker[ $i ];
                    $trimmed_date = trim( $date_raw );
                    $date         = '' !== $trimmed_date ? $trimmed_date . ' ' . $time : '';
                } else {
                    $index = ( isset( $command->year[ $i ] ) && isset( $command->month[ $i ] ) && isset( $command->day[ $i ] ) ) ? $i : 0;
                    $year  = isset( $command->year[ $index ] ) ? intval( $command->year[ $index ] ) : 0;
                    $month = isset( $command->month[ $index ] ) ? intval( $command->month[ $index ] ) : 0;
                    $day   = isset( $command->day[ $index ] ) ? intval( $command->day[ $index ] ) : 0;
                    $date  = ( $year && $month && $day ) ? sprintf( '%04d-%02d-%02d %s', $year, $month, $day, $time ) : '';
                }

                if ( '' !== $date && ! Fixture_Time_Parser::is_valid_datetime( $date ) ) {
                    return new Action_Result_DTO(
                        message: sprintf( __( 'Invalid date format provided for match row %1$d: "%2$s"', 'racketmanager' ), $i + 1, $date ),
                        message_type: Admin_Message_Type::ERROR,
                        tab_override: 'matches',
                    );
                }

                $updates[] = array( $match, $date, $i );
            }
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO(
                message: sprintf( __( 'Match row %1$d: %2$s', 'racketmanager' ), $i + 1, $e->getMessage() ),
                message_type: Admin_Message_Type::ERROR,
                tab_override: 'matches',
            );
        }

        foreach ( $updates as [ $match, $date, $i ] ) {
            if ( '' !== $date ) {
                if ( $match instanceof Fixture || method_exists( $match, 'set_date' ) ) {
                    $match->set_date( $date );
                } else {
                    $match->date = $date;
                }
            }
            $league_id = isset( $league->id ) ? intval( $league->id ) : $match->league_id;
            $match_day = null;
            if ( isset( $command->match_day ) ) {
                if ( is_array( $command->match_day ) ) {
                    $match_day = isset( $command->match_day[ $i ] ) ? intval( $command->match_day[ $i ] ) : null;
                } elseif ( ! empty( $command->match_day ) ) {
                    $match_day = intval( $command->match_day );
                }
            }

            $host        = isset( $command->host[ $i ] ) ? $command->host[ $i ] : null;
            $home_team   = isset( $command->home_team[ $i ] ) ? $command->home_team[ $i ] : '';
            $away_team   = isset( $command->away_team[ $i ] ) ? $command->away_team[ $i ] : '';
            $location    = isset( $command->location[ $i ] ) ? $command->location[ $i ] : null;
            $final_round = isset( $command->final ) ? $command->final : null;

            if ( $match instanceof Fixture ) {
                $match->set_league_id( $league_id );
                $match->set_match_day( $match_day );
                $match->set_host( $host );
                $match->assign_teams( $home_team, $away_team );
                $match->set_location( $location );
                $match->set_final_round( $final_round );
            } else {
                $match->league_id   = $league_id;
                $match->match_day   = $match_day;
                $match->host        = $host;
                $match->home_team   = $home_team;
                $match->away_team   = $away_team;
                $match->location    = $location;
                $match->final_round = $final_round;
            }

            $this->fixture_service->update_match( $match );
        }

        /* translators: %d: number of matches updated */
        return new Action_Result_DTO(
            message: sprintf( _n( '%d Match updated', '%d Matches updated', $num_matches, 'racketmanager' ), $num_matches ),
            message_type: Admin_Message_Type::SUCCESS,
            tab_override: 'matches',
        );
    }
}
