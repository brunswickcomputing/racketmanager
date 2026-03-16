<?php
/**
 * Championship Factory API: Championship_Factory class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use Racketmanager\Domain\Championship;
use Racketmanager\Domain\Championship_Settings;
use Racketmanager\Util\Util;
use function Racketmanager\get_league;

final class Championship_Factory {
    /**
     * Create a championship domain object from league and settings.
     *
     * @param object $league league object.
     * @param Championship_Settings $settings settings object.
     *
     * @return Championship
     */
    public function create( object $league, Championship_Settings $settings ): Championship {
        $league_id       = (int) $league->id;
        $is_consolation  = $this->is_consolation( $league );
        $num_groups      = $settings->num_groups();
        $num_teams       = (int) $league->num_teams_total;

        if ( $settings->has_groups() ) {
            $num_advance           = $settings->num_advance;
            $num_teams_first_round = $num_groups * $num_advance;
            $num_rounds            = (int) log( $num_teams_first_round, 2 );
        } elseif ( $is_consolation ) {
            $progression           = $this->build_consolation_progression( $league, $num_teams );
            $num_teams             = $progression['num_teams'];
            $num_advance           = $progression['num_advance'];
            $num_rounds            = $progression['num_rounds'];
            $num_teams_first_round = $progression['num_teams_first_round'];
        } else {
            $progression           = $this->build_standard_progression( $league, $num_teams );
            $num_advance           = $progression['num_advance'];
            $num_rounds            = $progression['num_rounds'];
            $num_teams_first_round = $progression['num_teams_first_round'];
        }

        $num_seeds           = $this->calculate_num_seeds( (int) $league->num_teams_total, $is_consolation );
        $keys_by_round       = array();
        $finals_by_key       = array();
        $final_teams_by_round = array();

        $round_team_count = 2;
        $round_number     = $num_rounds;

        while ( $round_team_count <= $num_teams_first_round ) {
            $final_key   = Championship::resolve_final_key( $round_team_count );
            $num_matches = (int) ( $round_team_count / 2 );
            $is_final    = 'final' === $final_key;

            $finals_by_key[ $final_key ] = array(
                'key'         => $final_key,
                'is_final'    => $is_final,
                'name'        => Util::get_final_name( $final_key ),
                'num_matches' => $num_matches,
                'num_teams'   => $round_team_count,
                'colspan'     => ( $num_teams_first_round / 2 >= 4 ) ? (int) ceil( 4 / $num_matches ) : (int) ceil( ( $num_teams_first_round / 2 ) / $num_matches ),
                'round'       => $round_number,
            );

            $keys_by_round[ $round_number ] = $final_key;

            if ( 2 === $round_team_count && $settings->match_place3 ) {
                $finals_by_key['third'] = array(
                    'key'         => 'third',
                    'name'        => Util::get_final_name( 'third' ),
                    'num_matches' => $num_matches,
                    'num_teams'   => $round_team_count,
                    'colspan'     => ( $num_teams_first_round / 2 >= 4 ) ? (int) ceil( 4 / $num_matches ) : (int) ceil( ( $num_teams_first_round / 2 ) / $num_matches ),
                    'round'       => $round_number,
                );
            }

            --$round_number;
            $round_team_count = $round_team_count * 2;
        }

        foreach ( $finals_by_key as $key => $data ) {
            $final_teams_by_round[ $key ] = array();

            if ( $data['round'] > 1 ) {
                $previous_final_key = $keys_by_round[ $data['round'] - 1 ] ?? null;
                $previous_final     = null !== $previous_final_key ? ( $finals_by_key[ $previous_final_key ] ?? null ) : null;

                if ( isset( $previous_final['num_matches'] ) ) {
                    for ( $x = 1; $x <= $previous_final['num_matches']; $x++ ) {
                        if ( 'third' === $key ) {
                            /* translators: %1$s: round %2$d: match */
                            $title   = sprintf( __( 'Loser %1$s %2$d', 'racketmanager' ), $previous_final['name'], $x );
                            $team_id = '2_' . $previous_final['key'] . '_' . $x;
                        } else {
                            /* translators: %1$s: round %2$d: match */
                            $title   = sprintf( __( 'Winner %1$s %2$d', 'racketmanager' ), $previous_final['name'], $x );
                            $team_id = '1_' . $previous_final['key'] . '_' . $x;
                        }

                        $final_teams_by_round[ $key ][ $team_id ] = (object) array(
                            'id'    => $team_id,
                            'title' => $title,
                            'home'  => 0,
                        );
                    }
                }
            } elseif ( $settings->has_groups() ) {
                foreach ( $settings->groups as $group ) {
                    for ( $a = 1; $a <= $num_advance; $a++ ) {
                        $team_id = $a . '_' . $group;
                        $final_teams_by_round[ $key ][ $team_id ] = (object) array(
                            'id'    => $team_id,
                            /* translators: %1$d: team rank %2$s: group */
                            'title' => sprintf( __( '%1$d. Group %2$s', 'racketmanager' ), $a, $group ),
                            'home'  => 0,
                        );
                    }
                }
            } else {
                for ( $a = 1; $a <= $num_teams_first_round; $a++ ) {
                    $team_id = $a . '_';
                    $final_teams_by_round[ $key ][ $team_id ] = (object) array(
                        'id'    => $team_id,
                        /* translators: %d: rank number */
                        'title' => sprintf( __( 'Team Rank %d', 'racketmanager' ), $a ),
                        'home'  => 0,
                    );
                }
            }
        }

        return new Championship(
            $league_id,
            $is_consolation,
            $settings,
            $num_advance,
            $num_rounds,
            $num_teams,
            $num_teams_first_round,
            $num_seeds,
            $keys_by_round,
            $finals_by_key,
            $final_teams_by_round,
            $this->determine_current_final( $keys_by_round )
        );
    }

    /**
     * Check if the league is a consolation draw.
     *
     * @param object $league league object.
     *
     * @return bool
     */
    private function is_consolation( object $league ): bool {
        return ! empty( $league->event->primary_league ) && (int) $league->id !== (int) $league->event->primary_league;
    }

    /**
     * Build progression data for consolation draw.
     *
     * @param object $league league object.
     * @param int $num_teams number of teams.
     *
     * @return array
     */
    private function build_consolation_progression( object $league, int $num_teams ): array {
        $primary_league        = get_league( $league->event->primary_league );
        $max_rounds            = $primary_league->championship->num_rounds - 1;
        $max_teams_first_round = (int) pow( 2, $max_rounds );
        $first_round           = $primary_league->championship->get_final_keys( 1 );
        $outstanding_matches   = $primary_league->get_matches(
            array(
                'pending'          => true,
                'final'            => $first_round,
                'count'            => true,
                'season'           => $league->current_season['name'],
                'reset_query_args' => true,
            )
        );

        if ( $outstanding_matches || $num_teams > $max_teams_first_round ) {
            $num_teams  = 0;
            $num_rounds = $max_rounds;
        } else {
            $num_rounds = (int) ceil( log( $num_teams, 2 ) );
        }

        $num_teams_first_round = (int) pow( 2, $num_rounds );

        return array(
            'num_teams'             => $num_teams,
            'num_advance'           => $num_teams_first_round,
            'num_rounds'            => $num_rounds,
            'num_teams_first_round' => $num_teams_first_round,
        );
    }

    /**
     * Build progression data for standard draw.
     *
     * @param object $league league object.
     * @param int $num_teams number of teams.
     *
     * @return array
     */
    private function build_standard_progression( object $league, int $num_teams ): array {
        $num_advance = (int) pow( 2, $league->current_season['num_match_days'] );

        if ( $league->event->competition->is_active || $league->event->competition->is_complete ) {
            $use_teams = true;
        } elseif ( $num_teams > $num_advance ) {
            $use_teams = true;
        } else {
            $use_teams = false;
        }

        if ( $use_teams ) {
            $num_rounds            = (int) ceil( log( $num_teams, 2 ) );
            $num_teams_first_round = (int) pow( 2, $num_rounds );
        } else {
            $num_teams_first_round = $num_advance;
            $num_rounds            = (int) $league->current_season['num_match_days'];
        }

        return array(
            'num_teams'             => $num_teams,
            'num_advance'           => $num_advance,
            'num_rounds'            => $num_rounds,
            'num_teams_first_round' => $num_teams_first_round,
        );
    }

    /**
     * Calculate seed count.
     *
     * @param int $num_teams team count.
     * @param bool $is_consolation consolation flag.
     *
     * @return int
     */
    private function calculate_num_seeds( int $num_teams, bool $is_consolation ): int {
        return match ( true ) {
            $is_consolation => 0,
            $num_teams <= 10 => 2,
            $num_teams <= 20 => 4,
            $num_teams <= 40 => 8,
            $num_teams <= 80 => 16,
            $num_teams <= 132 => 32,
            default => 0,
        };
    }

    /**
     * Determine current final from request or default.
     *
     * @param array $keys_by_round keys by round.
     *
     * @return string
     */
    private function determine_current_final( array $keys_by_round ): string {
        if ( isset( $_GET['final'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return sanitize_text_field( wp_unslash( $_GET['final'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        return isset( $keys_by_round[1] ) ? (string) $keys_by_round[1] : '';
    }
}
