<?php
/**
 * RacketManager-Admin API: Admin_Championship class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Championship
 */

namespace Racketmanager\Admin;

use Racketmanager\Services\Validator\Validator;
use stdClass;
use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\get_match;

/**
 * RacketManager Championship Admin functions
 * Class to implement RacketManager Admin Championship
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Championship
 */
class Admin_Championship extends Admin_Display {
    /**
     * Set championship matches function
     *
     * @param object $league league object.
     * @param int $season season name.
     * @param array $input_rounds round details.
     * @param string $action action on matches.
     */
    protected function set_championship_matches( object $league, int $season, array $input_rounds, string $action ): void {
        $team_array      = array();
        $prev_round_name = null;
        $home_team       = null;
        $away_team       = null;
        $matches         = array();
        $valid           = true;
        $event_season    = $league->event->get_season_by_name( $season );
        $num_first_round = $league->championship->num_teams_first_round;
        $rounds          = array();
        $msg             = null;
        foreach ( $input_rounds as $round ) {
            if ( empty( $round['match_date'] ) ) {
                /* translators: $s: $round number */
                $msg[] = sprintf( __( 'Match date missing for round %s', 'racketmanager' ), $round['round'] );
                $valid = false;
            } elseif ( ! empty( $next_round_date ) && $round['match_date'] >= $next_round_date ) {
                /* translators: $s: $round number */
                $msg[] = sprintf( __( 'Match date for round %s after next round date', 'racketmanager' ), $round['round'] );
                $valid = false;
            } else {
                $round_date = $round['match_date'];
                $teams      = $league->championship->get_final_teams( $round['key'] );
                if ( 1 !== intval( $round['round'] ) ) {
                    $prev_round      = $round['round'] - 1;
                    $prev_round_name = $league->championship->get_final_keys( $prev_round );
                    $first_round     = false;
                    $home_team       = 1;
                    $away_team       = 2;
                } else {
                    $first_round = true;
                    $team_array = match ( $round['num_matches'] ) {
                        '1' => array(1),
                        '2' => array(1, 3),
                        '4' => array(1, 5, 3, 7),
                        '8' => array(1, 9, 4, 12, 11, 14, 7, 15),
                        '16' => array(1, 17, 9, 25, 4, 21, 13, 28, 6, 22, 14, 30, 7, 23, 15, 31),
                        '32' => array(1, 33, 17, 49, 9, 41, 25, 57, 4, 36, 20, 52, 12, 44, 28, 60, 6, 38, 22, 54, 14, 46, 30, 62, 7, 39, 23, 55, 15, 47, 31, 63),
                        default => array(),
                    };
                }
                $matches[ $round_date ] = array();
                for ( $i = 0; $i < $round['num_matches']; ++$i ) {
                    $match            = new stdClass();
                    $match->date      = $round_date . ' 00:00:00';
                    $match->match_day = null;
                    if ( 'final' !== $round['key'] ) {
                        if ( $round['round'] & 1 ) {
                            $match->host = 'home';
                        } else {
                            $match->host = 'away';
                        }
                    }
                    if ( $first_round ) {
                        $home_team      = $team_array[ $i ];
                        $home_team_name = $home_team . '_';
                        $away_team      = $num_first_round + 1 - $home_team;
                        $away_team_name = $away_team . '_';
                    } else {
                        $home_team_name = '1_' . $prev_round_name . '_' . $home_team;
                        $away_team_name = '1_' . $prev_round_name . '_' . $away_team;
                    }
                    $match->home_team = $teams[ $home_team_name ]->id;
                    $match->away_team = $teams[ $away_team_name ]->id;
                    if ( $first_round ) {
                        ++$home_team;
                        $away_team = $num_first_round + 1 - $home_team;
                    } else {
                        $home_team += 2;
                        $away_team += 2;
                    }
                    $match->location          = null;
                    $match->league_id         = $league->id;
                    $match->season            = $season;
                    $match->final_round       = $round['key'];
                    $match->num_rubbers       = $league->num_rubbers;
                    $matches[ $round_date ][] = $match;
                }
                $next_round_date               = $round['match_date'];
                $rounds[ $round['key'] ]       = new stdClass();
                $rounds[ $round['key'] ]->name = $round['key'];
                $rounds[ $round['key'] ]->num  = $round['round'];
                $rounds[ $round['key'] ]->date = $round['match_date'];
            }
        }
        if ( $valid ) {
            $league->set_rounds( $season, $rounds );
            if ( 'replace' === $action ) {
                $league->delete_season_matches( $season );
                $message = __( 'Matches replaced', 'racketmanager' );
            } else {
                $message = __( 'Matches added', 'racketmanager' );
            }
            $event_season['match_dates'] = array();
            foreach ( array_reverse( $matches ) as $match_date => $round_matches ) {
                $event_season['match_dates'][] = $match_date;
                foreach ( $round_matches as $match ) {
                    $league->add_match( $match );
                }
            }
            if ( ! $league->championship->is_consolation ) {
                $event_season['num_match_days'] = count( $event_season['match_dates'] );
                $event                          = get_event( $league->event_id );
                if ( $event ) {
                    $event_seasons            = $event->seasons;
                    $event_seasons[ $season ] = $event_season;
                    $event->update_seasons( $event_seasons );
                }
            }
            $this->set_message( $message );
        } else {
            $message = implode( '<br>', $msg );
            $this->set_message( $message, true );
        }
        $this->show_message();
    }
    /**
     * Handle administration panel
     *
     * @param object|null $league league object.
     */
    public function handle_championship_admin_page( object $league = null ): string {
        $validator = new Validator();
        $league = get_league( $league );
        $tab    = 'finalResults'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        if ( isset( $_POST['action'] ) ) {
            $action = sanitize_text_field( wp_unslash( $_POST['action'] ) );
            if ( 'startFinals' === $action ) {
                $validator = $validator->check_security_token( 'racketmanager_proceed_nonce', 'racketmanager_championship_proceed' );
                if ( empty( $validator->error ) ) {
                    $validator = $validator->capability( 'update_results');
                }
                if ( empty( $validator->error ) ) {
                    $updates = $this->start_final_rounds( $league );
                    if ( $updates ) {
                        $this->set_message( __( 'First round started', 'racketmanager' ) );
                    } else {
                        $this->set_message( __( 'First round not started', 'racketmanager' ), true );
                        $tab = 'preliminary'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                    }
                }
            } elseif ( 'updateFinalResults' === $action ) {
                $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_update-finals' );
                if ( empty( $validator->error ) ) {
                    $validator = $validator->capability( 'update_results');
                }
                if ( empty( $validator->error ) ) {
                    $custom      = $_POST['custom'] ?? array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                    $matches     = $_POST['matches'] ?? array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                    $home_points = $_POST['home_points'] ?? array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                    $away_points = $_POST['away_points'] ?? array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                    $round       = isset( $_POST['round'] ) ? intval( $_POST['round'] ) : null;
                    $season      = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
                    $league->championship->update_final_results( $matches, $home_points, $away_points, $custom, $round, $season );
                }
                if ( ! empty( $validator->error ) ) {
                    if ( empty( $validator->msg ) ) {
                        $validator->msg = __( 'Errors found', 'racketmanager' );
                    }
                    $this->set_message( $validator->msg, true );
                }
            }
            $this->show_message();
        }
        if ( count( $league->championship->groups ) > 0 ) {
            $league->set_group( $league->championship->groups[0] );
        }

        return $tab; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
    }
    /**
     * Start final rounds
     */
    private function start_final_rounds( $league ): bool {
        $updates = false;
        $home    = null;
        $away    = null;
        $league        = get_league( $league );
        $multiple_legs = false;
        $round_name    = $league->championship->get_final_keys( 1 );
        $match_args    = array(
            'final'            => $round_name,
            'limit'            => false,
            'match_day'        => -1,
            'reset_query_args' => true,
        );
        // get first round matches.
        if ( $league->event->current_season['home_away'] ) {
            $multiple_legs     = true;
            $match_args['leg'] = 1;
        }
        $matches_list = array();
        $matches      = $league->get_matches( $match_args );
        foreach ( $matches as $match ) {
            $matches_list[] = $match->id;
            if ( '-1' === $match->home_team ) {
                $home['team'] = -1;
                $home_team    = array( 'id' => -1 );
            } elseif ( str_contains( $match->home_team, '_' ) ) {
                $home      = explode( '_', $match->home_team );
                $home      = array(
                    'rank'  => $home[0],
                    'group' => $home[1] ?? '',
                );
                $home_team = $league->get_league_teams(
                    array(
                        'rank'             => $home['rank'],
                        'group'            => $home['group'],
                        'reset_query_args' => true,
                    )
                );
                if ( $home_team ) {
                    $home['team']         = $home_team[0]->id;
                    $match->home_team     = $home['team'];
                    $match->teams['home'] = $league->get_team_dtls( $home_team[0]->id );
                } else {
                    $home['team'] = -1;
                    $home_team    = array( 'id' => -1 );
                }
            } else {
                $home_team = '';
            }
            if ( '-1' === $match->away_team ) {
                $away['team'] = -1;
                $away_team    = array( 'id' => -1 );
            } elseif ( str_contains( $match->away_team, '_' ) ) {
                $away      = explode( '_', $match->away_team );
                $away      = array(
                    'rank'  => $away[0],
                    'group' => $away[1] ?? '',
                );
                $away_team = $league->get_league_teams(
                    array(
                        'rank'             => $away['rank'],
                        'group'            => $away['group'],
                        'reset_query_args' => true,
                    )
                );
                if ( $away_team ) {
                    $away['team']         = $away_team[0]->id;
                    $match->away_team     = $away['team'];
                    $match->teams['away'] = $league->get_team_dtls( $away_team[0]->id );
                } else {
                    $away['team'] = -1;
                    $away_team    = array( 'id' => -1 );
                }
            } else {
                $away_team = '';
            }
            if ( $home_team && $away_team ) {
                $league->championship->set_teams( $match, $home['team'], $away['team'] );
                $updates = true;
            }
        }
        if ( $matches_list ) {
            if ( $multiple_legs ) {
                foreach ( $matches_list as $match_id ) {
                    $match = get_match( $match_id );
                    if ( $match && $match->linked_match ) {
                        $matches_list[] = $match->linked_match;
                    }
                }
            }
            $league->championship->update_final_results( $matches_list, array(), array(), array(), 1, $league->current_season['name'] );
        }
        return $updates;
    }
}
