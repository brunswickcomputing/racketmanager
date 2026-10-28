<?php
declare( strict_types=1 );
/**
 * Club Admin Controller
 *
 * Handles display and actions for adding and editing a club.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Club_Page_View_Model;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Services\Admin\Club\Club_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;

final readonly class Club_Admin_Controller {

    public function __construct(
        private Club_Service $club_service,
        private Club_Action_Dispatcher $club_action_dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     *
     * @return array{view_model?: Club_Page_View_Model, redirect?: string, message?: string, message_type?: bool|string}
     */
    public function club_page( array $query, array $post ): array {
        if ( isset( $post['addClub'] ) || isset( $post['editClub'] ) ) {
            $result = $this->club_action_dispatcher->handle( $query, $post );

            $extra_args = array();
            if ( null !== $result->tab_override && '' !== $result->tab_override ) {
                $extra_args['tab'] = $result->tab_override;
            }
            $extra_args = array_merge( $extra_args, $result->query_params );

            $redirect = Admin_Redirect_Url_Builder::club_screen( $query, $extra_args );

            return array(
                'redirect'     => $redirect,
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
            );
        }

        $this->action_guard->assert_capability( 'edit_teams' );

        $club_id = isset( $query['club_id'] ) ? intval( $query['club_id'] ) : null;
        if ( $club_id ) {
            $club = $this->club_service->get_club( $club_id );
            if ( ! $club ) {
                throw new Club_Not_Found_Exception( __( 'Club not found', 'racketmanager' ) );
            }
            $edit        = true;
            $form_title  = __( 'Edit Club', 'racketmanager' );
            $form_action = __( 'Update', 'racketmanager' );
        } else {
            $club        = null;
            $edit        = false;
            $form_title  = __( 'Add Club', 'racketmanager' );
            $form_action = __( 'Add', 'racketmanager' );
        }

        return array(
            'view_model' => new Club_Page_View_Model( $club, $edit, $form_title, $form_action, $club_id ),
        );
    }
}
