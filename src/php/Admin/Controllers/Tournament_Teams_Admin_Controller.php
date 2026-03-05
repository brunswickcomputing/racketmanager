<?php
/**
 * Tournament Teams Admin Controller
 *
 * Handles admin.php?page=racketmanager-tournaments&view=teams
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\View_Models\Tournament_Teams_List_Page_View_Model;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use stdClass;
use function Racketmanager\get_league;
use function Racketmanager\get_club;

readonly final class Tournament_Teams_Admin_Controller {

    public function __construct(
        private Tournament_Service $tournament_service,
        private League_Service $league_service,
        private Team_Service $team_service,
        private Club_Service $club_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array $query Typically $_GET
     * @param array $post  Typically $_POST
     * @return array{view_model?:Tournament_Teams_List_Page_View_Model, redirect?:string, message?:string, message_type?:bool|string}
     *
     * @throws Invalid_Status_Exception
     * @throws Tournament_Not_Found_Exception
     */
    public function teams_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_teams' );

        $is_post = ( 'POST' === strtoupper( strval( $_SERVER['REQUEST_METHOD'] ?? '' ) ) );

        //phpcs:disable WordPress.Security.NonceVerification.Recommended
        $league_id     = isset( $query['league_id'] ) ? intval( $query['league_id'] ) : ( isset( $query['league'] ) ? intval( $query['league'] ) : ( isset( $post['league_id'] ) ? intval( $post['league_id'] ) : null ) );
        $season        = isset( $query['season'] ) ? sanitize_text_field( wp_unslash( strval( $query['season'] ) ) ) : ( isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : '' );
        $tournament_id = isset( $query['tournament'] ) ? intval( $query['tournament'] ) : ( isset( $post['tournament_id'] ) ? intval( $post['tournament_id'] ) : null );
        $type          = isset( $query['type'] ) ? sanitize_text_field( wp_unslash( strval( $query['type'] ) ) ) : null;
        //phpcs:enable WordPress.Security.NonceVerification.Recommended

        if ( ! $league_id ) {
            throw new Invalid_Status_Exception( __( 'League not found', 'racketmanager' ) );
        }

        $league = get_league( $league_id );
        if ( ! $league ) {
            throw new Invalid_Status_Exception( __( 'League not found', 'racketmanager' ) );
        }

        $tournament = null;
        if ( $tournament_id ) {
            $tournament = $this->tournament_service->get_tournament( $tournament_id );
        }

        // POST: add selected teams to a league, then PRG redirect back to draw.
        if ( $is_post ) {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_add-teams-bulk', 'edit_teams' );

            $added = 0;

            //phpcs:disable WordPress.Security.NonceVerification.Missing
            $team_ids = array();
            if ( isset( $post['team'] ) && is_array( $post['team'] ) ) {
                foreach ( $post['team'] as $team_id ) {
                    $team_ids[] = intval( $team_id );
                }
            }
            //phpcs:enable WordPress.Security.NonceVerification.Missing

            foreach ( $team_ids as $team_id ) {
                $this->league_service->add_team_to_league( $team_id, $league_id, $season );
                ++$added;
            }

            $redirect = Admin_Redirect_Url_Builder::tournament_draw_view(
                $query,
                $post,
                'draw',
                $tournament_id,
                $league_id,
                // After adding teams, take the user back to the Teams tab.
                'preliminary'
            );

            return array(
                'redirect' => $redirect,
                /* translators: %d: number of teams */
                'message' => sprintf( _n( '%d team added', '%d teams added', $added, 'racketmanager' ), $added ),
                'message_type' => false,
            );
        }

        // GET: build a list of addable teams (mirrors legacy selection rules).
        $league_type = $league->type ?? '';
        if ( 'LD' === $league_type ) {
            $league_type = 'XD';
        }

        if ( empty( $league->championship->is_consolation ) ) {
            if ( $league->event->competition->is_player_entry ) {
                $teams = $this->team_service->get_player_teams( $league_type );
            } else {
                $teams = $this->get_club_teams( $league_type );
            }
        } else {
            // Consolation selection remains legacy-only for now.
            $teams = $this->get_consolation_teams( $league );
        }

        $vm = new Tournament_Teams_List_Page_View_Model(
            league: $league,
            league_id: $league_id,
            season: strval( $season ),
            tournament_id: $tournament_id,
            tournament: $tournament,
            teams: $teams,
            type: $type,
        );

        return array(
            'view_model' => $vm,
        );
    }

    /**
     * Legacy behaviour: list all club teams for affiliated clubs for a given league type.
     *
     * @return array<int,object>
     */
    private function get_club_teams( string $league_type ): array {
        $teams = array();

        $clubs = $this->club_service->get_clubs(
            array(
                'type' => 'affiliated',
            )
        );

        if ( $clubs ) {
            foreach ( $clubs as $club ) {
                $club_obj = get_club( $club );
                if ( ! $club_obj ) {
                    continue;
                }

                $club_teams = $this->team_service->get_teams_for_club( $club_obj->id, $league_type );
                if ( $club_teams ) {
                    foreach ( $club_teams as $team ) {
                        $teams[] = $team;
                    }
                }
            }
        }

        return $teams;
    }

    /**
     * Legacy behaviour: build an eligible consolation teams list.
     *
     * @return array<int,object>
     */
    private function get_consolation_teams( object $league ): array {
        $primary_league = get_league( $league->event->primary_league );
        if ( ! $primary_league ) {
            return array();
        }

        $teams = $primary_league->get_league_teams();
        $t     = 0;

        foreach ( $teams as $team ) {
            $match_array                     = array();
            $match_array['loser_id']         = $team->id;
            $match_array['count']            = true;
            $match_array['final']            = 'all';
            $match_array['reset_query_args'] = true;
            $matches                         = $primary_league->get_matches( $match_array );

            if ( ! $matches ) {
                unset( $teams[ $t ] );
            } else {
                $match_array['loser_id'] = null;
                $match_array['team_id']  = $team->id;
                $matches                 = $primary_league->get_matches( $match_array );
                $last_match              = null;

                if ( $matches > 2 ) {
                    unset( $teams[ $t ] );
                } elseif ( 2 === $matches ) {
                    $match_array['count'] = false;
                    $matches              = $primary_league->get_matches( $match_array );
                    if ( $matches ) {
                        $first_match = $matches[0];
                        if ( '-1' !== $first_match->home_team && '-1' !== $first_match->away_team ) {
                            unset( $teams[ $t ] );
                        } else {
                            $last_match = $matches[1];
                        }
                    }
                } elseif ( 1 === $matches ) {
                    $match_array['count'] = false;
                    $matches              = $primary_league->get_matches( $match_array );
                    $last_match           = $matches[0];
                }

                if ( $last_match && ! empty( $last_match->is_walkover ) ) {
                    unset( $teams[ $t ] );
                }
            }

            ++$t;
        }

        $match_array                     = array();
        $match_array['reset_query_args'] = true;
        $final_name                      = $primary_league->championship->get_final_keys( 1 );
        $match_array['final']            = $final_name;
        $match_array['pending']          = true;
        $matches                         = $primary_league->get_matches( $match_array );

        if ( $matches ) {
            foreach ( $matches as $match ) {
                $teams[] = $this->build_loser_team( $final_name, $match );
            }
        }

        $final_name           = $primary_league->championship->get_final_keys( 2 );
        $match_array['final'] = $final_name;
        $matches              = $primary_league->get_matches( $match_array );

        if ( $matches ) {
            foreach ( $matches as $match ) {
                $possible   = 0;
                $team_types = array( 'home', 'away' );
                foreach ( $team_types as $team_type ) {
                    $team_ref = $team_type . '_team';
                    if ( is_numeric( $match->$team_ref ) ) {
                        $match_array['pending']   = false;
                        $match_array['final']     = 'all';
                        $match_array['winner_id'] = $match->$team_ref;
                        $team_matches             = $primary_league->get_matches( $match_array );
                        foreach ( $team_matches as $team_match ) {
                            if ( '-1' === $team_match->home_team || '-1' === $team_match->away_team ) {
                                ++ $possible;
                            }
                        }
                    }
                }
                if ( $possible ) {
                    $teams[] = $this->build_loser_team( $final_name, $match );
                }
            }
        }

        return $teams;
    }

    private function build_loser_team( string $final_name, object $match ): object {
        $team          = new stdClass();
        $team->id      = '2_' . $final_name . '_' . $match->id;
        $team->title   = __( 'Loser of ', 'racketmanager' ) . $match->teams['home']->title . ' ' . __( 'vs', 'racketmanager' ) . ' ' . $match->teams['away']->title;
        $team->stadium = '';
        return $team;
    }

}
