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
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\Validator\Validator_Tournament;

use function Racketmanager\get_league;
use function Racketmanager\get_match;
use function Racketmanager\get_team;

readonly final class Tournament_Match_Admin_Controller {

    public function __construct(
        private Tournament_Service $tournament_service,
        private Draw_Action_Dispatcher $draw_action_dispatcher,
    ) {
    }

    private function build_match_redirect_url( array $query, array $post, ?int $tournament_id, ?int $league_id, ?string $final_key, ?int $match_id ): string {
        $args = array(
            'page'       => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-tournaments',
            'view'       => 'match',
            'tournament' => $tournament_id,
            'league'     => $league_id,
            'final'      => $final_key,
            'edit'       => $match_id,
        );

        $args = array_merge( $args, Redirect_Context_Params::from( $query, $post ) );

        return add_query_arg( $args, admin_url( 'admin.php' ) );
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
        $v = new Validator_Tournament();
        $v = $v->capability( 'edit_matches' );
        if ( ! empty( $v->error ) ) {
            throw new Invalid_Status_Exception( $v->msg );
        }

        $is_post = ( 'POST' === strtoupper( strval( $_SERVER['REQUEST_METHOD'] ?? '' ) ) );

        //phpcs:disable WordPress.Security.NonceVerification.Recommended
        $tournament_id = isset( $query['tournament'] ) ? intval( $query['tournament'] ) : null;
        $league_id     = isset( $query['league'] ) ? intval( $query['league'] ) : ( isset( $post['league_id'] ) ? intval( $post['league_id'] ) : null );
        $final_key     = isset( $query['final'] ) ? sanitize_text_field( wp_unslash( $query['final'] ) ) : ( isset( $post['final'] ) ? sanitize_text_field( wp_unslash( strval( $post['final'] ) ) ) : null );
        $match_id      = isset( $query['edit'] ) ? intval( $query['edit'] ) : ( isset( $post['match'][0] ) ? intval( $post['match'][0] ) : null );
        //phpcs:enable WordPress.Security.NonceVerification.Recommended

        // POST: reuse the existing match-management action (updateLeague=match).
        if ( $is_post ) {
            $dto = new Draw_Action_Request_DTO(
                tournament_id: $tournament_id ?? 0,
                league_id: $league_id ?? 0,
                season: isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : null,
                post: $post
            );

            $response = $this->draw_action_dispatcher->handle( $dto );

            $redirect_url = $this->build_match_redirect_url( $query, $post, $tournament_id, $league_id, $final_key, $match_id );

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

        // Template expects $teams list too; for single cup game it isn't used for selects, but keep it defined.
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
