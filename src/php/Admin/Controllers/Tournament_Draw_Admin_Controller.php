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
use Racketmanager\Services\League_Service;

/**
 * Handles draw-page orchestration and tab selection.
 */
readonly final class Tournament_Draw_Admin_Controller {

    public function __construct(
        private Tournament_Service $tournament_service,
        private League_Service $league_service,
        private Draw_Action_Dispatcher $draw_action_dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @return bool True when the current request is a POST.
     */
    private function is_post_request(): bool {
        return 'POST' === strtoupper( strval( $_SERVER['REQUEST_METHOD'] ?? '' ) );
    }

    /**
     * Controller for admin.php?page=racketmanager-tournaments&view=draw
     *
     * @param array $query Typically $_GET
     * @param array $post  Typically $_POST
     * @return array{view_model:Tournament_Draw_Page_View_Model, redirect_tab:string, redirect?:string, message?:string, message_type?:bool|string}
     *
     * @throws Invalid_Status_Exception
     * @throws Tournament_Not_Found_Exception
     */
    public function draw_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_matches' );

        $tournament_id = isset( $query['tournament'] ) ? intval( $query['tournament'] ) : null;
        $league_id     = isset( $query['league'] ) ? intval( $query['league'] ) : null;

        $tournament = $this->tournament_service->get_tournament( $tournament_id );

        $league = $this->league_service->get_league( $league_id );
        if ( ! $league ) {
            throw new Invalid_Status_Exception( __( 'League not found', 'racketmanager' ) );
        }

        $response = $this->dispatch_action( $tournament_id, $league_id, $post );

        $vm = $this->build_view_model( $tournament, $league, $query, $post, $response->tab_override );

        $result = array(
            'view_model'   => $vm,
            // For PRG redirects: the tab the user should land on after POST.
            'redirect_tab' => $vm->tab,
        );

        if ( null !== $response->message ) {
            $result['message']      = $response->message;
            $result['message_type'] = Admin_Message_Mapper::to_legacy( $response->message_type );
        }

        if ( $this->is_post_request() ) {
            $result['redirect'] = Admin_Redirect_Url_Builder::tournament_draw_view(
                $query,
                $post,
                'draw',
                $tournament_id,
                $league_id,
                $result['redirect_tab']
            );
        }

        return $result;
    }

    private function dispatch_action( ?int $tournament_id, ?int $league_id, array $post ): object {
        $dto = new Draw_Action_Request_DTO(
            tournament_id: $tournament_id,
            league_id: $league_id,
            season: isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : null,
            post: $post
        );

        return $this->draw_action_dispatcher->handle( $dto );
    }

    private function build_view_model( object $tournament, object $league, array $query, array $post, ?string $tab_override ): Tournament_Draw_Page_View_Model {
        return new Tournament_Draw_Page_View_Model(
            tournament: $tournament,
            league: $league,
            tab: $this->extract_tab( $query, $tab_override ),
            season: $this->extract_season( $query, $post, $tournament->get_season() ),
        );
    }

    /**
     * @param array $query Typically $_GET
     * @param string|null $override Optional tab override.
     */
    private function extract_tab( array $query, ?string $override ): string {
        if ( ! empty( $override ) ) {
            return $override;
        }

        return isset( $query['league-tab'] ) ? sanitize_text_field( wp_unslash( $query['league-tab'] ) ) : 'finalResults';
    }

    /**
     * @param array $query  Typically $_GET
     * @param array $post   Typically $_POST
     * @param string|null $fallback Fallback season value.
     */
    private function extract_season( array $query, array $post, ?string $fallback ): string {
        if ( isset( $post['season'] ) ) {
            return sanitize_text_field( wp_unslash( strval( $post['season'] ) ) );
        }

        return strval( $query['season'] ?? $fallback );
    }
}
