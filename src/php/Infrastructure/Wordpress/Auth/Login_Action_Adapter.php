<?php
declare( strict_types=1 );
/**
 * Login action adapter for WordPress plugin RacketManager
 *
 * @package    RacketManager
 * @subpackage Infrastructure/Wordpress/Auth
 */

namespace Racketmanager\Infrastructure\Wordpress\Auth;

use JetBrains\PhpStorm\NoReturn;
use Racketmanager\Application\Login\Services\Auth_Service;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Infrastructure\Wordpress\Request\Auth\Custom_Login_Request;
use Racketmanager\Infrastructure\Wordpress\Request\Auth\User_Registration_Request;
use WP_Error;
use WP_User;

/**
 * Adapter handling WordPress authentication hooks, HTTP redirects, and script enqueuing.
 */
class Login_Action_Adapter {
    /**
     * Relative registration page link.
     *
     * @var string
     */
    private const string REGISTER_LINK = 'member-login?action=register';

    /**
     * Login_Action_Adapter constructor.
     *
     * @param Auth_Service                  $auth_service           Authentication domain service.
     * @param Option_Service                $option_service         Option service.
     * @param Password_Reset_Action_Adapter $password_reset_adapter Password reset adapter.
     * @param Auth_Email_Filter_Adapter     $email_filter_adapter   Email filter adapter.
     */
    public function __construct(
        private readonly Auth_Service $auth_service,
        private readonly Option_Service $option_service,
        private readonly Password_Reset_Action_Adapter $password_reset_adapter,
        private readonly Auth_Email_Filter_Adapter $email_filter_adapter
    ) {
        $this->register_hooks();
    }

    /**
     * Registers all WordPress authentication and login action/filter hooks.
     */
    private function register_hooks(): void {
        add_action( 'wp_enqueue_scripts', array( $this, 'do_enqueue_login_scripts' ) );
        add_action( 'login_form_login', array( $this, 'redirect_to_custom_login' ) );
        add_action( 'wp_logout', array( $this, 'redirect_after_logout' ) );
        add_action( 'login_form_register', array( $this, 'redirect_to_custom_register' ) );
        add_action( 'login_form_register', array( $this, 'do_register_user' ) );
        add_action( 'admin_init', array( $this, 'disable_dashboard' ) );
        $this->password_reset_adapter->register_hooks();
        add_filter( 'login_redirect', array( $this, 'redirect_after_login' ), 10, 3 );
        $this->email_filter_adapter->register_hooks();
    }

    /**
     * Enqueue login scripts.
     */
    public function do_enqueue_login_scripts(): void {
        wp_enqueue_script( 'recaptcha', 'https://www.google.com/recaptcha/api.js', array(), RACKETMANAGER_VERSION, array( 'in_footer' => true ) );
    }

    /**
     * Disable dashboard access for subscribers.
     */
    public function disable_dashboard(): void {
        if ( is_user_logged_in() ) {
            $user = wp_get_current_user();
            if ( $user->has_cap( 'subscriber' ) && is_admin() && ! wp_doing_ajax() ) {
                wp_safe_redirect( home_url() );
                $this->terminate();
            }
        }
    }

    /**
     * Redirect the user to the custom login page instead of wp-login.php.
     */
    public function redirect_to_custom_login(): void {
        $this->handle_custom_login( Custom_Login_Request::from_globals() );
    }

    /**
     * Handle the custom login redirect for a parsed request.
     *
     * @param Custom_Login_Request $request Request object.
     */
    public function handle_custom_login( Custom_Login_Request $request ): void {
        if ( $request->is_get() ) {
            $redirect_to = $request->get_redirect_to();
            if ( is_user_logged_in() ) {
                $this->redirect_logged_in_user( $redirect_to );
                $this->terminate();
            }

            // The rest are redirected to the login page.
            $login_url = home_url( 'member-login' );
            if ( ! empty( $redirect_to ) ) {
                $login_url = add_query_arg( 'redirect_to', $redirect_to, $login_url );
            }
            wp_safe_redirect( $login_url );
            $this->terminate();
        }
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
     * Redirect to custom login page after the user has been logged out.
     */
    #[NoReturn]
    public function redirect_after_logout(): void {
        $redirect_url = home_url();
        wp_safe_redirect( $redirect_url );
        $this->terminate();
    }

    /**
     * Returns the URL to which the user should be redirected after successful login.
     *
     * @param string           $redirect_to           Redirect destination URL.
     * @param string           $requested_redirect_to Requested redirect destination URL.
     * @param WP_Error|WP_User $user                  WP_User object on success, WP_Error otherwise.
     *
     * @return string Redirect URL.
     * @noinspection PhpUnusedParameterInspection
     * @SuppressWarnings("php:S1172")
     */
    public function redirect_after_login( string $redirect_to, string $requested_redirect_to, WP_Error|WP_User $user ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
        return $this->auth_service->evaluate_redirect_after_login( $redirect_to, $user );
    }

    /**
     * Redirects user to custom registration page instead of default.
     */
    public function redirect_to_custom_register(): void {
        $this->handle_custom_register( Custom_Login_Request::from_globals() );
    }

    /**
     * Handle the custom registration redirect for a parsed request.
     *
     * @param Custom_Login_Request $request Request object.
     */
    public function handle_custom_register( Custom_Login_Request $request ): void {
        if ( $request->is_get() ) {
            if ( is_user_logged_in() ) {
                $this->redirect_logged_in_user();
            } else {
                wp_safe_redirect( home_url( self::REGISTER_LINK ) );
            }
            $this->terminate();
        }
    }

    /**
     * Handles registration of a new user.
     */
    #[NoReturn]
    public function do_register_user(): void {
        $this->handle_register_user( User_Registration_Request::from_globals() );
    }

    /**
     * Handle registration of a new user for a parsed request.
     *
     * @param User_Registration_Request $request Request object.
     */
    #[NoReturn]
    public function handle_register_user( User_Registration_Request $request ): void {
        $redirect_url = home_url( self::REGISTER_LINK );
        if ( $request->has_http_method() && ! $request->is_post() ) {
            wp_safe_redirect( $redirect_url );
            $this->terminate();
        }

        $errors = array();

        if ( ! get_option( 'users_can_register' ) ) { // Registration closed, display error.
            $errors[]     = 'closed';
            $redirect_url = add_query_arg( 'register-errors', 'closed', $redirect_url );
            wp_safe_redirect( $redirect_url );
            $this->terminate();
        }
        if ( ! $request->verify_nonce() ) {
            $errors[]     = 'security';
            $redirect_url = add_query_arg( 'register-errors', 'security', $redirect_url );
            wp_safe_redirect( $redirect_url );
            $this->terminate();
        }
        if ( ! $request->verify_recaptcha( $this->option_service ) ) { // Recaptcha check failed, display error.
            $errors[]     = 'captcha';
            $redirect_url = add_query_arg( 'register-errors', 'captcha', $redirect_url );
            wp_safe_redirect( $redirect_url );
            $this->terminate();
        }
        $email = $request->get_email();
        if ( ! $email ) {
            $errors[] = 'email';
        }
        $firstname = $request->get_first_name();
        if ( ! $firstname ) {
            $errors[] = 'first_name';
        }
        $last_name = $request->get_last_name();
        if ( ! $last_name ) {
            $errors[] = 'last_name';
        }
        if ( ! $errors ) {
            $result = $this->auth_service->register_user( $email, $firstname, $last_name );
            if ( ! is_wp_error( $result ) ) {
                update_user_meta( $result, 'show_admin_bar_front', false );
                // Success, redirect to login page.
                $redirect_url = home_url( 'member-login' );
                $redirect_url = add_query_arg( 'registered', $email, $redirect_url );
            }
        }
        if ( $errors ) {
            $error_msgs   = join( ',', $errors );
            $redirect_url = add_query_arg( 'register-errors', $error_msgs, $redirect_url );
        }
        wp_safe_redirect( $redirect_url );
        $this->terminate();
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
