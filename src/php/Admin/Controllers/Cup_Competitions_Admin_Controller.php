<?php
declare( strict_types=1 );
/**
 * Cup Competitions Admin Controller
 *
 * Handles display and actions for cup competitions list view.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Competitions_Page_View_Model;
use Racketmanager\Services\Admin\Index\Index_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Util\Util_Lookup;

final readonly class Cup_Competitions_Admin_Controller {

    public function __construct(
        private Competition_Service $competition_service,
        private Action_Guard_Interface $action_guard,
        private Index_Action_Dispatcher $index_action_dispatcher,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     *
     * @return array{view_model?: Competitions_Page_View_Model, redirect?: string, message?: string, message_type?: bool|string, flash_data?: array<string, mixed>}
     */
    public function competitions_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_leagues' );

        if ( isset( $post['doCompDel'] ) ) {
            $result = $this->index_action_dispatcher->handle( $query, $post );

            $redirect = Admin_Redirect_Url_Builder::cup_screen( $query );

            return array(
                'redirect'     => $redirect,
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            );
        }

        $competition_type = 'cup';
        $page_title       = ucfirst( $competition_type ) . ' ' . __( 'Competitions', 'racketmanager' );
        $age_group_select = isset( $query['age_group'] ) ? sanitize_text_field( wp_unslash( strval( $query['age_group'] ) ) ) : '';
        $competitions     = $this->competition_service->find_competitions_with_summary( $age_group_select, $competition_type );
        $age_groups       = Util_Lookup::get_age_groups();
        $page_name        = isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-cups';

        $vm = new Competitions_Page_View_Model(
            page_title: $page_title,
            competitions: $competitions,
            age_groups: $age_groups,
            age_group_select: $age_group_select,
            page_name: $page_name,
            tournament: null,
        );

        return array(
            'view_model' => $vm,
        );
    }
}
