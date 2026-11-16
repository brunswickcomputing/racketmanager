<?php
declare( strict_types=1 );
/**
 * Seasons Admin Controller
 *
 * Handles seasons administrative screen display and mutations.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Seasons_Page_View_Model;
use Racketmanager\Services\Admin\Season\Season_Action_Dispatcher;
use Racketmanager\Application\Admin\Ports\Action_Guard_Interface;
use Racketmanager\Services\Season_Service;

/**
 * Class Seasons_Admin_Controller
 *
 * Coordinates season listing and creation/deletion requests.
 */
final readonly class Seasons_Admin_Controller {

    /**
     * @param Season_Service           $season_service          Season domain query service.
     * @param Season_Action_Dispatcher $season_action_dispatcher Dispatcher for season mutations.
     * @param Action_Guard_Interface   $action_guard            Security guard service.
     */
    public function __construct(
        private Season_Service $season_service,
        private Season_Action_Dispatcher $season_action_dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * Handle seasons page display and mutations.
     *
     * @param array<string, mixed> $query      Current query arguments.
     * @param array<string, mixed> $post       Current POST payload.
     * @param array<string, mixed> $flash_data Restored flash payload.
     *
     * @return array{view_model?: Seasons_Page_View_Model, redirect?: string, message?: string|null, message_type?: bool|string|null, flash_data?: array<string, mixed>}
     */
    public function seasons_page( array $query, array $post, array $flash_data = array() ): array {
        if ( isset( $post['addSeason'] ) || isset( $post['doSeasonDel'] ) ) {
            $result = $this->season_action_dispatcher->handle( $query, $post );

            $extra_args = array();
            if ( null !== $result->tab_override && '' !== $result->tab_override ) {
                $extra_args['tab'] = $result->tab_override;
            }
            $extra_args = array_merge( $extra_args, $result->query_params );

            $redirect = Admin_Redirect_Url_Builder::season_screen( $query, $extra_args );

            return array(
                'redirect'     => $redirect,
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            );
        }

        $this->action_guard->assert_capability( 'edit_seasons' );

        $errors      = is_array( $flash_data['errors'] ?? null ) ? new Error_Bag( $flash_data['errors'] ) : new Error_Bag();
        $season_name = isset( $flash_data['season_name'] ) ? strval( $flash_data['season_name'] ) : null;
        $seasons     = $this->season_service->get_all_seasons();

        return array(
            'view_model' => new Seasons_Page_View_Model(
                seasons: $seasons,
                errors: $errors,
                season_name: $season_name,
            ),
        );
    }
}
