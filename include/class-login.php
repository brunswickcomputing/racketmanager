<?php
/**
 * Login class for the WordPress plugin RacketManager
 *
 * @author     Paul Moffat
 * @package    RacketManager
 * @copyright Copyright 2018
 */

namespace Racketmanager;

use JetBrains\PhpStorm\NoReturn;
use Racketmanager\util\Util;
use WP_Error;
use WP_User;

/**
 * Class for plugin login
 */
class Login {
    /**
     * Url for registration
     *
     * @var string
     */
    private string $register_link = 'member-login?action=register';

    /**
     * Initialize shortcodes
     *
     * @return void
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'do_enqueue_login_scripts' ) );
        add_action( 'login_form_login', array( $this, 'redirect_to_custom_login' ) );
        add_action( 'wp_logout', array( $this, 'redirect_after_logout' ) );
        add_action( 'login_form_register', array( $this, 'redirect_to_custom_register' ) );
        add_action( 'login_form_register', array( $this, 'do_register_user' ) );
        add_action( 'admin_init', array( $this, 'disable_dashboard' ) );
        add_action( 'login_form_lostpassword', array( $this, 'redirect_to_custom_lostpassword' ) );
        add_action( 'login_form_lostpassword', array( $this, 'do_password_lost' ) );
        add_action( 'login_form_rp', array( $this, 'redirect_to_custom_password_reset' ) );
        add_action( 'login_form_resetpass', array( $this, 'redirect_to_custom_password_reset' ) );
        add_action( 'login_form_rp', array( $this, 'do_password_reset' ) );
        add_action( 'login_form_resetpass', array( $this, 'do_password_reset' ) );
        add_filter( 'login_redirect', array( $this, 'redirect_after_login' ), 10, 3 );
        add_filter( 'retrieve_password_message', array( $this, 'racketmanager_retrieve_password_email' ), 10, 4 );
        add_filter( 'password_change_email', array( $this, 'racketmanager_password_change_email' ), 10, 3 );
        add_filter( 'wp_privacy_personal_data_email_content', array( $this, 'racketmanager_privacy_personal_data_email' ), 10, 3 );
        add_filter( 'user_request_action_email_content', array( $this, 'racketmanager_user_request_action_email' ), 10, 2 );
        add_filter( 'wp_new_user_notification_email_admin', array( $this, 'my_wp_new_user_notification_email_admin' ), 10, 3 );
        add_filter( 'wp_new_user_notification_email', array( $this, 'my_wp_new_user_notification_email' ), 10, 3 );
        add_filter( 'password_hint', array( $this, 'racketmanager_change_password_hint' ) );
    }
    /**
     * Function to enqueue login scripts
     *   */
    public function do_enqueue_login_scripts(): void {
        wp_enqueue_script( 'recaptcha', 'https://www.google.com/recaptcha/api.js', array(), RACKETMANAGER_VERSION, array( 'in_footer' => true ) );
    }
    /**
     * Function to return password hint message
     *
     * @param string $hint_text original hint text.
     *
     * @return string password hint text.
     */
    public function racketmanager_change_password_hint( string $hint_text ): string {
        return 'Please use a strong password. Passwords that consist of special characters (&#%!@), upper case/lower case characters and numbers are considered strong.';
    }

    /**
     * Function to return new user notification email details for administrator
     *
     * @param array $wp_new_user_notification_email array of email details.
     * @param object $user user object.
     * @param string $blogname site name.
     *
     * @return array new user notication email details.
     */
    public function my_wp_new_user_notification_email_admin( array $wp_new_user_notification_email, object $user, string $blogname ): array {

        $user_count = count_users();

        $wp_new_user_notification_email['subject'] = sprintf( '[%s] New user %s registered.', $blogname, $user->user_login );
        $wp_new_user_notification_email['message'] = sprintf( '%s has registered to %s.', $user->user_login, $blogname ) . "\n\n\r" . sprintf( 'You now have %d users', $user_count['total_users'] );

        return $wp_new_user_notification_email;
    }
    /**
     * Function to return new user notifcation email details
     *
     * @param array $wp_new_user_notification_email array of email details.
     * @param WP_User $user user object.
     * @param string $blogname site name.
     *
     * @return array
     */
    public function my_wp_new_user_notification_email( array $wp_new_user_notification_email, WP_User $user, string $blogname ): array {
        global $racketmanager;

        $key                                       = get_password_reset_key( $user );
        $vars['site_name']                         = $racketmanager->site_name;
        $vars['site_url']                          = $racketmanager->site_url;
        $vars['user_login']                        = $user->user_login;
        $vars['display_name']                      = $user->display_name;
        $vars['action_url']                        = $racketmanager->site_url . '/member-password-reset/?key=' . $key . '&login=' . rawurlencode( $user->user_login );
        $vars['email_link']                        = $racketmanager->admin_email;
        $wp_new_user_notification_email['message'] = $racketmanager->shortcodes->load_template( 'email-welcome', $vars, 'email' );
        $wp_new_user_notification_email['headers'] = 'Content-Type: text/html; charset=UTF-8';

        return $wp_new_user_notification_email;
    }

    /**
     * Function to set email content type to html
     *
     * @return string
     */
    public function racketmanager_wp_email_content_type(): string {
        return 'text/html';
    }

    /**
     * Function to set retrieve password email details
     *
     * @param string $message message.
     * @param string $key security key.
     * @param string $user_login user login.
     * @param object $user_data user data.
     *
     * @return string email details.
     */
    public function racketmanager_retrieve_password_email( string $message, string $key, string $user_login, object $user_data ): string {
        global $racketmanager;

        add_filter( 'wp_mail_content_type', array( $this, 'racketmanager_wp_email_content_type' ) );
        $vars['site_name']    = $racketmanager->site_name;
        $vars['site_url']     = $racketmanager->site_url;
        $vars['user_login']   = $user_login;
        $vars['display_name'] = $user_data->display_name;
        $vars['action_url']   = $racketmanager->site_url . '/member-password-reset/?key=' . $key . '&login=' . rawurlencode( $user_login );
        return $racketmanager->shortcodes->load_template( 'email-password-reset', $vars, 'email' );
    }
    /**
     * Function to set password change email
     *
     * @param array $password_change_message message.
     * @param array $user_data array of user data.
     * @param array $user_data_new array of old user data.
     *
     * @return array
     */
    public function racketmanager_password_change_email( array $password_change_mail, array $user_data, array $user_data_new ): array {
        global $racketmanager;
        add_filter( 'wp_mail_content_type', array( $this, 'racketmanager_wp_email_content_type' ) );
        $vars['site_name']               = $racketmanager->site_name;
        $vars['site_url']                = $racketmanager->site_url;
        $vars['user_login']              = $user_data['user_login'];
        $vars['display_name']            = $user_data['display_name'];
        $vars['email_link']              = $racketmanager->admin_email;
        $password_change_mail['message'] = $racketmanager->shortcodes->load_template( 'email-password-change', $vars, 'email' );
        return $password_change_mail;
    }
    /**
     * Function to set privacy personal data email
     *
     * @param string $message original email message.
     * @param string $request request.
     * @param string $email_data email data.
     *
     * @return string email message.
     */
    public function racketmanager_privacy_personal_data_email( string $message, string $request, string $email_data ): string {
        global $racketmanager;

        add_filter( 'wp_mail_content_type', array( $this, 'racketmanager_wp_email_content_type' ) );
        $vars['site_name'] = $racketmanager->site_name;
        $vars['site_url']  = $racketmanager->site_url;
        return $racketmanager->shortcodes->load_template( 'email-privacy-personal-data', $vars, 'email' );
    }
    /**
     * Function to set email message for user action
     *
     * @param string $message orginal email message.
     * @param array $email_data email data.
     *
     * @return string email message.
     */
    public function racketmanager_user_request_action_email( string $message, array $email_data ): string {
        global $racketmanager;

        add_filter( 'wp_mail_content_type', array( $this, 'racketmanager_wp_email_content_type' ) );
        $vars['site_name'] = $racketmanager->site_name;
        $vars['site_url']  = $racketmanager->site_url;
        return $racketmanager->shortcodes->load_template( 'email-user-request-action', $vars, 'email' );
    }
    /**
     * Function to disable dashboard
     */
    public function disable_dashboard(): void {
        if ( is_user_logged_in() ) {
            $user = wp_get_current_user();
            if ( $user->has_cap( 'subscriber' ) && is_admin() && ! wp_doing_ajax() ) {
                wp_safe_redirect( home_url() );
                exit;
            }
        }
    }

    /**
     * Redirect the user to the custom login page instead of wp-login.php.
     */
    public function redirect_to_custom_login(): void {
        if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'GET' === $_SERVER['REQUEST_METHOD'] ) {
            $redirect_to = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ( is_user_logged_in() ) {
                $this->redirect_logged_in_user( $redirect_to );
                exit;
            }

            // The rest are redirected to the login page.
            $login_url = home_url( 'member-login' );
            if ( ! empty( $redirect_to ) ) {
                $login_url = add_query_arg( 'redirect_to', $redirect_to, $login_url );
            }
            wp_safe_redirect( $login_url );
            exit;
        }
    }

    /**
     * Redirects the user to the correct page depending on whether admin or not.
     *
     * @param string|null $redirect_to   An optional redirect_to URL for admin users.
     */
    public function redirect_logged_in_user( string $redirect_to = null ): void {
        $user = wp_get_current_user();
        if ( user_can( $user, 'manage_options' ) ) {
            if ( $redirect_to ) {
                wp_safe_redirect( $redirect_to );
            } else {
                wp_safe_redirect( admin_url() );
            }
        } else {
            wp_safe_redirect( home_url() );
        }
    }
    /**
     * Redirect to custom login page after the user has been logged out.
     */
    #[NoReturn]
    public function redirect_after_logout(): void {
        $redirect_url = home_url();
        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Returns the URL to which the user should be redirected after the (successful) login.
     *
     * @param string $redirect_to           The redirect destination URL.
     * @param string $requested_redirect_to The requested redirect destination URL passed as a parameter.
     * @param WP_Error|WP_User $user                  WP_User object if login was successful, WP_Error object otherwise.
     *
     * @return string Redirect URL
     */
    public function redirect_after_login( string $redirect_to, string $requested_redirect_to, WP_Error|WP_User $user ): string {
        if ( strpos( $redirect_to, 'password-reset' ) ) {
            $redirect_to = '';
        }
        $redirect_url = home_url();
        if ( ! isset( $user->ID ) ) {
            return $redirect_url;
        }

        if ( user_can( $user, 'manage_options' ) ) {
            // Use the redirect_to parameter if one is set, otherwise redirect to admin dashboard.
            if ( '' === $redirect_to ) {
                $redirect_url = admin_url();
            } else {
                $redirect_url = $redirect_to;
            }
        } elseif ( '' === $redirect_to ) { // Use the redirect_to parameter if one is set, otherwise redirect to homepage.
                $redirect_url = home_url();
        } else {
            $redirect_url = $redirect_to;
        }

        return wp_validate_redirect( $redirect_url, home_url() );
    }

    /**
     * Redirects the user to the custom registration page instead
     * of thedefault.
     */
    public function redirect_to_custom_register(): void {
        if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'GET' === $_SERVER['REQUEST_METHOD'] ) {
            if ( is_user_logged_in() ) {
                $this->redirect_logged_in_user();
            } else {
                wp_safe_redirect( home_url( $this->register_link ) );
            }
            exit;
        }
    }

    /**
     * Validates and then completes the new user signup process if all went well.
     *
     * @param string $email         The new user's email address.
     * @param string $firstname    The new user's first name.
     * @param string $last_name     The new user's last name.
     *
     * @return int|WP_Error         The id of the user that was created, or error if failed.
     */
    public function register_user( string $email, string $firstname, string $last_name ): WP_Error|int {
        $errors = new WP_Error();

        // Email address is used as both username and email. It is also the only
        // parameter we need to validate.
        if ( ! is_email( $email ) ) {
            $errors->add( 'email', Util::get_error_message( 'email' ) );
            return $errors;
        }

        if ( username_exists( $email ) || email_exists( $email ) ) {
            $errors->add( 'email_exists', Util::get_error_message( 'email_exists' ) );
            return $errors;
        }

        // Generate the password so that the subscriber will have to check email...
        $password = wp_generate_password( 12, false );

        $user_data = array(
            'user_login' => $email,
            'user_email' => $email,
            'user_pass'  => $password,
            'first_name' => $firstname,
            'last_name'  => $last_name,
            'nickname'   => $firstname,
        );

        $user_id = wp_insert_user( $user_data );
        wp_new_user_notification( $user_id, null, 'both' );

        return $user_id;
    }

    /**
     * Handles the registration of a new user.
     *
     * Used through the action hook "login_form_register" activated on wp-login.php
     * when accessed through the registration action.
     */
    #[NoReturn]
    public function do_register_user(): void {
        $redirect_url = home_url( $this->register_link );
        if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
            wp_safe_redirect( $redirect_url );
            exit;
        }

        $errors = array();

        if ( ! get_option( 'users_can_register' ) ) { // Registration closed, display error.
            $errors[]     = 'closed';
            $redirect_url = add_query_arg( 'register-errors', 'closed', $redirect_url );
            wp_safe_redirect( $redirect_url );
            exit;
        }
        if ( ! isset( $_POST['racketmanager_register_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_register_nonce'] ) ), 'racketmanager_register' ) ) {
            $errors[]     = 'security';
            $redirect_url = add_query_arg( 'register-errors', 'security', $redirect_url );
            wp_safe_redirect( $redirect_url );
            exit;
        }
        if ( ! $this->verify_recaptcha() ) { // Recaptcha check failed, display error.
            $errors[]     = 'captcha';
            $redirect_url = add_query_arg( 'register-errors', 'captcha', $redirect_url );
            wp_safe_redirect( $redirect_url );
            exit;
        }
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        if ( ! $email ) {
            $errors[] = 'email';
        }
        $firstname = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
        if ( ! $firstname ) {
            $errors[] = 'first_name';
        }
        $last_name = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
        if ( ! $last_name ) {
            $errors[] = 'last_name';
        }
        if ( ! $errors ) {
            $result = $this->register_user( $email, $firstname, $last_name );
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
        exit;
    }

    /**
     * Checks that the reCAPTCHA parameter sent with the registration
     * request is valid.
     *
     * @return bool True if the CAPTCHA is OK, otherwise false.
     */
    private function verify_recaptcha(): bool {
        global $racketmanager;
        // This field is set by the recaptcha widget if check is successful.
        if ( isset( $_POST['g-recaptcha-response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $captcha_response = sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        } else {
            return false;
        }

        $keys                 = $racketmanager->get_options( 'keys' );
        $recaptcha_secret_key = $keys['recaptchaSecretKey'] ?? '';
        if ( ! $recaptcha_secret_key ) {
            return false;
        }
        // Verify the captcha response from Google.
        $response = wp_remote_post(
            'https://www.google.com/recaptcha/api/siteverify',
            array(
                'body' => array(
                    'secret'   => $recaptcha_secret_key,
                    'response' => $captcha_response,
                ),
            )
        );

        $success = false;
        if ( $response && is_array( $response ) ) {
            $decoded_response = json_decode( $response['body'] );
            $success          = $decoded_response->success;
        }
        return $success;
    }

    /**
     * Redirects the user to the custom "Forgot your password?" page instead of
     * wp-login.php?action=lostpassword.
     */
    public function redirect_to_custom_lostpassword(): void {
        if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'GET' === $_SERVER['REQUEST_METHOD'] ) {
            if ( is_user_logged_in() ) {
                $this->redirect_logged_in_user();
                exit;
            }

            wp_safe_redirect( home_url( 'member-password-lost' ) );
            exit;
        }
    }


    /**
     * Initiates password reset.
     */
    public function do_password_lost(): void {
        if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
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
            exit;
        }
    }

    /**
     * Redirects to the custom password reset page, or the login page
     * if there are errors.
     */
    public function redirect_to_custom_password_reset(): void {
        if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'GET' === $_SERVER['REQUEST_METHOD'] ) {
            // phpcs:disable WordPress.Security.NonceVerification.Recommended
            // Verify key / login combo.
            $rp_key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
            $rp_login = isset( $_GET['login'] ) ? sanitize_text_field( wp_unslash( $_GET['login'] ) ) : '';
            preg_replace( '/[^a-z0-9]/i', '', $rp_key );
            $user = check_password_reset_key( $rp_key, $rp_login );
            if ( ! $user || is_wp_error( $user ) ) {
                if ( $user && $user->get_error_code() === 'expired_key' ) {
                    wp_safe_redirect( home_url( 'member-password-lost?errors=expiredkey' ) );
                } else {
                    wp_safe_redirect( home_url( 'member-password-lost?errors=invalidkey' ) );
                }
                exit;
            }
            // phpcs:enable WordPress.Security.NonceVerification.Recommended

            $redirect_url = home_url( 'member-password-reset' );
            $redirect_url = add_query_arg( 'login', esc_attr( $rp_login ), $redirect_url );
            $redirect_url = add_query_arg( 'key', esc_attr( $rp_key ), $redirect_url );

            wp_safe_redirect( $redirect_url );
            exit;
        }
    }


    /**
     * Resets the user's password if the password reset form was submitted.
     */
    public function do_password_reset(): void {
        if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
            if ( isset( $_POST['racketmanager_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_reset-password' ) ) {
                $rp_key   = isset( $_POST['rp_key'] ) ? sanitize_text_field( wp_unslash( $_POST['rp_key'] ) ) : null;
                $rp_login = isset( $_POST['rp_login'] ) ? sanitize_text_field( wp_unslash( $_POST['rp_login'] ) ) : null;
                $user     = check_password_reset_key( $rp_key, $rp_login );

                if ( ! $user || is_wp_error( $user ) ) {
                    if ( $user && $user->get_error_code() === 'expired_key' ) {
                        wp_safe_redirect( home_url( 'member-login?login=expiredkey' ) );
                    } else {
                        wp_safe_redirect( home_url( 'member-login?login=invalidkey' ) );
                    }
                    exit;
                }

                $password    = isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : null;
                $re_password = isset( $_POST['rePassword'] ) ? sanitize_text_field( wp_unslash( $_POST['rePassword'] ) ) : null;
                if ( $password !== $re_password ) {
                    // Passwords don't match.
                    $redirect_url = home_url( 'member-password-reset' );

                    $redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
                    $redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
                    $redirect_url = add_query_arg( 'error', 'password_reset_mismatch', $redirect_url );

                    wp_safe_redirect( $redirect_url );
                    exit;
                }

                if ( empty( $password ) ) {
                    // Password is empty.
                    $redirect_url = home_url( 'member-password-reset' );

                    $redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
                    $redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
                    $redirect_url = add_query_arg( 'error', 'password_reset_empty', $redirect_url );

                    wp_safe_redirect( $redirect_url );
                    exit;
                }

                // Parameter checks OK, reset password.
                reset_password( $user, $password );
                wp_safe_redirect( home_url( 'member-login?passwordUpdate=true' ) );

                exit;

            } else {
                $rp_key       = isset( $_POST['rp_key'] ) && sanitize_text_field( wp_unslash( $_POST['rp_key'] ) );
                $rp_login     = isset( $_POST['rp_login'] ) && sanitize_text_field( wp_unslash( $_POST['rp_login'] ) );
                $redirect_url = home_url( 'member-password-reset' );
                $redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
                $redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
                $redirect_url = add_query_arg( 'error', 'form_has_timedout', $redirect_url );
                wp_safe_redirect( $redirect_url );
                exit();
            }
        }
    }
}
