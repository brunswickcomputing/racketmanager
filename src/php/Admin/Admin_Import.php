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
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Services\View\View_Renderer_Interface;

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
        $c                              = $this->racketmanager->container;
        $this->actions                  = $actions ?? $c->get( Admin_Action_Adapter::class );
        $screens_key                    = $c->has_definition( Admin_Screen_Presenter::class )
            ? Admin_Screen_Presenter::class
            : ( $c->has_definition( 'admin_screen_presenter' ) ? 'admin_screen_presenter' : null );
        $this->screens                  = $screens ?? ( $screens_key ? $c->get( $screens_key ) : null );
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
            $dispatcher = $this->import_action_dispatcher;
            if ( null === $dispatcher && isset( $this->racketmanager->container ) ) {
                $c              = $this->racketmanager->container;
                $dispatcher_key = $c->has_definition( Import_Action_Dispatcher_Interface::class )
                    ? Import_Action_Dispatcher_Interface::class
                    : ( $c->has( 'import_action_dispatcher' ) ? 'import_action_dispatcher' : null );
                $dispatcher     = $dispatcher_key ? $c->get( $dispatcher_key ) : null;
            }
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
            $renderer_key = $this->racketmanager->container->has( View_Renderer_Interface::class )
                ? View_Renderer_Interface::class
                : 'view_renderer';
            $renderer     = $this->racketmanager->container->get( $renderer_key );
            $renderer->render( 'admin/tools/import', $vm );
        }
    }
}
