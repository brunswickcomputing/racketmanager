<?php
declare( strict_types=1 );
/**
 * League Setup Admin Controller
 *
 * Handles display and actions for league competition setup view.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Setup_Page_View_Model;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Services\Admin\League\League_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;

final readonly class League_Setup_Admin_Controller {

    public function __construct(
        private Competition_Service $competition_service,
        private Action_Guard_Interface $action_guard,
        private League_Setup_Action_Dispatcher $league_setup_dispatcher,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     *
     * @return array{view_model?: Setup_Page_View_Model, redirect?: string, message?: string, message_type?: bool|string, flash_data?: array<string, mixed>}
     *
     * @throws Competition_Not_Found_Exception
     * @throws Season_Not_Found_Exception
     */
    public function setup_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_matches' );

        if ( ( isset( $post['action'] ) || isset( $post['rank'] ) ) ) {
            $result = $this->league_setup_dispatcher->handle( $query, $post );

            $extra_args = array();
            if ( null !== $result->tab_override && '' !== $result->tab_override ) {
                $extra_args['tab'] = $result->tab_override;
            }
            $extra_args = array_merge( $extra_args, $result->query_params );

            $redirect = Admin_Redirect_Url_Builder::league_screen( $query, $extra_args );

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

        $competition = $this->competition_service->get_competition( $competition_id );
        if ( ! $competition ) {
            throw new Competition_Not_Found_Exception( __( 'Competition not found', 'racketmanager' ) );
        }

        $season = isset( $query['season'] ) ? intval( $query['season'] ) : null;
        if ( ! $season ) {
            throw new Season_Not_Found_Exception( __( 'Season is required', 'racketmanager' ) );
        }

        $current_season = $competition->get_season_by_name( (string) $season );
        if ( ! $current_season ) {
            throw new Season_Not_Found_Exception( __( 'Season not found', 'racketmanager' ) );
        }

        $vm = new Setup_Page_View_Model(
            competition: $competition,
            season: (string) $season,
            current_season: $current_season,
            event: null,
            league: null,
            match_count: 0,
        );

        return array(
            'view_model' => $vm,
        );
    }
}
