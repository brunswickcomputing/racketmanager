<?php
/**
 * Tournament Information Admin Controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Tournament_Information_Page_View_Model;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Tournament\Tournament_Information_Action_Dispatcher;
use Racketmanager\Services\Tournament_Service;
use stdClass;

readonly final class Tournament_Information_Admin_Controller {

    public function __construct(
        private Tournament_Service $tournament_service,
        private Tournament_Information_Action_Dispatcher $dispatcher,
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
        if ( isset( $post['tournament_id'] ) ) {
            $tournament_id = isset( $query['tournament_id'] ) ? intval( $query['tournament_id'] ) : ( intval( $post['tournament_id'] ) );
        } else {
            $tournament_id = isset( $query['tournament_id'] ) ? intval( $query['tournament_id'] ) : ( null );
        }
        $is_post       = ( 'POST' === strtoupper( strval( $_SERVER['REQUEST_METHOD'] ?? '' ) ) );

        // POST: dispatch action, then PRG redirect back to GET.
        if ( $is_post ) {
            $action_result = $this->dispatcher->handle( $tournament_id, $post );

            $redirect_url = add_query_arg(
                array(
                    'page'          => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-tournaments',
                    'view'          => 'information',
                    'tournament_id' => $tournament_id,
                ),
                admin_url( 'admin.php' )
            );

            $result = array(
                'redirect' => $redirect_url,
            );

            if ( ! empty( $action_result->message ) ) {
                $result['message'] = $action_result->message;
                $result['message_type'] = Admin_Message_Mapper::to_legacy( $action_result->message_type );
            }

            return $result;
        }

        // GET: render.
        $tournament = $this->tournament_service->get_tournament( $tournament_id );

        // Template expects $validator->err_flds / $validator->err_msgs.
        $validator = new stdClass();
        $validator->err_flds = array();
        $validator->err_msgs = array();

        $vm = new Tournament_Information_Page_View_Model(
            tournament: $tournament,
            validator: $validator,
        );

        return array(
            'view_model' => $vm,
        );
    }
}
