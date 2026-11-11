<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Login\Queries;

use Racketmanager\Application\Login\DTOs\Login_Form_Result;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Application\Ports\Settings_Service_Interface;
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

/**
 * Handles requests for rendering the login and registration form.
 *
 * Builds the template variables required by the login form, resolves login and
 * registration states, converts error codes into display messages, and returns
 * the appropriate login form result. Also handles redirects for authenticated
 * users and validates redirect targets when WordPress redirect helpers are
 * available.
 */
readonly class Get_Login_Form_Handler {
    /**
     * Constructor.
     *
     * @param Option_Gateway_Interface   $option_gateway option gateway.
     * @param Settings_Service_Interface $settings_service settings service.
     */
    public function __construct(
        private Option_Gateway_Interface $option_gateway,
        private Settings_Service_Interface $settings_service
    ) {}

    /**
     * Handle get login form query.
     *
     * @param Get_Login_Form_Query $query query.
     *
     * @return Login_Form_Result
     */
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

    /**
     * Resolve logged in redirect view.
     *
     * @param Get_Login_Form_Query $query query.
     *
     * @return Login_Form_Result
     */
    private function logged_in_redirect_view( Get_Login_Form_Query $query ): Login_Form_Result {
        return Login_Form_Result::html(
            '<script>location.href = "' . esc_url( $this->resolve_logged_in_redirect( $query ) ) . '"</script>'
        );
    }

    /**
     * Resolve logged in redirect.
     *
     * @param Get_Login_Form_Query $query query.
     *
     * @return string
     */
    private function resolve_logged_in_redirect( Get_Login_Form_Query $query ): string {
        $redirect_to = null;
        if ( 'GET' === $query->request_method && $query->redirect_to ) {
            $redirect_to = esc_url_raw( wp_unslash( $query->redirect_to ) );
        }
        return $redirect_to ?: $this->home_url_or_root();
    }

    /**
     * Resolve home url or root.
     *
     * @return string
     */
    private function home_url_or_root(): string {
        return function_exists( 'home_url' ) ? home_url() : '/';
    }

    /**
     * Resolve base vars.
     *
     * @param Get_Login_Form_Query $query query.
     *
     * @return array<string, mixed>
     */
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

    /**
     * Decode site name.
     *
     * @param string $raw raw.
     *
     * @return string
     */
    private function decode_site_name( string $raw ): string {
        return function_exists( 'wp_specialchars_decode' )
            ? wp_specialchars_decode( $raw, ENT_QUOTES )
            : htmlspecialchars_decode( $raw, ENT_QUOTES );
    }

    /**
     * Parse error codes.
     *
     * @param ?string $csv csv.
     *
     * @return array{0: list<string>, 1: list<string>} [ $errors, $error_codes ]
     */
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

    /**
     * Resolve registration view.
     *
     * @param Get_Login_Form_Query $query query.
     * @param array                $vars vars.
     *
     * @return Login_Form_Result
     */
    private function registration_view( Get_Login_Form_Query $query, array $vars ): Login_Form_Result {
        [ $vars['errors'], $vars['error_codes'] ] = $this->parse_error_codes( $query->register_errors );

        if ( ! $vars['users_can_register'] ) {
            return Login_Form_Result::html(
                __( 'Registering new users is currently not allowed', 'racketmanager' )
            );
        }
        return Login_Form_Result::template( 'form-login', $vars );
    }

    /**
     * Resolve login view.
     *
     * @param Get_Login_Form_Query $query query.
     * @param array                $vars vars.
     *
     * @return Login_Form_Result
     */
    private function login_view( Get_Login_Form_Query $query, array $vars ): Login_Form_Result {
        [ $vars['errors'], $vars['error_codes'] ] = $this->parse_error_codes( $query->login_errors );

        $vars['registered']         = $query->registered;
        $vars['redirect']           = $this->resolve_login_redirect( $query, $vars['site_url'] );
        $vars['lost_password_sent'] = 'confirm' === $query->checkemail;
        $vars['password_updated']   = $query->password_updated;
        $vars['logged_out']         = $query->logged_out;

        return Login_Form_Result::template( 'form-login', $vars );
    }

    /**
     * Resolve login redirect.
     *
     * @param Get_Login_Form_Query $query query.
     * @param string               $site_url site url.
     *
     * @return string
     */
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

    /**
     * Uses wp_validate_redirect() when WordPress is loaded, otherwise the supplied fallback.
     *
     * @param string $url url.
     * @param string $fallback fallback.
     *
     * @return string
     */
    private function validate_redirect( string $url, string $fallback ): string {
        return function_exists( 'wp_validate_redirect' ) ? wp_validate_redirect( $url, '' ) : $fallback;
    }
}
