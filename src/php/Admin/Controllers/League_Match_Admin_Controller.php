<?php
declare( strict_types=1 );
/**
 * League Match Admin Controller
 *
 * Handles view=match on league screens (adding/editing match fixtures).
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\View_Models\Tournament_Fixtures_Page_View_Model;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Exceptions\Fixture_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Util\Util;
use stdClass;

final readonly class League_Match_Admin_Controller {

    public function __construct(
        private League_Service $league_service,
        private League_Query_Service $league_query_service,
        private Team_Service $team_service,
        private Fixture_Repository_Interface $fixture_repository,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     *
     * @return array{view_model?:Tournament_Fixtures_Page_View_Model, redirect?:string, message?:string, message_type?:bool|string}
     *
     * @throws Invalid_Status_Exception
     * @throws League_Not_Found_Exception
     * @throws Fixture_Not_Found_Exception
     */
    public function match_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_matches' );

        $league_id = isset( $query['league_id'] ) ? intval( $query['league_id'] ) : intval( $post['league_id'] ?? 0 );
        $league    = null;

        if ( $league_id ) {
            $league = $this->league_service->get_league( $league_id );
            if ( $league instanceof League ) {
                $this->league_query_service->set_season( $league );
            }
        }

        $is_finals       = false;
        $final_key       = '';
        $cup             = false;
        $single_cup_game = false;
        $group           = isset( $query['group'] ) ? sanitize_text_field( wp_unslash( strval( $query['group'] ) ) ) : '';
        $bulk            = false;
        $matches         = array();
        $match           = null;
        $max_matches     = 0;
        $edit            = false;
        $mode            = 'add';
        $form_title      = '';
        $submit_title    = '';
        $home_title      = '';
        $away_title      = '';
        $match_day       = null;

        if ( $league instanceof League ) {
            $cup = (bool) ( $league->event->competition->is_championship ?? false );
        }

        if ( isset( $query['edit'] ) ) {
            $match_id = intval( $query['edit'] );
            $match    = $this->fixture_repository->find_by_id( $match_id );
            if ( ! $match ) {
                throw new Fixture_Not_Found_Exception( __( 'Match not found', 'racketmanager' ) );
            }
            $mode         = 'edit';
            $edit         = true;
            $form_title   = __( 'Edit Match', 'racketmanager' );
            $submit_title = $form_title;
            if ( isset( $match->final_round ) && '' !== $match->final_round ) {
                $cup             = true;
                $single_cup_game = true;
            }
            $match_league_id = (int) ( $match->league_id ?? 0 );
            if ( ! $league && $match_league_id ) {
                $league = $this->league_service->get_league( $match_league_id );
                if ( $league instanceof League ) {
                    $this->league_query_service->set_season( $league );
                }
            }
            $matches[0]  = $match;
            $match_day   = $match->match_day;
            $final_key   = (string) ( $match->final_round ?? '' );
            $max_matches = 1;
        }

        if ( ! $league instanceof League ) {
            throw new League_Not_Found_Exception( __( 'League not found', 'racketmanager' ) );
        }

        $season = $league->current_season['name'] ?? ( method_exists( $league, 'get_season' ) ? (string) $league->get_season() : '' );

        if ( $cup && empty( $group ) ) {
            $groups = ( $league->groups ?? '' );
            if ( ! is_array( $groups ) ) {
                $groups = explode( ';', (string) $groups );
            }
            $group = $groups[0] ?? '';
        }

        if ( ! isset( $query['edit'] ) ) {
            if ( isset( $query['match_day'] ) ) {
                $mode      = 'edit';
                $edit      = true;
                $bulk      = true;
                $match_day = intval( $query['match_day'] );
                $season    = isset( $query['season'] ) ? sanitize_text_field( wp_unslash( strval( $query['season'] ) ) ) : $season;

                $match_args = array(
                    'match_day' => $match_day,
                    'season'    => $season,
                );
                if ( $cup ) {
                    $match_args['group'] = $group;
                }
                /* translators: %d: Match day */
                $form_title   = sprintf( __( 'Edit Matches - Match Day %d', 'racketmanager' ), $match_day );
                $submit_title = __( 'Edit Matches', 'racketmanager' );

                $matches     = $this->league_query_service->get_matches( $league, $match_args );
                $max_matches = count( $matches );
            } elseif ( isset( $query['final'] ) ) {
                if ( ! $league instanceof League || ! $league->championship ) {
                    throw new Invalid_Status_Exception( __( 'Championship not found', 'racketmanager' ) );
                }
                $is_finals = true;
                $final_key = isset( $query['final'] ) && '' !== $query['final'] ? sanitize_text_field( wp_unslash( strval( $query['final'] ) ) ) : (string) $league->championship->get_current_final_key();
                $mode      = isset( $query['mode'] ) ? sanitize_text_field( wp_unslash( strval( $query['mode'] ) ) ) : '';
                $edit      = 'edit' === $mode;

                $final           = $league->championship->get_finals( $final_key );
                $num_first_round = $league->championship->num_teams_first_round;
                $max_matches     = (int) ( $final['num_matches'] ?? 0 );

                if ( 'add' === $mode ) {
                    /* translators: %s: round name */
                    $form_title = sprintf( __( 'Add Matches - %s', 'racketmanager' ), Util::get_final_name( $final_key ) );
                    for ( $h = 0; $h < $max_matches; $h++ ) {
                        $matches[ $h ] = new stdClass();
                        if ( 'final' !== $final_key ) {
                            $round = $final['round'] ?? 1;
                            if ( $round & 1 ) {
                                $matches[ $h ]->host = 'home';
                            } else {
                                $matches[ $h ]->host = 'away';
                            }
                        }
                        $matches[ $h ]->hour    = $league->event->competition->settings['default_match_start_time']['hour'] ?? 18;
                        $matches[ $h ]->minutes = $league->event->competition->settings['default_match_start_time']['minutes'] ?? 0;
                    }
                } else {
                    /* translators: %s: round name */
                    $form_title = sprintf( __( 'Edit Matches - %s', 'racketmanager' ), Util::get_final_name( $final_key ) );
                    $match_args = array(
                        'final'   => $final_key,
                        'orderby' => array( 'id' => 'ASC' ),
                    );
                    if ( 'final' !== $final_key && ! empty( $league->current_season['home_away'] ) && 'true' === $league->current_season['home_away'] ) {
                        $match_args['leg'] = 1;
                    }
                    $matches = $this->league_query_service->get_matches( $league, $match_args );
                }
                $submit_title = $form_title;
            } else {
                $mode = 'add';
                $edit = false;
                $bulk = $cup;

                $submit_title = __( 'Add Matches', 'racketmanager' );
                if ( $cup ) {
                    /* translators: %s: group name */
                    $form_title  = sprintf( __( 'Add Matches - Group %s', 'racketmanager' ), $group );
                    $num_days    = (int) ( $league->current_season['num_match_days'] ?? 1 );
                    $max_matches = (int) ceil( ( $league->num_teams / 2 ) * $num_days );
                } else {
                    $form_title  = $submit_title;
                    $max_matches = (int) ceil( (float) ( $league->num_teams_total ?? 0 ) );
                }
                $match_day        = 1;
                $matches          = array();
                $matches[0]       = new stdClass();
                $matches[0]->year = ( isset( $query['season'] ) && is_numeric( $query['season'] ) ) ? intval( $query['season'] ) : gmdate( 'Y' );
                for ( $i = 0; $i < $max_matches; $i++ ) {
                    $matches[ $i ]          = new stdClass();
                    $matches[ $i ]->hour    = $league->event->competition->settings['default_match_start_time']['hour'] ?? 18;
                    $matches[ $i ]->minutes = $league->event->competition->settings['default_match_start_time']['minutes'] ?? 0;
                }
            }
        }

        $teams = array();
        if ( $single_cup_game && null !== $match ) {
            $final       = $league->championship->get_finals( $final_key );
            $final_teams = $league->championship->get_final_teams( $final['key'] ?? '' );
            if ( is_numeric( $match->home_team ?? null ) ) {
                try {
                    $home_team = $this->team_service->get_team_by_id( (int) $match->home_team );
                } catch ( \Exception ) {
                    $home_team = null;
                }
                $home_title = (string) ( $home_team?->title ?? '' );
            } else {
                $home_team  = $final_teams[ $match->home_team ] ?? null;
                $home_title = (string) ( $home_team->title ?? '' );
            }
            if ( is_numeric( $match->away_team ?? null ) ) {
                try {
                    $away_team = $this->team_service->get_team_by_id( (int) $match->away_team );
                } catch ( \Exception ) {
                    $away_team = null;
                }
                $away_title = (string) ( $away_team?->title ?? '' );
            } else {
                $away_team  = $final_teams[ $match->away_team ] ?? null;
                $away_title = (string) ( $away_team->title ?? '' );
            }
        } elseif ( $is_finals ) {
            $teams = $league->championship->get_final_teams( $final_key );
            if ( 'add' === $mode ) {
                $round = $final['round'] ?? 1;
                if ( 1 !== intval( $round ) ) {
                    $prev_round      = ( (int) $final['round'] ) - 1;
                    $prev_round_name = $league->championship->get_final_keys( $prev_round );
                    $first_round     = false;
                    $home_team       = 1;
                    $away_team       = 2;
                } else {
                    $first_round = true;
                    $team_array  = match ( $max_matches ) {
                        1       => array( 1 ),
                        2       => array( 1, 3 ),
                        4       => array( 1, 5, 3, 7 ),
                        8       => array( 1, 9, 4, 12, 11, 14, 7, 15 ),
                        16      => array( 1, 17, 9, 25, 4, 21, 13, 28, 6, 22, 14, 30, 7, 23, 15, 31 ),
                        32      => array( 1, 33, 17, 49, 9, 41, 25, 57, 4, 36, 20, 52, 12, 44, 28, 60, 6, 38, 22, 54, 14, 46, 30, 62, 7, 39, 23, 55, 15, 47, 31, 63 ),
                        default => array(),
                    };
                }
                for ( $i = 0; $i < $max_matches; $i++ ) {
                    if ( $first_round ) {
                        $home_team      = $team_array[ $i ] ?? 1;
                        $home_team_name = $home_team . '_';
                        $away_team      = ( $num_first_round + 1 ) - $home_team;
                        $away_team_name = $away_team . '_';
                    } else {
                        $home_team_name = '1_' . $prev_round_name . '_' . $home_team;
                        $away_team_name = '1_' . $prev_round_name . '_' . $away_team;
                    }
                    $matches[ $i ]->home_team = $teams[ $home_team_name ]->id ?? null;
                    $matches[ $i ]->away_team = $teams[ $away_team_name ]->id ?? null;
                    if ( $first_round ) {
                        ++$home_team;
                    } else {
                        $home_team += 2;
                        $away_team += 2;
                    }
                }
            }
        } else {
            $teams = $this->league_query_service->get_league_teams(
                $league,
                array(
                    'season'  => $season,
                    'orderby' => array( 'title' => 'ASC' ),
                )
            );
        }

        $competition = $league->event->competition;
        $vm          = new Tournament_Fixtures_Page_View_Model(
            league: $league,
            tournament: $competition,
            competition: $competition,
            season: (string) $season,
            form_title: $form_title,
            submit_title: $submit_title,
            fixtures: is_array( $matches ) ? $matches : array(),
            edit: $edit,
            bulk: $bulk,
            is_finals: $is_finals,
            mode: $mode,
            teams: is_array( $teams ) ? $teams : array(),
            single_cup_game: $single_cup_game,
            max_fixtures: $max_matches,
            final_key: (string) $final_key,
            home_title: (string) $home_title,
            away_title: (string) $away_title,
            match_day: $match_day,
        );

        return array(
            'view_model' => $vm,
        );
    }
}
