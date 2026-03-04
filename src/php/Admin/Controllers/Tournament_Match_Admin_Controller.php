<?php
/**
 * Tournament Match Admin Controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Tournament_Match_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Request_DTO;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Championship\Draw_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Tournament_Service;

use function Racketmanager\get_league;
use function Racketmanager\get_match;
use function Racketmanager\get_team;

readonly final class Tournament_Match_Admin_Controller {

    public function __construct(
        private Tournament_Service $tournament_service,
        private Draw_Action_Dispatcher $draw_action_dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * Controller for admin.php?page=racketmanager-tournaments&view=match
     *
     * @param array $query Typically $_GET
     * @param array $post  Typically $_POST
     * @return array{view_model?:Tournament_Match_Page_View_Model, redirect?:string, message?:string, message_type?:bool|string}
     *
     * @throws Invalid_Status_Exception
     * @throws Tournament_Not_Found_Exception
     */
    public function match_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_matches' );

        $is_post = ( 'POST' === strtoupper( strval( $_SERVER['REQUEST_METHOD'] ?? '' ) ) );

        //phpcs:disable WordPress.Security.NonceVerification.Recommended
        $tournament_id = isset( $query['tournament'] ) ? intval( $query['tournament'] ) : null;
        $league_id     = isset( $query['league'] ) ? intval( $query['league'] ) : ( isset( $post['league_id'] ) ? intval( $post['league_id'] ) : null );
        $final_key     = isset( $query['final'] ) ? sanitize_text_field( wp_unslash( $query['final'] ) ) : ( isset( $post['final'] ) ? sanitize_text_field( wp_unslash( strval( $post['final'] ) ) ) : null );
        $match_id      = isset( $query['edit'] ) ? intval( $query['edit'] ) : ( isset( $post['match'][0] ) ? intval( $post['match'][0] ) : null );
        //phpcs:enable WordPress.Security.NonceVerification.Recommended

        // POST: reuse the existing match-management action (updateLeague=match).
        if ( $is_post ) {
            // Nonce + capability must flow through Action_Guard_Interface.
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_manage-matches', 'edit_matches' );

            $dto = new Draw_Action_Request_DTO(
                tournament_id: $tournament_id ?? 0,
                league_id: $league_id ?? 0,
                season: isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : null,
                post: $post
            );

            $response = $this->draw_action_dispatcher->handle( $dto );

            $redirect_url = Admin_Redirect_Url_Builder::tournament_match( $query, $post, $tournament_id, $league_id, $final_key, $match_id );

            $result = array(
                'redirect' => $redirect_url,
            );

            if ( null !== $response->message ) {
                $result['message'] = $response->message;
                $result['message_type'] = Admin_Message_Mapper::to_legacy( $response->message_type );
            }

            return $result;
        }

        // GET: render the match edit screen (same variables as the legacy implementation).
        $tournament = $this->tournament_service->get_tournament( $tournament_id );
        $season     = $tournament->get_season();

        $league = get_league( $league_id );
        if ( ! $league || ! $match_id ) {
            throw new Invalid_Status_Exception( __( 'Match not found', 'racketmanager' ) );
        }

        $match = get_match( $match_id );
        if ( ! $match ) {
            throw new Invalid_Status_Exception( __( 'Match not found', 'racketmanager' ) );
        }

        $form_title      = __( 'Edit Match', 'racketmanager' );
        $submit_title    = $form_title;
        $matches         = array( $match );
        $match_day       = $match->match_day;
        $max_matches     = 1;

        $final       = $league->championship->get_finals( $final_key );
        $final_teams = $league->championship->get_final_teams( $final['key'] );

        if ( is_numeric( $match->home_team ) ) {
            $home_team  = get_team( $match->home_team );
            $home_title = $home_team?->title;
        } else {
            $home_team = $final_teams[ $match->home_team ] ?? null;
            $home_title = $home_team ? $home_team->title : null;
        }

        if ( is_numeric( $match->away_team ) ) {
            $away_team  = get_team( $match->away_team );
            $away_title = $away_team?->title;
        } else {
            $away_team = $final_teams[ $match->away_team ] ?? null;
            $away_title = $away_team ? $away_team->title : null;
        }

        // Template expects $teams list too; for a single cup game it isn't used for select, but keep it defined.
        $teams = $final_teams;

        $vm = new Tournament_Match_Page_View_Model(
            league: $league,
            tournament: $tournament,
            competition: $league->event->competition,
            season: $season,
            form_title: $form_title,
            submit_title: $submit_title,
            matches: $matches,
            edit: true,
            bulk: false,
            is_finals: true,
            mode: 'edit',
            home_title: strval( $home_title ?? '' ),
            away_title: strval( $away_title ?? '' ),
            teams: $teams,
            single_cup_game: true,
            max_matches: $max_matches,
            final_key: $final_key ?? '',
            match_day: $match_day,
        );

        return array(
            'view_model' => $vm,
        );
    }
}
