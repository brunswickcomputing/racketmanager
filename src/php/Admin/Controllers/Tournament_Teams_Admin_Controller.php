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
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;

readonly final class Tournament_Teams_Admin_Controller {

    public function __construct(
        private Tournament_Service $tournament_service,
        private League_Service $league_service,
        private Team_Service $team_service,
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

        // Extract input parameters.
        $league_id     = intval( $query['league_id'] ?? $query['league'] ?? $post['league_id'] ?? 0 );
        $season        = sanitize_text_field( wp_unslash( strval( $query['season'] ?? $post['season'] ?? '' ) ) );
        $tournament_id = intval( $query['tournament'] ?? $post['tournament_id'] ?? 0 );
        $type          = isset( $query['type'] ) ? sanitize_text_field( wp_unslash( strval( $query['type'] ) ) ) : null;

        if ( ! $league_id ) {
            throw new Invalid_Status_Exception( __( 'League not found', 'racketmanager' ) );
        }

        $league = $this->league_service->get_league( $league_id );
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

            $team_ids = array_map( 'intval', (array) ( $post['team'] ?? array() ) );
            $added    = $this->league_service->add_teams_to_league( $team_ids, $league_id, (int) $season );

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
                $teams = $this->team_service->get_club_teams( $league_type );
            }
        } else {
            $teams = $this->league_service->get_consolation_teams( $league, $season );
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

}
