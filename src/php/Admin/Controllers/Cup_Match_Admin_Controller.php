<?php
declare( strict_types=1 );
/**
 * Cup Match Admin Controller
 *
 * Handles view=match on cup screens (editing single cup match).
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Tournament_Fixtures_Page_View_Model;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Request_DTO;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Response_DTO;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Fixture_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Admin\Championship\Draw_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;

final readonly class Cup_Match_Admin_Controller {

    public function __construct(
        private Competition_Service $competition_service,
        private League_Repository_Interface $league_repository,
        private Fixture_Repository_Interface $fixture_repository,
        private Team_Repository_Interface $team_repository,
        private Draw_Action_Dispatcher $draw_action_dispatcher,
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
     * @throws Competition_Not_Found_Exception
     * @throws League_Not_Found_Exception
     * @throws Fixture_Not_Found_Exception
     */
    public function match_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_matches' );

        $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
        $is_post        = 'POST' === strtoupper( $request_method );

        if ( $is_post ) {
            $league_id = isset( $query['league'] ) ? intval( $query['league'] ) : ( isset( $query['league_id'] ) ? intval( $query['league_id'] ) : ( isset( $post['league_id'] ) ? intval( $post['league_id'] ) : null ) );
            $season    = isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : ( isset( $query['season'] ) ? sanitize_text_field( wp_unslash( strval( $query['season'] ) ) ) : null );

            $dto = new Draw_Action_Request_DTO(
                tournament_id: 0,
                league_id: $league_id ?? 0,
                season: $season,
                post: $post
            );

            try {
                $response = $this->draw_action_dispatcher->handle( $dto );
            } catch ( Invalid_Status_Exception $e ) {
                $response = new Draw_Action_Response_DTO(
                    message: $e->getMessage(),
                    message_type: Admin_Message_Type::ERROR
                );
            }

            if ( $response->is_handled() ) {
                $extra_args = array();
                if ( null !== $response->tab_override && '' !== $response->tab_override ) {
                    $extra_args['tab'] = $response->tab_override;
                }

                $redirect = Admin_Redirect_Url_Builder::cup_screen( $query, $extra_args );

                return array(
                    'redirect'     => $redirect,
                    'message'      => $response->message,
                    'message_type' => Admin_Message_Mapper::to_legacy( $response->message_type ),
                );
            }
        }

        $season         = isset( $query['season'] ) ? intval( $query['season'] ) : null;
        $competition_id = isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : null;
        $league_id      = isset( $query['league'] ) ? intval( $query['league'] ) : ( isset( $query['league_id'] ) ? intval( $query['league_id'] ) : null );
        $final_key      = isset( $query['final'] ) ? sanitize_text_field( wp_unslash( strval( $query['final'] ) ) ) : null;
        $match_id       = isset( $query['edit'] ) ? intval( $query['edit'] ) : null;

        if ( ! $competition_id ) {
            throw new Competition_Not_Found_Exception( __( 'Competition not found', 'racketmanager' ) );
        }

        $competition = $this->competition_service->get_competition( $competition_id );
        if ( ! $competition ) {
            throw new Competition_Not_Found_Exception( __( 'Competition not found', 'racketmanager' ) );
        }

        if ( ! $league_id ) {
            throw new League_Not_Found_Exception( __( 'League not found', 'racketmanager' ) );
        }

        $league = $this->league_repository->find_by_id( (int) $league_id );
        if ( ! $league instanceof League ) {
            throw new League_Not_Found_Exception( __( 'League not found', 'racketmanager' ) );
        }

        if ( ! $match_id ) {
            throw new Fixture_Not_Found_Exception( __( 'Match not found', 'racketmanager' ) );
        }

        $match = $this->fixture_repository->find_by_id( (int) $match_id );
        if ( ! $match ) {
            throw new Fixture_Not_Found_Exception( __( 'Match not found', 'racketmanager' ) );
        }

        $is_finals       = true;
        $single_cup_game = true;
        $bulk            = false;
        $mode            = 'edit';
        $edit            = true;
        $form_title      = __( 'Edit Match', 'racketmanager' );
        $submit_title    = $form_title;
        $matches         = array( $match );
        $match_day       = $match->match_day ?? null;
        $max_matches     = 1;

        $final       = $league->championship?->get_finals( $final_key ?? false );
        $final_teams = $league->championship?->get_final_teams( $final['key'] ?? '' ) ?? array();

        if ( is_numeric( $match->home_team ?? null ) ) {
            $home_team  = $this->team_repository->find_by_id( (int) $match->home_team );
            $home_title = (string) ( $home_team?->title ?? '' );
        } else {
            $home_team  = $final_teams[ $match->home_team ] ?? null;
            $home_title = (string) ( $home_team?->title ?? '' );
        }

        if ( is_numeric( $match->away_team ?? null ) ) {
            $away_team  = $this->team_repository->find_by_id( (int) $match->away_team );
            $away_title = (string) ( $away_team?->title ?? '' );
        } else {
            $away_team  = $final_teams[ $match->away_team ] ?? null;
            $away_title = (string) ( $away_team?->title ?? '' );
        }

        $vm = new Tournament_Fixtures_Page_View_Model(
            league: $league,
            tournament: $competition,
            competition: $competition,
            season: (string) $season,
            form_title: $form_title,
            submit_title: $submit_title,
            fixtures: $matches,
            edit: $edit,
            bulk: $bulk,
            is_finals: $is_finals,
            mode: $mode,
            teams: is_array( $final_teams ) ? $final_teams : array(),
            single_cup_game: $single_cup_game,
            max_fixtures: $max_matches,
            final_key: (string) ( $final_key ?? '' ),
            home_title: $home_title,
            away_title: $away_title,
            match_day: $match_day ? (int) $match_day : null,
        );

        return array(
            'view_model' => $vm,
        );
    }
}
