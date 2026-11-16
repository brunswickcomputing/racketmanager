<?php
declare( strict_types=1 );
/**
 * Options Admin Controller
 *
 * Handles options administrative screen display and mutations.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Options_Page_View_Model;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Services\Admin\Options\Options_Action_Dispatcher_Interface;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;

/**
 * Class Options_Admin_Controller
 *
 * Coordinates settings tab display and update requests.
 */
final readonly class Options_Admin_Controller {

    /**
     * @param Option_Service                      $option_service            Options service.
     * @param Options_Action_Dispatcher_Interface $options_action_dispatcher Dispatcher for options updates.
     * @param Action_Guard_Interface              $action_guard              Security guard service.
     */
    public function __construct(
        private Option_Service $option_service,
        private Options_Action_Dispatcher_Interface $options_action_dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * Handle options page display and mutations.
     *
     * @param array<string, mixed> $query      Current query arguments.
     * @param array<string, mixed> $post       Current POST payload.
     * @param array<string, mixed> $flash_data Restored flash payload.
     *
     * @return array{view_model?: Options_Page_View_Model, redirect?: string, message?: string|null, message_type?: bool|string|null, flash_data?: array<string, mixed>}
     */
    public function options_page( array $query, array $post, array $flash_data = array() ): array {
        if ( isset( $post['updateRacketManager'] ) ) {
            $result = $this->options_action_dispatcher->handle( $query, $post );

            $extra_args = array();
            if ( null !== $result->tab_override && '' !== $result->tab_override ) {
                $extra_args['tab'] = $result->tab_override;
            }
            $extra_args = array_merge( $extra_args, $result->query_params );

            $redirect = Admin_Redirect_Url_Builder::options_screen( $query, $extra_args );

            return array(
                'redirect'     => $redirect,
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            );
        }

        $this->action_guard->assert_capability( 'manage_racketmanager' );

        $stored_options = $this->option_service->get_options();
        $options        = $stored_options;

        if ( ! empty( $flash_data['options'] ) && is_array( $flash_data['options'] ) ) {
            $options      = $flash_data['options'];
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
        }

        $tab = isset( $query['tab'] ) ? sanitize_text_field( wp_unslash( strval( $query['tab'] ) ) ) : ( $flash_data['tab'] ?? 'club-players' );
        $tab = sanitize_key( strval( $tab ) );
        if ( '' === $tab ) {
            $tab = 'club-players';
        }

        $errors = is_array( $flash_data['errors'] ?? null ) ? new Error_Bag( $flash_data['errors'] ) : new Error_Bag();

        return array(
            'view_model' => new Options_Page_View_Model(
                options: $options,
                tab: $tab,
                errors: $errors,
            ),
        );
    }
}
