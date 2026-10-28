<?php
declare( strict_types=1 );
/**
 * Competition Season Modify Admin Controller
 *
 * Handles display and actions for adding and modifying competition seasons.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Competition_Page_Presenter;
use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Season_Modify_Page_View_Model;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Services\Admin\Competition\Competition_Season_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;

final readonly class Competition_Season_Modify_Admin_Controller {

    public function __construct(
        private Competition_Service $competition_service,
        private Competition_Season_Action_Dispatcher $competition_season_action_dispatcher,
        private Action_Guard_Interface $action_guard,
        private Admin_Competition_Page_Presenter $pages,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $flash_data
     *
     * @return array{view_model?: Season_Modify_Page_View_Model, redirect?: string, message?: string, message_type?: bool|string, flash_data?: array<string, mixed>}
     */
    public function season_modify_page( array $query, array $post, array $flash_data = array() ): array {
        $this->action_guard->assert_capability( 'edit_seasons' );

        if ( isset( $post['addSeason'] ) || isset( $post['editSeason'] ) ) {
            $result = $this->competition_season_action_dispatcher->handle( $query, $post );

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

        $season    = isset( $query['season'] ) ? sanitize_text_field( wp_unslash( strval( $query['season'] ) ) ) : null;
        $edit_mode = ( null !== $season && '' !== $season );

        if ( $edit_mode ) {
            $seasons = $competition->get_seasons();
            if ( empty( $seasons ) ) {
                throw new Season_Not_Found_Exception( __( 'No seasons found', 'racketmanager' ) );
            }
            if ( empty( $seasons[ $season ] ) && ! $competition->get_season_by_name( $season ) ) {
                throw new Season_Not_Found_Exception( __( 'Season not found', 'racketmanager' ) );
            }
            if ( ! $competition->get_season_by_name( $season ) ) {
                throw new Season_Not_Found_Exception( __( 'Season not found for competition', 'racketmanager' ) );
            }
        }

        $vm = $this->pages->season( $competition, $season, $edit_mode, $flash_data );

        $result = array(
            'view_model' => $vm,
        );

        if ( ! $edit_mode ) {
            $result['message']      = __( 'New season', 'racketmanager' );
            $result['message_type'] = 'info';
        }

        return $result;
    }
}
