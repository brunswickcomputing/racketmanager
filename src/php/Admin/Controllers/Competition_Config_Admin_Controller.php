<?php
declare( strict_types=1 );
/**
 * Competition Config Admin Controller
 *
 * Handles display and actions for competition configuration.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Competition_Page_Presenter;
use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Tournament_Competition_Config_Page_View_Model;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Services\Admin\Competition\Competition_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Tournament_Service;

final readonly class Competition_Config_Admin_Controller {

    public function __construct(
        private Competition_Service $competition_service,
        private Tournament_Service $tournament_service,
        private Competition_Action_Dispatcher $competition_action_dispatcher,
        private Action_Guard_Interface $action_guard,
        private Admin_Competition_Page_Presenter $pages,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $flash_data
     *
     * @return array{view_model?: Tournament_Competition_Config_Page_View_Model, redirect?: string, message?: string, message_type?: bool|string, flash_data?: array<string, mixed>}
     */
    public function config_page( array $query, array $post, array $flash_data = array() ): array {
        $this->action_guard->assert_capability( 'edit_leagues' );

        if ( isset( $post['updateCompetitionConfig'] ) || isset( $post['doActionEvent'] ) ) {
            $result = $this->competition_action_dispatcher->handle( $query, $post );

            $extra_args = array();
            if ( null !== $result->tab_override && '' !== $result->tab_override ) {
                $extra_args['tab'] = $result->tab_override;
            }
            $extra_args = array_merge( $extra_args, $result->query_params );

            $redirect = Admin_Redirect_Url_Builder::competition_screen( $query, $extra_args );

            return array(
                'redirect'     => $redirect,
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            );
        }

        $competition_id = isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : null;
        if ( ! $competition_id ) {
            throw new Competition_Not_Found_Exception( __( 'Competition not found', 'racketmanager' ) );
        }

        $competition = $this->competition_service->get_by_id( $competition_id );
        if ( ! $competition ) {
            throw new Competition_Not_Found_Exception( __( 'Competition not found', 'racketmanager' ) );
        }

        $tournament    = null;
        $tournament_id = isset( $query['tournament'] ) ? intval( $query['tournament'] ) : null;
        if ( $tournament_id ) {
            $tournament = $this->tournament_service->get_tournament( $tournament_id );
        }

        $tab = isset( $query['tab'] ) ? sanitize_text_field( wp_unslash( strval( $query['tab'] ) ) ) : 'general';

        $vm = $this->pages->config( $competition, $tournament, $tab, $flash_data );

        return array(
            'view_model' => $vm,
        );
    }
}
