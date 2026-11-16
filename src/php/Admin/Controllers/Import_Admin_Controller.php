<?php
declare( strict_types=1 );
/**
 * Import Admin Controller
 *
 * Handles import administrative screen display and file upload mutations.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Import_Page_View_Model;
use Racketmanager\Application\Admin\Ports\Import_Action_Dispatcher_Interface;
use Racketmanager\Application\Admin\Ports\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;

/**
 * Class Import_Admin_Controller
 *
 * Coordinates dataset import forms and CSV import processing.
 */
final readonly class Import_Admin_Controller {

    /**
     * @param Club_Service                       $club_service              Clubs query service.
     * @param Competition_Service                $competition_service       Competitions query service.
     * @param Import_Action_Dispatcher_Interface $import_action_dispatcher Dispatcher for import mutations.
     * @param Action_Guard_Interface             $action_guard              Security guard service.
     */
    public function __construct(
        private Club_Service $club_service,
        private Competition_Service $competition_service,
        private Import_Action_Dispatcher_Interface $import_action_dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * Handle import page display and mutations.
     *
     * @param array<string, mixed>      $query      Current query arguments.
     * @param array<string, mixed>      $post       Current POST payload.
     * @param array<string, mixed>|null $files      Uploaded files payload.
     * @param array<string, mixed>      $flash_data Restored flash payload.
     *
     * @return array{view_model?: Import_Page_View_Model, redirect?: string, message?: string|null, message_type?: bool|string|null, flash_data?: array<string, mixed>}
     */
    public function import_page( array $query, array $post, ?array $files = null, array $flash_data = array() ): array {
        if ( isset( $post['import'] ) ) {
            $result = $this->import_action_dispatcher->handle( $query, $post, $files );

            $extra_args = array();
            if ( null !== $result->tab_override && '' !== $result->tab_override ) {
                $extra_args['tab'] = $result->tab_override;
            }
            $extra_args = array_merge( $extra_args, $result->query_params );

            $redirect = Admin_Redirect_Url_Builder::import_screen( $query, $extra_args );

            return array(
                'redirect'     => $redirect,
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            );
        }

        $this->action_guard->assert_capability( 'import_leagues' );

        $errors = is_array( $flash_data['errors'] ?? null ) ? new Error_Bag( $flash_data['errors'] ) : new Error_Bag();

        $clubs        = $this->club_service->get_clubs(
            array(
                'type' => 'affiliated',
            )
        );
        $competitions = $this->competition_service->get_all();

        return array(
            'view_model' => new Import_Page_View_Model(
                clubs: $clubs,
                competitions: $competitions,
                errors: $errors,
            ),
        );
    }
}
