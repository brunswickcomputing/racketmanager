<?php
/**
 * Tournament Information Admin Controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Tournament_Information_Page_View_Model;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Admin\Tournament\Tournament_Information_Action_Dispatcher;
use Racketmanager\Services\Tournament_Service;

readonly final class Tournament_Information_Admin_Controller {

    public function __construct(
        private Tournament_Service $tournament_service,
        private Tournament_Information_Action_Dispatcher $dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * Controller for admin.php?page=racketmanager-tournaments&view=information
     *
     * @param array $query Typically $_GET
     * @param array $post  Typically $_POST
     * @return array{view_model?:Tournament_Information_Page_View_Model, redirect?:string, message?:string, message_type?:bool|string}
     *
     * @throws Tournament_Not_Found_Exception
     */
    public function information_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_teams' );

        $tournament_id = $this->extract_tournament_id( $query, $post );

        if ( $this->is_post_request() ) {
            return $this->handle_post_request( $tournament_id, $query, $post );
        }

        return $this->handle_get_request( $tournament_id );
    }

    private function is_post_request(): bool {
        return 'POST' === strtoupper( strval( $_SERVER['REQUEST_METHOD'] ?? '' ) );
    }

    private function extract_tournament_id( array $query, array $post ): ?int {
        if ( isset( $query['tournament'] ) ) {
            return intval( $query['tournament'] );
        }

        if ( isset( $post['tournament_id'] ) ) {
            return intval( $post['tournament_id'] );
        }

        return null;
    }

    private function handle_post_request( ?int $tournament_id, array $query, array $post ): array {
        $action_result = $this->dispatcher->handle( $tournament_id, $post );

        $result = array(
            'redirect' => Admin_Redirect_Url_Builder::tournament_information_view( $query, $post, $tournament_id ),
        );

        if ( ! empty( $action_result->message ) ) {
            $result['message']      = $action_result->message;
            $result['message_type'] = Admin_Message_Mapper::to_legacy( $action_result->message_type );
        }

        return $result;
    }

    private function handle_get_request( ?int $tournament_id ): array {
        $tournament = $this->tournament_service->get_tournament( $tournament_id );

        $vm = new Tournament_Information_Page_View_Model(
            tournament: $tournament,
            errors: new Error_Bag(),
        );

        return array(
            'view_model' => $vm,
        );
    }
}
