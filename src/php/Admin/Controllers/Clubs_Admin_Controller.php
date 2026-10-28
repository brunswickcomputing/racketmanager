<?php
declare( strict_types=1 );
/**
 * Clubs Admin Controller
 *
 * Handles display and actions for clubs list.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Clubs_Page_View_Model;
use Racketmanager\Services\Admin\Club\Club_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;

final readonly class Clubs_Admin_Controller {

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
     * @return array{view_model?: Clubs_Page_View_Model, redirect?: string, message?: string, message_type?: bool|string}
     */
    public function clubs_page( array $query, array $post ): array {
        if ( isset( $post['doClubDel'] ) || isset( $post['doSchedulePlayerRatings'] ) ) {
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

        $clubs = $this->club_service->get_clubs();

        return array(
            'view_model' => new Clubs_Page_View_Model( $clubs ),
        );
    }
}
