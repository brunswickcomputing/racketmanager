<?php
/**
 * RacketManager-Admin API: RacketManager-import class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Import
 */

declare( strict_types=1 );

namespace Racketmanager\Admin;

use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Import_Page_View_Model;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Services\Admin\Import\Import_Action_Dispatcher_Interface;
use Racketmanager\Services\Validator\Validator;

/**
 * RacketManager Import functions
 * Class to implement RacketManager Import
 */
class Admin_Import extends Admin_Display {

    private ?Admin_Action_Adapter $actions;
    private ?Admin_Screen_Presenter $screens;
    private ?Import_Action_Dispatcher_Interface $import_action_dispatcher;

    public function __construct(
        $plugin_instance,
        ?Admin_Action_Adapter $actions = null,
        ?Admin_Screen_Presenter $screens = null,
        ?Import_Action_Dispatcher_Interface $dispatcher = null,
    ) {
        parent::__construct( $plugin_instance );
        $c                             = $this->racketmanager->container ?? null;
        $this->actions                 = $actions ?? ( ( $c && $c->has( 'admin_action_adapter' ) ) ? $c->get( 'admin_action_adapter' ) : null );
        $this->screens                 = $screens ?? ( ( $c && $c->has( 'admin_screen_presenter' ) ) ? $c->get( 'admin_screen_presenter' ) : null );
        $this->import_action_dispatcher = $dispatcher;
        if ( $this->actions ) {
            $this->admin_action_adapter = $this->actions;
        }
    }

    /**
     * Display import Page
     */
    public function display_import_page(): void {
        $validator = new Validator();
        $validator = $validator->capability( 'import_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }

        if ( isset( $_POST['import'] ) ) {
            $dispatcher = $this->import_action_dispatcher ?? ( $this->racketmanager->container && $this->racketmanager->container->has( 'import_action_dispatcher' ) ? $this->racketmanager->container->get( 'import_action_dispatcher' ) : null );
            if ( $dispatcher ) {
                $result = $dispatcher->handle( $_GET, $_POST, $_FILES );
                if ( $this->deliver_import_action_result( $result, $_GET ) ) {
                    return;
                }
            }
        }

        $flash      = $this->screens ? $this->screens->apply_flash() : $this->apply_flash_message();
        $flash_data = $flash['flash_data'] ?? array();
        $errors     = is_array( $flash_data['errors'] ?? null ) ? new Error_Bag( $flash_data['errors'] ) : new Error_Bag();

        $clubs = $this->club_service->get_clubs(
            array(
                'type' => 'affiliated',
            )
        );
        $competitions = $this->competition_service->get_all();

        $vm = new Import_Page_View_Model(
            clubs: $clubs,
            competitions: $competitions,
            errors: $errors,
        );

        if ( $this->screens ) {
            $this->screens->present(
                array( 'view_model' => $vm ),
                Import_Page_View_Model::class,
                'admin/tools/import'
            );
        } else {
            $renderer = $this->racketmanager->container && $this->racketmanager->container->has( 'view_renderer' )
                ? $this->racketmanager->container->get( 'view_renderer' )
                : null;
            if ( $renderer ) {
                $renderer->render( 'admin/tools/import', $vm );
            } else {
                require_once RACKETMANAGER_PATH . 'templates/admin/tools/import.php';
            }
        }
    }
}
