<?php
/**
 * Tournament Matches Admin Controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Tournament_Matches_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Request_DTO;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Championship\Draw_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Admin\Tournament\Tournament_Matches_Admin_Service;

readonly class Tournament_Matches_Admin_Controller {

    public function __construct(
        private Tournament_Matches_Admin_Service $matches_admin_service,
        private Draw_Action_Dispatcher $draw_action_dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * Controller for admin.php?page=racketmanager-tournaments&view=matches or &view=match
     *
     * @param array $query Typically $_GET
     * @param array $post  Typically $_POST
     * @return array{view_model?:Tournament_Matches_Page_View_Model, redirect?:string, message?:string, message_type?:bool|string}
     *
     * @throws Tournament_Not_Found_Exception
     */
    public function matches_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_matches' );

        $is_post = ( 'POST' === strtoupper( strval( $_SERVER['REQUEST_METHOD'] ?? '' ) ) );

        //phpcs:disable WordPress.Security.NonceVerification.Recommended
        $tournament_id = $this->extract_tournament_id( $query );
        $league_id     = $this->extract_league_id( $query, $post );
        $final_key     = $this->extract_final_key( $query, $post );
        $fixture_id      = $this->extract_fixture_id( $query, $post );
        $view          = $this->extract_view( $query );
        //phpcs:enable WordPress.Security.NonceVerification.Recommended

        // POST: manage matches (updateLeague=match) -> PRG redirect back to GET.
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

            if ( 'match' === $view ) {
                $redirect_url = Admin_Redirect_Url_Builder::tournament_match( $query, $post, $tournament_id, $league_id, $final_key, $fixture_id );
            } else {
                $redirect_url = Admin_Redirect_Url_Builder::tournament_matches( $query, $post, $tournament_id, $league_id, $final_key );
            }

            $result = array(
                'redirect' => $redirect_url,
            );

            if ( null !== $response->message ) {
                $result['message'] = $response->message;
                $result['message_type'] = Admin_Message_Mapper::to_legacy( $response->message_type );
            }

            return $result;
        }

        // GET: render page
        $vm = $this->matches_admin_service->prepare_matches_view_model( $tournament_id, $league_id, $final_key, $fixture_id, $view );

        return array(
            'view_model' => $vm,
        );
    }

    private function extract_tournament_id( array $query ): ?int {
        return isset( $query['tournament'] ) ? intval( $query['tournament'] ) : null;
    }

    private function extract_league_id( array $query, array $post ): ?int {
        $league_id = null;

        if ( isset( $query['league_id'] ) ) {
            $league_id = intval( $query['league_id'] );
        } elseif ( isset( $query['league'] ) ) {
            $league_id = intval( $query['league'] );
        } elseif ( isset( $post['league_id'] ) ) {
            $league_id = intval( $post['league_id'] );
        }

        return $league_id;
    }

    private function extract_final_key( array $query, array $post ): ?string {
        if ( isset( $query['final'] ) ) {
            return sanitize_text_field( wp_unslash( $query['final'] ) );
        }

        if ( isset( $post['final'] ) ) {
            return sanitize_text_field( wp_unslash( strval( $post['final'] ) ) );
        }

        return null;
    }

    private function extract_fixture_id( array $query, array $post ): ?int {
        if ( isset( $query['edit'] ) ) {
            return intval( $query['edit'] );
        }

        if ( isset( $post['match'][0] ) ) {
            return intval( $post['match'][0] );
        }

        return null;
    }

    private function extract_view( array $query ): string {
        return isset( $query['view'] ) ? sanitize_text_field( wp_unslash( $query['view'] ) ) : 'matches';
    }
}
