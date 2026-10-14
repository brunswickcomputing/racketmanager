<?php
/**
 * League match action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/League
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\League;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use stdClass;
use function Racketmanager\get_league;

/**
 * Handles match/fixture mutations for the league admin screen (delete matches, manage matches, update results).
 */
readonly final class League_Match_Action_Dispatcher {

    public function __construct(
        private Fixture_Repository_Interface $fixture_repository,
        private Fixture_Maintenance_Service $fixture_maintenance_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     * @param object|null          $league League domain entity/model
     */
    public function handle( array $query, array $post, ?object $league = null ): Action_Result_DTO {
        $league = $this->resolve_league( $query, $post, $league );
        if ( null === $league ) {
            return new Action_Result_DTO();
        }

        if ( isset( $post['delMatches'] ) ) {
            return $this->handle_delete_matches( $post );
        }

        if ( isset( $post['updateLeague'] ) && 'match' === $post['updateLeague'] ) {
            return $this->handle_manage_matches( $query, $post, $league );
        }

        if ( isset( $post['updateLeague'] ) && 'results' === $post['updateLeague'] ) {
            return $this->handle_update_results( $post, $league );
        }

        return new Action_Result_DTO();
    }

    private function resolve_league( array $query, array $post, ?object $league ): ?object {
        if ( null !== $league ) {
            return $league;
        }

        $league_id = isset( $query['league_id'] ) ? intval( $query['league_id'] ) : ( isset( $post['league_id'] ) ? intval( $post['league_id'] ) : null );
        if ( ! $league_id ) {
            return null;
        }

        return get_league( $league_id );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function handle_delete_matches( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_matches-bulk', 'del_matches' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                tab_override: 'matches',
            );
        }

        $matches = isset( $post['match'] ) && is_array( $post['match'] ) ? $post['match'] : array();
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
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_manage_matches( array $query, array $post, object $league ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_manage-matches', 'edit_matches' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                tab_override: 'matches',
            );
        }

        $mode = isset( $post['mode'] ) ? sanitize_text_field( wp_unslash( strval( $post['mode'] ) ) ) : '';
        if ( 'add' === $mode ) {
            return $this->add_matches( $query, $post, $league );
        }

        return $this->edit_matches( $post, $league );
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function add_matches( array $query, array $post, object $league ): Action_Result_DTO {
        $matches = isset( $post['match'] ) && is_array( $post['match'] ) ? $post['match'] : array();
        if ( empty( $matches ) ) {
            return new Action_Result_DTO( tab_override: 'matches' );
        }

        $season = isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : null;
        $final  = isset( $post['final'] ) ? sanitize_text_field( wp_unslash( strval( $post['final'] ) ) ) : null;

        if ( $final ) {
            $final_exists = $league->get_matches(
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
        foreach ( $matches as $i => $match_id ) {
            if ( isset( $post['away_team'][ $i ] ) && isset( $post['home_team'][ $i ] ) && $post['away_team'][ $i ] !== $post['home_team'][ $i ] ) {
                $index         = isset( $post['myDatePicker'][ $i ] ) ? $i : 0;
                $begin_hour    = isset( $post['begin_hour'][ $i ] ) ? intval( $post['begin_hour'][ $i ] ) : 0;
                $begin_minutes = isset( $post['begin_minutes'][ $i ] ) ? intval( $post['begin_minutes'][ $i ] ) : 0;
                $time          = sprintf( '%02d:%02d:00', $begin_hour, $begin_minutes );

                if ( isset( $post['myDatePicker'][ $index ] ) ) {
                    $match              = new stdClass();
                    $match->date        = sanitize_text_field( wp_unslash( strval( $post['myDatePicker'][ $index ] ) ) ) . ' ' . $time;
                    $match->match_day   = '';
                    if ( isset( $post['match_day'][ $i ] ) ) {
                        $match->match_day = sanitize_text_field( wp_unslash( strval( $post['match_day'][ $i ] ) ) );
                    } elseif ( ! empty( $post['match_day'] ) ) {
                        $match->match_day = intval( $post['match_day'] );
                    }
                    $match->host        = isset( $post['host'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['host'][ $i ] ) ) ) : null;
                    $match->home_team   = sanitize_text_field( wp_unslash( strval( $post['home_team'][ $i ] ) ) );
                    $match->away_team   = sanitize_text_field( wp_unslash( strval( $post['away_team'][ $i ] ) ) );
                    $match->location    = isset( $post['location'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['location'][ $i ] ) ) ) : null;
                    $match->league_id   = isset( $post['league_id'] ) ? sanitize_text_field( wp_unslash( strval( $post['league_id'] ) ) ) : null;
                    $match->season      = $season;
                    $match->group       = null;
                    $match->final_round = $final;
                    $match->num_rubbers = isset( $post['num_rubbers'] ) ? intval( $post['num_rubbers'] ) : null;
                    $league->add_match( $match );
                }
            } else {
                --$num_matches;
            }
        }

        /* translators: %d: number of matches added */
        return new Action_Result_DTO(
            message: sprintf( _n( '%d Match added', '%d Matches added', $num_matches, 'racketmanager' ), $num_matches ),
            message_type: Admin_Message_Type::SUCCESS,
            tab_override: 'matches',
        );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function edit_matches( array $post, object $league ): Action_Result_DTO {
        $matches = isset( $post['match'] ) && is_array( $post['match'] ) ? $post['match'] : array();
        if ( empty( $matches ) ) {
            return new Action_Result_DTO( tab_override: 'matches' );
        }

        $num_matches = count( $matches );
        foreach ( $matches as $i => $match_id ) {
            $match = $this->fixture_repository->find_by_id( (int) $match_id );
            if ( null === $match ) {
                continue;
            }

            $begin_hour    = isset( $post['begin_hour'][ $i ] ) ? intval( $post['begin_hour'][ $i ] ) : 0;
            $begin_minutes = isset( $post['begin_minutes'][ $i ] ) ? intval( $post['begin_minutes'][ $i ] ) : 0;
            $time          = sprintf( '%02d:%02d:00', $begin_hour, $begin_minutes );

            if ( isset( $post['myDatePicker'][ $i ] ) ) {
                $date = sanitize_text_field( wp_unslash( strval( $post['myDatePicker'][ $i ] ) ) );
                $date = $date . ' ' . $time;
            } else {
                $index = ( isset( $post['year'][ $i ] ) && isset( $post['month'][ $i ] ) && isset( $post['day'][ $i ] ) ) ? $i : 0;
                $year  = isset( $post['year'][ $index ] ) ? intval( $post['year'][ $index ] ) : 0;
                $month = isset( $post['month'][ $index ] ) ? intval( $post['month'][ $index ] ) : 0;
                $day   = isset( $post['day'][ $index ] ) ? intval( $post['day'][ $index ] ) : 0;
                $date  = sprintf( '%04d-%02d-%02d %s', $year, $month, $day, $time );
            }

            $match->date      = $date;
            $match->league_id = isset( $league->id ) ? intval( $league->id ) : $match->league_id;
            $match->match_day = null;
            if ( isset( $post['match_day'] ) ) {
                if ( is_array( $post['match_day'] ) ) {
                    $match->match_day = isset( $post['match_day'][ $i ] ) ? intval( $post['match_day'][ $i ] ) : null;
                } elseif ( ! empty( $post['match_day'] ) ) {
                    $match->match_day = intval( $post['match_day'] );
                }
            }

            $match->host        = isset( $post['host'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['host'][ $i ] ) ) ) : null;
            $match->home_team   = isset( $post['home_team'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['home_team'][ $i ] ) ) ) : '';
            $match->away_team   = isset( $post['away_team'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['away_team'][ $i ] ) ) ) : '';
            $match->location    = isset( $post['location'][ $i ] ) ? sanitize_text_field( wp_unslash( strval( $post['location'][ $i ] ) ) ) : null;
            $match->final_round = isset( $post['final'] ) ? sanitize_text_field( wp_unslash( strval( $post['final'] ) ) ) : null;

            $league->update_match( $match );
        }

        /* translators: %d: number of matches updated */
        return new Action_Result_DTO(
            message: sprintf( _n( '%d Match updated', '%d Matches updated', $num_matches, 'racketmanager' ), $num_matches ),
            message_type: Admin_Message_Type::SUCCESS,
            tab_override: 'matches',
        );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function handle_update_results( array $post, object $league ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_matches-bulk', 'update_results' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                tab_override: 'matches',
            );
        }

        $custom      = isset( $post['custom'] ) && is_array( $post['custom'] ) ? $post['custom'] : array();
        $matches     = isset( $post['matches'] ) && is_array( $post['matches'] ) ? $post['matches'] : array();
        $home_points = isset( $post['home_points'] ) && is_array( $post['home_points'] ) ? $post['home_points'] : array();
        $away_points = isset( $post['away_points'] ) && is_array( $post['away_points'] ) ? $post['away_points'] : array();
        $season      = isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : '';

        if ( ! empty( $matches ) && ! empty( $home_points ) && ! empty( $away_points ) && ! empty( $season ) ) {
            $league->set_finals( false );
            $num_matches = $league->update_match_results( $matches, $home_points, $away_points, $custom, $season, false );

            /* translators: %d: number of matches updated */
            return new Action_Result_DTO(
                message: sprintf( __( 'Updated Results of %d matches', 'racketmanager' ), intval( $num_matches ) ),
                message_type: Admin_Message_Type::SUCCESS,
                tab_override: 'matches',
            );
        }

        return new Action_Result_DTO( tab_override: 'matches' );
    }
}
