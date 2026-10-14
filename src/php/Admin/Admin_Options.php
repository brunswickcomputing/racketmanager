<?php
/**
 * RacketManager-Admin API: Admin_Options class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Options
 */

declare( strict_types=1 );

namespace Racketmanager\Admin;

use Racketmanager\Admin\View_Models\Options_Page_View_Model;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Services\Admin\Options\Options_Action_Dispatcher_Interface;
use Racketmanager\Services\Validator\Validator;

/**
 * RacketManager Season Options functions
 * Class to implement RacketManager Options Result
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Options
 */
class Admin_Options extends Admin_Display {

    private Admin_Action_Adapter $actions;
    private Admin_Screen_Presenter $screens;
    private ?Options_Action_Dispatcher_Interface $options_action_dispatcher;

    public function __construct(
        $plugin_instance,
        ?Admin_Action_Adapter $actions = null,
        ?Admin_Screen_Presenter $screens = null,
        ?Options_Action_Dispatcher_Interface $dispatcher = null,
    ) {
        parent::__construct( $plugin_instance );
        $c                               = $this->racketmanager->container;
        $this->actions                   = $actions ?? $c->get( 'admin_action_adapter' );
        $this->screens                   = $screens ?? $c->get( 'admin_screen_presenter' );
        $this->options_action_dispatcher = $dispatcher;
        $this->admin_action_adapter      = $this->actions;
    }

    /**
     * Function to handle administration club displays
     *
     * @param string|null $view
     *
     * @return void
     */
    public function handle_display( ?string $view ): void {
        $this->display_options_page();
    }

    /**
     * Display global settings page (e.g. color scheme options)
     */
    public function display_options_page(): void {
        $validator = new Validator();
        $validator = $validator->capability( 'manage_racketmanager' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }

        if ( isset( $_POST['updateRacketManager'] ) ) {
            $dispatcher = $this->options_action_dispatcher ?? $this->racketmanager->container->get( 'options_action_dispatcher' );
            $result     = $dispatcher->handle( $_GET, $_POST );
            if ( $this->deliver_options_action_result( $result, $_GET ) ) {
                return;
            }
        }

        $flash          = $this->screens->apply_flash();
        $flash_data     = $flash['flash_data'] ?? array();
        $tab            = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( strval( $_GET['tab'] ) ) ) : ( $flash_data['tab'] ?? 'club-players' );
        $stored_options = ( isset( $this->racketmanager ) && is_object( $this->racketmanager ) ) ? ( $this->racketmanager->get_options() ?? array() ) : array();

        if ( ! empty( $flash_data['options'] ) && is_array( $flash_data['options'] ) ) {
            $options = $flash_data['options'];
            $secret_paths = array(
                array( 'billing', 'api_secret_key_live' ),
                array( 'billing', 'api_endpoint_key_live' ),
                array( 'billing', 'api_secret_key_test' ),
                array( 'billing', 'api_endpoint_key_test' ),
                array( 'keys', 'recaptchaSecretKey' ),
            );
            foreach ( $secret_paths as [$section, $key] ) {
                if ( empty( $options[ $section ][ $key ] ) && ! empty( $stored_options[ $section ][ $key ] ) ) {
                    $options[ $section ][ $key ] = $stored_options[ $section ][ $key ];
                }
            }
        } else {
            $options = $stored_options;
        }

        $vm = new Options_Page_View_Model(
            options: $options,
            tab: $tab,
        );

        $this->screens->present(
            array( 'view_model' => $vm ),
            Options_Page_View_Model::class,
            'admin/show-settings'
        );
    }
}
