<?php
/**
 * Tournament Draw Admin Controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Tournament_Draw_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Request_DTO;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Championship\Draw_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Tournament_Service;

use function Racketmanager\get_league;

/**
 * Handles draw-page orchestration and tab selection.
 */
readonly final class Tournament_Draw_Admin_Controller {

    public function __construct(
        private Tournament_Service $tournament_service,
        private Draw_Action_Dispatcher $draw_action_dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * Controller for admin.php?page=racketmanager-tournaments&view=draw
     *
     * @param array $query Typically $_GET
     * @param array $post  Typically $_POST
     * @return array{view_model:Tournament_Draw_Page_View_Model, redirect_tab:string, message?:string, message_type?:bool|string}
     *
     * @throws Invalid_Status_Exception
     * @throws Tournament_Not_Found_Exception
     */
    public function draw_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_matches' );

        $tournament_id = isset( $query['tournament'] ) ? intval( $query['tournament'] ) : null;
        $league_id     = isset( $query['league'] ) ? intval( $query['league'] ) : null;
        $tab           = isset( $query['league-tab'] ) ? sanitize_text_field( wp_unslash( $query['league-tab'] ) ) : null;

        try {
            $tournament = $this->tournament_service->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }

        $league = get_league( $league_id );
        if ( ! $league ) {
            throw new Invalid_Status_Exception( __( 'League not found', 'racketmanager' ) );
        }

        // Default tab if none supplied
        if ( empty( $tab ) ) {
            $tab = 'finalResults';
        }

        $dto = new Draw_Action_Request_DTO(
            tournament_id: $tournament_id,
            league_id: $league_id,
            season: isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : null,
            post: $post
        );

        $response = $this->draw_action_dispatcher->handle( $dto );
        $tab = $response->tab_override ?: $tab;

        $vm = new Tournament_Draw_Page_View_Model(
            tournament: $tournament,
            league: $league,
            tab: $tab ?: 'finalResults',
            season: isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : ( $query['season'] ?? $tournament->get_season() ),
        );

        $result = array(
            'view_model' => $vm,
            // For PRG redirects: the tab the user should land on after POST.
            'redirect_tab' => $vm->tab ?: 'finalResults',
        );

        if ( null !== $response->message ) {
            $result['message'] = $response->message;
            $result['message_type'] = Admin_Message_Mapper::to_legacy( $response->message_type );
        }

        return $result;
    }
}
