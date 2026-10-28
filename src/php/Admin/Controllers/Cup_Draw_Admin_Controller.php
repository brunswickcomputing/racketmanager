<?php
declare( strict_types=1 );
/**
 * Cup Draw Admin Controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Championship_Finals_Presenter;
use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Cup_Draw_Page_View_Model;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Request_DTO;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Response_DTO;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Admin\Championship\Draw_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;

final readonly class Cup_Draw_Admin_Controller {

    public function __construct(
        private Competition_Service $competition_service,
        private League_Repository_Interface $league_repository,
        private League_Query_Service $league_query_service,
        private Fixture_Detail_Service $fixture_detail_service,
        private Admin_Championship_Finals_Presenter $finals_presenter,
        private Draw_Action_Dispatcher $draw_action_dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     *
     * @return array{view_model?:Cup_Draw_Page_View_Model, redirect?:string, message?:string, message_type?:bool|string}
     *
     * @throws Invalid_Status_Exception
     * @throws Competition_Not_Found_Exception
     * @throws League_Not_Found_Exception
     */
    public function draw_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_matches' );

        $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
        $is_post        = 'POST' === strtoupper( $request_method );

        if ( $is_post ) {
            $league_id = isset( $query['league'] ) ? intval( $query['league'] ) : ( isset( $post['league_id'] ) ? intval( $post['league_id'] ) : ( isset( $query['league_id'] ) ? intval( $query['league_id'] ) : null ) );
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
        $tab            = isset( $query['tab'] ) ? sanitize_text_field( wp_unslash( strval( $query['tab'] ) ) ) : 'finalResults';

        if ( ! $competition_id ) {
            throw new Competition_Not_Found_Exception( __( 'Competition not found', 'racketmanager' ) );
        }

        $competition = $this->competition_service->get_by_id( $competition_id );
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

        if ( ! empty( $league->championship?->groups ) && count( $league->championship->groups ) > 0 ) {
            $league->set_group( $league->championship->groups[0] );
        }

        $finals = $this->finals_presenter->present_league_finals(
            $league,
            (string) $season,
            $this->league_query_service,
            $this->fixture_detail_service
        );

        $vm = new Cup_Draw_Page_View_Model(
            competition: $competition,
            league: $league,
            season: (string) $season,
            tab: $tab,
            finals: $finals,
        );

        return array(
            'view_model' => $vm,
        );
    }
}
