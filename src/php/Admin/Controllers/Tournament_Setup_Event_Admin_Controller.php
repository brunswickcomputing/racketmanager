<?php
/**
 * Tournament Setup-Event Admin Controller
 *
 * Handles admin.php?page=racketmanager-tournaments&view=setup-event
 * using the setup view model + PRG.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Tournament_Setup_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Request_DTO;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Championship\Draw_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Tournament_Service;

readonly final class Tournament_Setup_Event_Admin_Controller {

    public function __construct(
        private Tournament_Service $tournament_service,
        private League_Service $league_service,
        private Draw_Action_Dispatcher $draw_action_dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array $query Typically $_GET
     * @param array $post  Typically $_POST
     * @return array{view_model?:Tournament_Setup_Page_View_Model, redirect?:string, message?:string, message_type?:bool|string}
     *
     * @throws Invalid_Status_Exception
     * @throws Tournament_Not_Found_Exception
     */
    public function setup_event_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_matches' );

        $tournament_id = isset( $query['tournament'] ) ? intval( $query['tournament'] ) : null;
        $league_id     = isset( $query['league'] ) ? intval( $query['league'] ) : ( isset( $post['league_id'] ) ? intval( $post['league_id'] ) : null );

        if ( $this->is_post_request() ) {
            return $this->handle_post_request( $tournament_id, $league_id, $query, $post );
        }

        return $this->handle_get_request( $tournament_id, $league_id, $query );
    }

    private function is_post_request(): bool {
        return 'POST' === strtoupper( strval( $_SERVER['REQUEST_METHOD'] ?? '' ) );
    }

    /**
     * @param int|null $tournament_id
     * @param int|null $league_id
     * @param array $query
     * @param array $post
     * @return array{redirect:string, message?:string, message_type?:bool|string}
     */
    private function handle_post_request( ?int $tournament_id, ?int $league_id, array $query, array $post ): array {
        // Security: setup template posts with this nonce.
        $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_add_championship-fixtures', 'edit_matches' );

        $dto = new Draw_Action_Request_DTO(
            tournament_id: $tournament_id ?? 0,
            league_id: $league_id ?? 0,
            season: isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : null,
            post: $post
        );

        $response = $this->draw_action_dispatcher->handle( $dto );

        $redirect_url = Admin_Redirect_Url_Builder::tournament_draw_view(
            $query,
            $post,
            'setup-event',
            $tournament_id,
            $league_id,
            // setup-event uses the same tab pattern as draw for context; default is fine.
            isset( $query['tab'] ) ? sanitize_text_field( wp_unslash( strval( $query['tab'] ) ) ) : 'finalResults'
        );

        $result = array(
            'redirect' => $redirect_url,
        );

        if ( null !== $response->message ) {
            $result['message']      = $response->message;
            $result['message_type'] = Admin_Message_Mapper::to_legacy( $response->message_type );
        }

        return $result;
    }

    /**
     * @param int|null $tournament_id
     * @param int|null $league_id
     * @param array $query
     * @return array{view_model:Tournament_Setup_Page_View_Model}
     *
     * @throws Invalid_Status_Exception
     * @throws Tournament_Not_Found_Exception
     */
    private function handle_get_request( ?int $tournament_id, ?int $league_id, array $query ): array {
        $tournament = $this->tournament_service->get_tournament( $tournament_id );
        $season     = isset( $query['season'] ) ? sanitize_text_field( wp_unslash( strval( $query['season'] ) ) ) : $tournament->get_season();

        $league = $this->league_service->get_league( $league_id );
        if ( ! $league ) {
            throw new Invalid_Status_Exception( __( 'League not found', 'racketmanager' ) );
        }

        $match_count = $league->get_matches(
            array(
                'count' => true,
                'final' => 'all',
            )
        );

        $event_dtls       = $league->event->get_season_by_name( $season );
        $competition_dtls = $league->event->competition->get_season_by_name( $season );
        $match_dates      = empty( $event_dtls['match_dates'] )
            ? ( $competition_dtls['match_dates'] ?? array() )
            : $event_dtls['match_dates'];

        if ( empty( $match_dates ) ) {
            $match_dates = $this->tournament_service->calculate_default_match_dates( $tournament, $league->event->competition );
        }

        $vm = new Tournament_Setup_Page_View_Model(
            tournament: $tournament,
            season: strval( $season ),
            match_dates: is_array( $match_dates ) ? $match_dates : array(),
            match_count: is_numeric( $match_count ) ? intval( $match_count ) : null,
            league: $league,
            errors: new Error_Bag(),
            validator: null
        );

        return array(
            'view_model' => $vm,
        );
    }
}
