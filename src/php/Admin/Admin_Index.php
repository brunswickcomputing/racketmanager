<?php
/**
 * RacketManager-Admin API: Admin_Index class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Index
 */

declare( strict_types=1 );

namespace Racketmanager\Admin;

use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Index_Page_View_Model;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Services\Admin\Index\Index_Action_Dispatcher_Interface;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Util\Util_Lookup;

/**
 * RacketManager Index Admin functions
 * Class to implement RacketManager Admin Index
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Index
 */
class Admin_Index extends Admin_Display {

    private Admin_Action_Adapter $actions;
    private Admin_Screen_Presenter $screens;
    private ?Index_Action_Dispatcher_Interface $index_action_dispatcher;

    public function __construct(
        $plugin_instance,
        ?Admin_Action_Adapter $actions = null,
        ?Admin_Screen_Presenter $screens = null,
        ?Index_Action_Dispatcher_Interface $dispatcher = null,
    ) {
        parent::__construct( $plugin_instance );
        $c                             = $this->racketmanager->container;
        $this->actions                 = $actions ?? $c->get( 'admin_action_adapter' );
        $this->screens                 = $screens ?? $c->get( 'admin_screen_presenter' );
        $this->index_action_dispatcher = $dispatcher;
        $this->admin_action_adapter    = $this->actions;
    }

    /**
     * Show RacketManager index page
     */
    public function display_index_page(): void {
        $validator = new Validator();
        $validator = $validator->capability( 'view_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }

        if ( isset( $_POST['addCompetition'] ) || isset( $_POST['doCompDel'] ) ) {
            $dispatcher = $this->index_action_dispatcher ?? $this->racketmanager->container->get( 'index_action_dispatcher' );
            $result     = $dispatcher->handle( $_GET, $_POST );
            if ( $this->deliver_index_action_result( $result, $_GET ) ) {
                return;
            }
        }

        $flash            = $this->screens->apply_flash();
        $flash_data       = $flash['flash_data'] ?? array();
        $errors           = is_array( $flash_data['errors'] ?? null ) ? new Error_Bag( $flash_data['errors'] ) : new Error_Bag();
        $competition_name = isset( $flash_data['competition_name'] ) ? strval( $flash_data['competition_name'] ) : null;
        $type             = isset( $flash_data['type'] ) ? strval( $flash_data['type'] ) : null;
        $age_group        = isset( $flash_data['age_group'] ) ? strval( $flash_data['age_group'] ) : null;

        $age_group_select = isset( $_GET['age_group'] ) ? sanitize_text_field( wp_unslash( strval( $_GET['age_group'] ) ) ) : '';
        $competitions     = $this->competition_service->find_competitions_with_summary( $age_group_select, null );
        $age_groups       = Util_Lookup::get_age_groups();
        $page_name        = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( strval( $_GET['page'] ) ) ) : 'racketmanager';

        $vm = new Index_Page_View_Model(
            competitions: $competitions,
            age_groups: $age_groups,
            age_group_select: $age_group_select,
            page_name: $page_name,
            errors: $errors,
            competition_name: $competition_name,
            type: $type,
            age_group: $age_group,
        );

        $this->screens->present(
            array( 'view_model' => $vm ),
            Index_Page_View_Model::class,
            'admin/index'
        );
    }
}
