<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Login\Queries;

use Racketmanager\Application\Login\DTOs\Login_Form_Result;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Util\Util;
use function esc_url;
use function esc_url_raw;
use function htmlspecialchars_decode;
use function home_url;
use function str_starts_with;
use function wp_specialchars_decode;
use function wp_unslash;
use function wp_validate_redirect;
use function __;

readonly class Get_Login_Form_Handler {
    public function __construct(
        private Option_Gateway_Interface $option_gateway,
        private Settings_Service $settings_service
    ) {}

    public function handle( Get_Login_Form_Query $query ): Login_Form_Result {
        if ( $query->is_logged_in ) {
            return $this->logged_in_redirect_view( $query );
        }

        $vars = $this->base_vars( $query );

        if ( 'register' === $query->action ) {
            return $this->registration_view( $query, $vars );
        }

        return $this->login_view( $query, $vars );
    }

    private function logged_in_redirect_view( Get_Login_Form_Query $query ): Login_Form_Result {
        return Login_Form_Result::html(
            '<script>location.href = "' . esc_url( $this->resolve_logged_in_redirect( $query ) ) . '"</script>'
        );
    }

    private function resolve_logged_in_redirect( Get_Login_Form_Query $query ): string {
        $redirect_to = null;
        if ( 'GET' === $query->request_method && $query->redirect_to ) {
            $redirect_to = esc_url_raw( wp_unslash( $query->redirect_to ) );
        }
        return $redirect_to ?: $this->home_url_or_root();
    }

    private function home_url_or_root(): string {
        return function_exists( 'home_url' ) ? home_url() : '/';
    }

    /** @return array<string, mixed> */
    private function base_vars( Get_Login_Form_Query $query ): array {
        $keys = $this->settings_service->get_category( 'keys' );
        return [
            'show_title'         => $query->show_title,
            'tab'                => 'register' === $query->action ? 'registration' : 'login',
            'site_name'          => $this->decode_site_name( (string) $this->option_gateway->get_option( 'blogname', '' ) ),
            'site_url'           => (string) $this->option_gateway->get_option( 'siteurl', '' ),
            'recaptcha_site_key' => $keys['recaptchaSiteKey'] ?? '',
            'users_can_register' => (bool) $this->option_gateway->get_option( 'users_can_register', false ),
        ];
    }

    private function decode_site_name( string $raw ): string {
        return function_exists( 'wp_specialchars_decode' )
            ? wp_specialchars_decode( $raw, ENT_QUOTES )
            : htmlspecialchars_decode( $raw, ENT_QUOTES );
    }

    /** @return array{0: list<string>, 1: list<string>} [ $errors, $error_codes ] */
    private function parse_error_codes( ?string $csv ): array {
        if ( empty( $csv ) ) {
            return [ [], [] ];
        }
        $error_codes = explode( ',', $csv );
        $errors      = array_map(
            static fn ( string $code ): string => Util::get_error_message( trim( $code ) ),
            $error_codes
        );
        return [ $errors, $error_codes ];
    }

    private function registration_view( Get_Login_Form_Query $query, array $vars ): Login_Form_Result {
        [ $vars['errors'], $vars['error_codes'] ] = $this->parse_error_codes( $query->register_errors );

        if ( ! $vars['users_can_register'] ) {
            return Login_Form_Result::html(
                __( 'Registering new users is currently not allowed', 'racketmanager' )
            );
        }
        return Login_Form_Result::template( 'form-login', $vars, 'form' );
    }

    private function login_view( Get_Login_Form_Query $query, array $vars ): Login_Form_Result {
        [ $vars['errors'], $vars['error_codes'] ] = $this->parse_error_codes( $query->login_errors );

        $vars['registered']         = $query->registered;
        $vars['redirect']           = $this->resolve_login_redirect( $query, $vars['site_url'] );
        $vars['lost_password_sent'] = 'confirm' === $query->checkemail;
        $vars['password_updated']   = $query->password_updated;
        $vars['logged_out']         = $query->logged_out;

        return Login_Form_Result::template( 'form-login', $vars, 'form' );
    }

    private function resolve_login_redirect( Get_Login_Form_Query $query, string $site_url ): string {
        if ( ! empty( $query->redirect_to ) ) {
            return $this->validate_redirect(
                esc_url_raw( wp_unslash( $query->redirect_to ) ),
                esc_url_raw( $query->redirect_to )
            );
        }

        $referer = (string) $query->referer;
        if ( '' === $referer
             || str_contains( $referer, 'member-login' )
             || '' === $site_url
             || ! str_starts_with( $referer, $site_url ) ) {
            return '';
        }

        return $this->validate_redirect( $referer, $referer );
    }

    /** Uses wp_validate_redirect() when WordPress is loaded, otherwise the supplied fallback. */
    private function validate_redirect( string $url, string $fallback ): string {
        return function_exists( 'wp_validate_redirect' ) ? wp_validate_redirect( $url, '' ) : $fallback;
    }
}
