<?php
declare( strict_types=1 );
/**
 * League Competitions Admin Controller
 *
 * Handles display and actions for league competitions list view.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Competitions_Page_View_Model;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Application\Admin\Ports\Index_Action_Dispatcher_Interface;
use Racketmanager\Application\Admin\Ports\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Util\Util_Lookup;

/**
 * Controller for the league competitions administration screen.
 *
 * Coordinates permission checks, form-action dispatching, redirects, flash
 * messages, and construction of the competitions page view model for league
 * competition management.
 *
 * @package RacketManager
 * @subpackage Admin\Controllers
 */
final readonly class League_Competitions_Admin_Controller {

    /**
     * Initialises the service with competition handling, action guarding, and index action dispatching dependencies.
     *
     * @param Competition_Service               $competition_service Service used to manage competition-related operations.
     * @param Action_Guard_Interface            $action_guard Guard used to validate or restrict actions.
     * @param Index_Action_Dispatcher_Interface $index_action_dispatcher Dispatcher used to handle index actions.
     *
     * @return void
     */
    public function __construct(
        private Competition_Service $competition_service,
        private Action_Guard_Interface $action_guard,
        private Index_Action_Dispatcher_Interface $index_action_dispatcher,
    ) {
    }

    /**
     * Builds the competitions administration page response, handling competition add or delete actions when submitted.
     *
     * @param array $query Query parameters used to determine filtering, page context, and redirect destinations.
     * @param array $post Submitted form data used to process competition actions.
     * @param array $flash_data Optional flash data used to restore validation errors and submitted field values.
     *
     * @return array Response data containing either redirect details after an action or a competitions page view model.
     */
    public function competitions_page( array $query, array $post, array $flash_data = array() ): array {
        $this->action_guard->assert_capability( 'edit_leagues' );

        if ( isset( $post['addCompetition'] ) || isset( $post['doCompDel'] ) ) {
            if ( ! isset( $post['type'] ) || '' === $post['type'] ) {
                $post['type'] = 'league';
            }
            $result = $this->index_action_dispatcher->handle( $query, $post );

            $redirect = Admin_Redirect_Url_Builder::league_screen( $query );

            return array(
                'redirect'     => $redirect,
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            );
        }

        $competition_type = 'league';
        $page_title       = ucfirst( $competition_type ) . ' ' . __( 'Competitions', 'racketmanager' );
        $age_group_select = isset( $query['age_group'] ) ? sanitize_text_field( wp_unslash( strval( $query['age_group'] ) ) ) : '';
        $competitions     = $this->competition_service->find_competitions_with_summary( $age_group_select, $competition_type );
        $age_groups       = Util_Lookup::get_age_groups();
        $page_name        = isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-leagues';

        $errors             = is_array( $flash_data['errors'] ?? null ) ? new Error_Bag( $flash_data['errors'] ) : new Error_Bag();
        $competition_name   = isset( $flash_data['competition_name'] ) ? strval( $flash_data['competition_name'] ) : null;
        $selected_age_group = isset( $flash_data['age_group'] ) ? strval( $flash_data['age_group'] ) : null;
        $action_url         = Admin_Redirect_Url_Builder::league_screen( array() );

        $vm = new Competitions_Page_View_Model(
            page_title: $page_title,
            competitions: $competitions,
            age_groups: $age_groups,
            age_group_select: $age_group_select,
            page_name: $page_name,
            tournament: null,
            competition_type: $competition_type,
            errors: $errors,
            competition_name: $competition_name,
            selected_age_group: $selected_age_group,
            action_url: $action_url,
        );

        return array(
            'view_model' => $vm,
        );
    }
}
