<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

use InvalidArgumentException;
use stdClass;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Time_Parser;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\Fixture_Service;

final readonly class League_Match_Handler {
    public function __construct(
        private Fixture_Repository_Interface $fixture_repository,
        private Fixture_Maintenance_Service $fixture_maintenance_service,
        private Fixture_Result_Manager $fixture_result_manager,
        private Fixture_Service $fixture_service
    ) {
    }

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

    public function handle_manage_matches( Manage_League_Matches_Command $command, object $league ): Action_Result_DTO {
        $mode = isset( $command->mode ) ? $command->mode : '';
        if ( 'add' === $mode ) {
            return $this->add_matches( $command, $league );
        }

        return $this->edit_matches( $command, $league );
    }

    public function handle_update_results( Update_League_Results_Command $command, object $league ): Action_Result_DTO {
        $custom      = isset( $command->custom ) && is_array( $command->custom ) ? $command->custom : array();
        $matches     = isset( $command->matches ) && is_array( $command->matches ) ? $command->matches : array();
        $home_points = isset( $command->home_points ) && is_array( $command->home_points ) ? $command->home_points : array();
        $away_points = isset( $command->away_points ) && is_array( $command->away_points ) ? $command->away_points : array();
        $season      = isset( $command->season ) ? $command->season : '';

        if ( ! empty( $matches ) && ! empty( $home_points ) && ! empty( $away_points ) && ! empty( $season ) ) {
            if ( method_exists( $league, 'set_finals' ) ) {
                $league->set_finals( false );
            } elseif ( isset( $league->finals ) ) {
                $league->finals = false;
            }
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

    private function add_matches( Manage_League_Matches_Command $command, object $league ): Action_Result_DTO {
        $matches = isset( $command->match ) && is_array( $command->match ) ? $command->match : array();
        if ( empty( $matches ) ) {
            return new Action_Result_DTO( tab_override: 'matches' );
        }

        $season = isset( $command->season ) ? $command->season : null;
        $final  = isset( $command->final ) ? $command->final : null;

        if ( $final ) {
            $final_exists = \Racketmanager\get_league_query_service()->get_matches( $league,
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
                                message: sprintf( __( 'Invalid date format provided for match row %d: "%s"', 'racketmanager' ), $i + 1, $date_time ),
                                message_type: Admin_Message_Type::ERROR,
                                tab_override: 'matches',
                            );
                        }

                        $match              = new stdClass();
                        $match->date        = $date_time;
                        $match->match_day   = '';
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
                        $new_matches[] = $match;
                    }
                } else {
                    --$num_matches;
                }
            }
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO(
                message: sprintf( __( 'Match row %d: %s', 'racketmanager' ), $i + 1, $e->getMessage() ),
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

    private function edit_matches( Manage_League_Matches_Command $command, object $league ): Action_Result_DTO {
        $matches = isset( $command->match ) && is_array( $command->match ) ? $command->match : array();
        if ( empty( $matches ) ) {
            return new Action_Result_DTO( tab_override: 'matches' );
        }

        $num_matches = count( $matches );
        $updates = array();
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
                        message: sprintf( __( 'Invalid date format provided for match row %d: "%s"', 'racketmanager' ), $i + 1, $date ),
                        message_type: Admin_Message_Type::ERROR,
                        tab_override: 'matches',
                    );
                }

                $updates[] = array( $match, $date, $i );
            }
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO(
                message: sprintf( __( 'Match row %d: %s', 'racketmanager' ), $i + 1, $e->getMessage() ),
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
            $match->league_id = isset( $league->id ) ? intval( $league->id ) : $match->league_id;
            $match->match_day = null;
            if ( isset( $command->match_day ) ) {
                if ( is_array( $command->match_day ) ) {
                    $match->match_day = isset( $command->match_day[ $i ] ) ? intval( $command->match_day[ $i ] ) : null;
                } elseif ( ! empty( $command->match_day ) ) {
                    $match->match_day = intval( $command->match_day );
                }
            }

            $match->host        = isset( $command->host[ $i ] ) ? $command->host[ $i ] : null;
            $match->home_team   = isset( $command->home_team[ $i ] ) ? $command->home_team[ $i ] : '';
            $match->away_team   = isset( $command->away_team[ $i ] ) ? $command->away_team[ $i ] : '';
            $match->location    = isset( $command->location[ $i ] ) ? $command->location[ $i ] : null;
            $match->final_round = isset( $command->final ) ? $command->final : null;

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
