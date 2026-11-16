<?php
declare( strict_types=1 );
/**
 * Index Admin Controller
 *
 * Handles competitions index administrative screen display and mutations.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Index_Page_View_Model;
use Racketmanager\Application\Admin\Ports\Index_Action_Dispatcher_Interface;
use Racketmanager\Application\Admin\Ports\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Util\Util_Lookup;

/**
 * Class Index_Admin_Controller
 *
 * Coordinates competition listing and creation/deletion requests.
 */
final readonly class Index_Admin_Controller {

    /**
     * @param Competition_Service               $competition_service     Competition query service.
     * @param Index_Action_Dispatcher_Interface $index_action_dispatcher Dispatcher for competition mutations.
     * @param Action_Guard_Interface            $action_guard            Security guard service.
     */
    public function __construct(
        private Competition_Service $competition_service,
        private Index_Action_Dispatcher_Interface $index_action_dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * Handle index page display and mutations.
     *
     * @param array<string, mixed> $query      Current query arguments.
     * @param array<string, mixed> $post       Current POST payload.
     * @param array<string, mixed> $flash_data Restored flash payload.
     *
     * @return array{view_model?: Index_Page_View_Model, redirect?: string, message?: string|null, message_type?: bool|string|null, flash_data?: array<string, mixed>}
     */
    public function index_page( array $query, array $post, array $flash_data = array() ): array {
        if ( isset( $post['addCompetition'] ) || isset( $post['doCompDel'] ) ) {
            $result = $this->index_action_dispatcher->handle( $query, $post );

            $extra_args = array();
            if ( null !== $result->tab_override && '' !== $result->tab_override ) {
                $extra_args['tab'] = $result->tab_override;
            }
            $extra_args = array_merge( $extra_args, $result->query_params );

            $redirect = Admin_Redirect_Url_Builder::index_screen( $query, $extra_args );

            return array(
                'redirect'     => $redirect,
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            );
        }

        $this->action_guard->assert_capability( 'view_leagues' );

        $errors           = is_array( $flash_data['errors'] ?? null ) ? new Error_Bag( $flash_data['errors'] ) : new Error_Bag();
        $competition_name = isset( $flash_data['competition_name'] ) ? strval( $flash_data['competition_name'] ) : null;
        $type             = isset( $flash_data['type'] ) ? strval( $flash_data['type'] ) : null;
        $age_group        = isset( $flash_data['age_group'] ) ? strval( $flash_data['age_group'] ) : null;

        $age_group_select = isset( $query['age_group'] ) ? sanitize_text_field( wp_unslash( strval( $query['age_group'] ) ) ) : '';
        $competitions     = $this->competition_service->find_competitions_with_summary( $age_group_select, null );
        $age_groups       = Util_Lookup::get_age_groups();
        $page_name        = isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager';

        return array(
            'view_model' => new Index_Page_View_Model(
                competitions: $competitions,
                age_groups: $age_groups,
                age_group_select: $age_group_select,
                page_name: $page_name,
                errors: $errors,
                competition_name: $competition_name,
                type: $type,
                age_group: $age_group,
            ),
        );
    }
}
