<?php
declare( strict_types=1 );
/**
 * Password reset and recovery action adapter for WordPress plugin RacketManager
 *
 * @package    RacketManager
 * @subpackage Infrastructure/Wordpress/Auth
 */

namespace Racketmanager\Infrastructure\Wordpress\Auth;

use JetBrains\PhpStorm\NoReturn;
use Racketmanager\Application\Login\Services\Auth_Service;
use Racketmanager\Infrastructure\Wordpress\Request\Auth\Custom_Login_Request;
use Racketmanager\Infrastructure\Wordpress\Request\Auth\Password_Reset_Request;
use Racketmanager\Infrastructure\Wordpress\Request\Auth\Password_Reset_Route_Request;

/**
 * Adapter handling password recovery and reset actions, HTTP redirects, and key validation.
 */
class Password_Reset_Action_Adapter {
    /**
     * Whether hooks have been registered.
     *
     * @var bool
     */
    private bool $hooks_registered = false;

    /**
     * Password_Reset_Action_Adapter constructor.
     *
     * @param Auth_Service $auth_service Authentication domain service.
     */
    public function __construct(
        private readonly Auth_Service $auth_service
    ) {
    }

    /**
     * Registers all password lost and password reset WordPress action hooks.
     */
    public function register_hooks(): void {
        if ( $this->hooks_registered ) {
            return;
        }
        $this->hooks_registered = true;

        add_action( 'login_form_lostpassword', array( $this, 'redirect_to_custom_lost_password' ) );
        add_action( 'login_form_lostpassword', array( $this, 'do_password_lost' ) );
        add_action( 'login_form_rp', array( $this, 'redirect_to_custom_password_reset' ) );
        add_action( 'login_form_resetpass', array( $this, 'redirect_to_custom_password_reset' ) );
        add_action( 'login_form_rp', array( $this, 'do_password_reset' ) );
        add_action( 'login_form_resetpass', array( $this, 'do_password_reset' ) );
    }

    /**
     * Redirects to custom lost password page.
     */
    public function redirect_to_custom_lost_password(): void {
        $this->handle_custom_lost_password( Custom_Login_Request::from_globals() );
    }

    /**
     * Handle the custom lost password redirect for a parsed request.
     *
     * @param Custom_Login_Request $request Request object.
     */
    public function handle_custom_lost_password( Custom_Login_Request $request ): void {
        if ( $request->is_get() ) {
            if ( is_user_logged_in() ) {
                $this->redirect_logged_in_user();
                $this->terminate();
            }

            wp_safe_redirect( home_url( 'member-password-lost' ) );
            $this->terminate();
        }
    }

    /**
     * Initiates password lost process.
     */
    public function do_password_lost(): void {
        $this->handle_password_lost( Custom_Login_Request::from_globals() );
    }

    /**
     * Handle the password lost submission for a parsed request.
     *
     * @param Custom_Login_Request $request Request object.
     */
    public function handle_password_lost( Custom_Login_Request $request ): void {
        if ( $request->is_post() ) {
            $redirect_url = home_url( 'member-password-lost' );
            $errors       = retrieve_password();
            if ( is_wp_error( $errors ) ) {
                // Errors found.
                $redirect_url = add_query_arg( 'errors', join( ',', $errors->get_error_codes() ), $redirect_url );
            } else {
                // Email sent.
                $redirect_url = add_query_arg( 'checkemail', 'confirm', $redirect_url );
            }
            wp_safe_redirect( $redirect_url );
            $this->terminate();
        }
    }

    /**
     * Redirects to custom password reset page, or login/lost page on error.
     */
    public function redirect_to_custom_password_reset(): void {
        $this->handle_custom_password_reset( Password_Reset_Route_Request::from_globals() );
    }

    /**
     * Handle the custom password reset redirect for a parsed request.
     *
     * @param Password_Reset_Route_Request $request Request object.
     */
    public function handle_custom_password_reset( Password_Reset_Route_Request $request ): void {
        if ( $request->is_get() ) {
            $rp_key   = $request->get_clean_key();
            $rp_login = $request->get_login();
            $user     = $request->check_key();
            if ( ! $user || is_wp_error( $user ) ) {
                if ( $user && $user->get_error_code() === 'expired_key' ) {
                    wp_safe_redirect( home_url( 'member-password-lost?errors=expiredkey' ) );
                } else {
                    wp_safe_redirect( home_url( 'member-password-lost?errors=invalidkey' ) );
                }
                $this->terminate();
            }

            $redirect_url = home_url( 'member-password-reset' );
            $redirect_url = add_query_arg( 'login', esc_attr( $rp_login ), $redirect_url );
            $redirect_url = add_query_arg( 'key', esc_attr( $rp_key ), $redirect_url );

            wp_safe_redirect( $redirect_url );
            $this->terminate();
        }
    }

    /**
     * Completes password reset submission.
     */
    public function do_password_reset(): void {
        $this->handle_password_reset( Password_Reset_Request::from_globals() );
    }

    /**
     * Handle the password reset submission for a parsed request.
     *
     * Note: on an invalid nonce the redirect carries the submitted key and login. The legacy
     * implementation passed booleans here, which dropped the login and sent `key=1`.
     *
     * @param Password_Reset_Request $request Request object.
     */
    public function handle_password_reset( Password_Reset_Request $request ): void {
        if ( ! $request->is_post() ) {
            return;
        }

        $rp_key   = $request->get_rp_key() ?? '';
        $rp_login = $request->get_rp_login() ?? '';

        if ( ! $request->verify_nonce() ) {
            $this->redirect_password_reset_error( $rp_key, $rp_login, 'form_has_timedout' );
        }

        $user = $request->check_key();
        if ( ! $user || is_wp_error( $user ) ) {
            $this->redirect_invalid_reset_key( $user );
        }

        if ( ! $request->passwords_match() ) {
            $this->redirect_password_reset_error( $rp_key, $rp_login, 'password_reset_mismatch' );
        }

        if ( $request->is_password_empty() ) {
            $this->redirect_password_reset_error( $rp_key, $rp_login, 'password_reset_empty' );
        }

        // Parameter checks OK, reset password.
        reset_password( $user, $request->get_password() ?? '' );
        wp_safe_redirect( home_url( 'member-login?passwordUpdate=true' ) );
        $this->terminate();
    }

    /**
     * Redirect to the password reset form with an error code.
     *
     * @param string $key   Reset key.
     * @param string $login User login.
     * @param string $error Error code.
     */
    #[NoReturn]
    private function redirect_password_reset_error( string $key, string $login, string $error ): void {
        $redirect_url = home_url( 'member-password-reset' );
        $redirect_url = add_query_arg( 'key', $key, $redirect_url );
        $redirect_url = add_query_arg( 'login', $login, $redirect_url );
        $redirect_url = add_query_arg( 'error', $error, $redirect_url );
        wp_safe_redirect( $redirect_url );
        $this->terminate();
    }

    /**
     * Redirect to login page on invalid or expired password reset key.
     *
     * @param mixed $user WP_User, WP_Error, or false/null.
     */
    #[NoReturn]
    private function redirect_invalid_reset_key( mixed $user ): void {
        $error = ( is_wp_error( $user ) && $user->get_error_code() === 'expired_key' )
            ? 'expiredkey'
            : 'invalidkey';
        wp_safe_redirect( home_url( "member-login?login=$error" ) );
        $this->terminate();
    }

    /**
     * Redirects the user to the correct page depending on whether admin or not.
     *
     * @param string|null $redirect_to Optional redirect_to URL for admin users.
     */
    public function redirect_logged_in_user( ?string $redirect_to = null ): void {
        $target_url = $this->auth_service->get_logged_in_redirect_url( $redirect_to );
        wp_safe_redirect( $target_url );
    }

    /**
     * Terminate execution after redirect.
     *
     * @return void
     */
    #[NoReturn]
    protected function terminate(): void {
        exit;
    }
}
