<?php
declare( strict_types=1 );
/**
 * Club Teams Admin Controller
 *
 * Handles display and actions for club teams.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Club_Teams_Page_View_Model;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Services\Admin\Club\Club_Team_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Team_Service;

final readonly class Club_Teams_Admin_Controller {

    public function __construct(
        private Club_Service $club_service,
        private Team_Service $team_service,
        private Club_Team_Action_Dispatcher $club_team_action_dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     *
     * @return array{view_model?: Club_Teams_Page_View_Model, redirect?: string, message?: string, message_type?: bool|string}
     * @throws Club_Not_Found_Exception
     */
    public function club_teams_page( array $query, array $post ): array {
        if ( isset( $post['addTeam'] ) || isset( $post['editTeam'] ) || isset( $post['doTeamDel'] ) ) {
            $result = $this->club_team_action_dispatcher->handle( $query, $post );

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
        if ( ! $club_id ) {
            throw new Club_Not_Found_Exception( __( 'Club not found', 'racketmanager' ) );
        }

        $club = $this->club_service->get_club( $club_id );
        if ( ! $club ) {
            throw new Club_Not_Found_Exception( __( 'Club not found', 'racketmanager' ) );
        }

        $teams = $this->team_service->get_teams_for_club( $club_id );

        return array(
            'view_model' => new Club_Teams_Page_View_Model(
                club: $club,
                teams: $teams,
                club_id: $club_id,
            ),
        );
    }
}
