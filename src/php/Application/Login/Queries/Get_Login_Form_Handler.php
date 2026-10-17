<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Login\Queries;

use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\RacketManager;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Util\Util;
use function esc_url;
use function esc_url_raw;
use function home_url;
use function str_starts_with;
use function strpos;
use function wp_unslash;
use function wp_validate_redirect;
use function __;

class Get_Login_Form_Handler {
    public function __construct(
        private readonly Option_Gateway_Interface $option_gateway,
        private readonly Settings_Service $settings_service,
        private readonly ?RacketManager $racketmanager = null
    ) {}

    public function handle( Get_Login_Form_Query $query ): Shortcode_View_Model {
        if ( $query->is_logged_in ) {
            if ( 'GET' === $query->request_method ) {
                $redirect_to = $query->redirect_to ? esc_url_raw( wp_unslash( $query->redirect_to ) ) : null;
            } else {
                $redirect_to = function_exists( 'home_url' ) ? home_url() : '/';
            }
            if ( ! $redirect_to ) {
                $redirect_to = function_exists( 'home_url' ) ? home_url() : '/';
            }

            return Shortcode_View_Model::for_html(
                '<script>location.href = "' . esc_url( $redirect_to ) . '"</script>'
            );
        }

        $site_name = $this->racketmanager->site_name ?? '';
        $site_url  = $this->racketmanager->site_url ?? '';
        $keys      = $this->settings_service->get_category( 'keys' );
        $recaptcha_site_key = $keys['recaptchaSiteKey'] ?? '';

        $vars = [
            'show_title'         => $query->show_title,
            'site_name'          => $site_name,
            'site_url'           => $site_url,
            'recaptcha_site_key' => $recaptcha_site_key,
        ];

        if ( 'register' === $query->action ) {
            $errors      = [];
            $error_codes = [];

            if ( ! empty( $query->register_errors ) ) {
                $error_codes = explode( ',', $query->register_errors );
                foreach ( $error_codes as $code ) {
                    $errors[] = Util::get_error_message( trim( $code ) );
                }
            }

            $vars['errors']      = $errors;
            $vars['error_codes'] = $error_codes;

            $users_can_register = (bool) $this->option_gateway->get_option( 'users_can_register', false );
            if ( ! $users_can_register ) {
                return Shortcode_View_Model::for_html(
                    __( 'Registering new users is currently not allowed', 'racketmanager' )
                );
            }

            return Shortcode_View_Model::for_template( 'form-login', $vars, 'form' );
        }

        $vars['registered'] = $query->registered;
        $vars['redirect']   = '';

        if ( ! empty( $query->redirect_to ) ) {
            $vars['redirect'] = function_exists( 'wp_validate_redirect' )
                ? wp_validate_redirect( esc_url_raw( wp_unslash( $query->redirect_to ) ), $vars['redirect'] )
                : esc_url_raw( $query->redirect_to );
        } elseif ( ! empty( $query->referer ) ) {
            if ( strpos( $query->referer, 'member-login' ) !== false ) {
                $vars['redirect'] = '';
            } elseif ( '' !== $site_url && str_starts_with( $query->referer, $site_url ) ) {
                $vars['redirect'] = function_exists( 'wp_validate_redirect' )
                    ? wp_validate_redirect( $query->referer, $vars['redirect'] )
                    : $query->referer;
            }
        }

        $errors      = [];
        $error_codes = [];
        if ( ! empty( $query->login_errors ) ) {
            $error_codes = explode( ',', $query->login_errors );
            foreach ( $error_codes as $code ) {
                $errors[] = Util::get_error_message( trim( $code ) );
            }
        }

        $vars['errors']             = $errors;
        $vars['error_codes']        = $error_codes;
        $vars['lost_password_sent'] = 'confirm' === $query->checkemail;
        $vars['password_updated']   = $query->password_updated;
        $vars['logged_out']         = $query->logged_out;

        return Shortcode_View_Model::for_template( 'form-login', $vars, 'form' );
    }
}
