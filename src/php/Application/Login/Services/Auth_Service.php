<?php
declare( strict_types=1 );
/**
 * Auth Service for RacketManager
 *
 * @package    RacketManager
 * @subpackage Application/Login/Services
 */

namespace Racketmanager\Application\Login\Services;

use Racketmanager\Application\Ports\Template_Renderer_Interface;
use Racketmanager\Exceptions\Template_Renderer_Missing_Exception;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use WP_Error;
use WP_User;

/**
 * Service handling pure authentication, registration, notification formatting,
 * and redirect target resolution.
 */
readonly class Auth_Service {
    /**
     * Auth_Service constructor.
     *
     * @param Option_Service                   $option_service    Option service.
     * @param Template_Renderer_Interface|null $template_renderer Template renderer.
     */
    public function __construct(
        private Option_Service $option_service,
        private ?Template_Renderer_Interface $template_renderer = null
    ) {
    }

    /**
     * Validates and completes the new user signup process.
     *
     * @param string $email      The new user's email address.
     * @param string $firstname  The new user's first name.
     * @param string $last_name  The new user's last name.
     *
     * @return int|WP_Error The id of the user that was created, or error if failed.
     */
    public function register_user( string $email, string $firstname, string $last_name ): WP_Error|int {
        $validation_error = $this->validate_registration_email( $email );
        if ( null !== $validation_error ) {
            return $validation_error;
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
        if ( ! is_wp_error( $user_id ) ) {
            wp_new_user_notification( $user_id, null, 'both' );
        }

        return $user_id;
    }

    /**
     * Validates email address format and uniqueness for user registration.
     *
     * @param string $email Email address to validate.
     *
     * @return WP_Error|null Error if validation fails, null otherwise.
     */
    private function validate_registration_email( string $email ): ?WP_Error {
        // Email address is used as both username and email. It is also the only
        // parameter we need to validate.
        if ( ! is_email( $email ) ) {
            return new WP_Error( 'email', __( 'The email address you entered is not valid.', 'racketmanager' ) );
        }

        if ( username_exists( $email ) || email_exists( $email ) ) {
            return new WP_Error( 'email_exists', __( 'An account exists with this email address.', 'racketmanager' ) );
        }

        return null;
    }

    /**
     * Returns password hint message.
     *
     * @return string Password hint text.
     */
    public function get_password_hint(): string {
        return 'Please use a strong password. Passwords that consist of special characters (&#%!@), upper case/lower case characters and numbers are considered strong.';
    }

    /**
     * Returns new user notification email details for administrator.
     *
     * @param array  $wp_new_user_notification_email Array of email details.
     * @param object $user                           User object.
     * @param string $blogname                       Site name.
     *
     * @return array New user notification email details.
     */
    public function format_new_user_notification_email_admin( array $wp_new_user_notification_email, object $user, string $blogname ): array {
        $user_count = count_users();

        $wp_new_user_notification_email['subject'] = sprintf( '[%s] New user %s registered.', $blogname, $user->user_login );
        $wp_new_user_notification_email['message'] = sprintf( '%s has registered to %s.', $user->user_login, $blogname ) . "\n\n\r" . sprintf( 'You now have %d users', $user_count['total_users'] );

        return $wp_new_user_notification_email;
    }

    /**
     * Returns new user notification email details for subscriber.
     *
     * @param array   $wp_new_user_notification_email Array of email details.
     * @param WP_User $user                           User object.
     *
     * @return array
     */
    public function format_new_user_notification_email( array $wp_new_user_notification_email, WP_User $user ): array {
        $key                                       = get_password_reset_key( $user );
        $vars['site_name']                         = $this->option_service->get_site_name();
        $vars['site_url']                          = $this->option_service->get_site_url();
        $vars['user_login']                        = $user->user_login;
        $vars['display_name']                      = $user->display_name;
        $vars['action_url']                        = $this->option_service->get_site_url() . '/member-password-reset/?key=' . $key . '&login=' . rawurlencode( $user->user_login );
        $vars['email_link']                        = $this->option_service->get_admin_email();
        $wp_new_user_notification_email['message'] = $this->render_template( 'email-welcome', $vars, 'email' );
        $wp_new_user_notification_email['headers'] = 'Content-Type: text/html; charset=UTF-8';

        return $wp_new_user_notification_email;
    }

    /**
     * Returns email content type.
     *
     * @return string
     */
    public function get_email_content_type(): string {
        return 'text/html';
    }

    /**
     * Formats retrieve password email details.
     *
     * @param string $key        Security key.
     * @param string $user_login User login.
     * @param object $user_data  User data object.
     *
     * @return string Formatted email HTML message.
     */
    public function format_retrieve_password_email( string $key, string $user_login, object $user_data ): string {
        $vars['site_name']    = $this->option_service->get_site_name();
        $vars['site_url']     = $this->option_service->get_site_url();
        $vars['user_login']   = $user_login;
        $vars['display_name'] = $user_data->display_name;
        $vars['action_url']   = $this->option_service->get_site_url() . '/member-password-reset/?key=' . $key . '&login=' . rawurlencode( $user_login );
        return $this->render_template( 'email-password-reset', $vars, 'email' );
    }

    /**
     * Formats password change notification email.
     *
     * @param array $password_change_mail Email data array.
     * @param array $user_data            Old user data array.
     *
     * @return array
     */
    public function format_password_change_email( array $password_change_mail, array $user_data ): array {
        $vars['site_name']               = $this->option_service->get_site_name();
        $vars['site_url']                = $this->option_service->get_site_url();
        $vars['user_login']              = $user_data['user_login'];
        $vars['display_name']            = $user_data['display_name'];
        $vars['email_link']              = $this->option_service->get_admin_email();
        $password_change_mail['message'] = $this->render_template( 'email-password-change', $vars, 'email' );
        return $password_change_mail;
    }

    /**
     * Formats privacy personal data email.
     *
     * @return string Formatted email message.
     */
    public function format_privacy_personal_data_email(): string {
        $vars['site_name'] = $this->option_service->get_site_name();
        $vars['site_url']  = $this->option_service->get_site_url();
        return $this->render_template( 'email-privacy-personal-data', $vars, 'email' );
    }

    /**
     * Formats user action request email.
     *
     * @return string Formatted email message.
     */
    public function format_user_request_action_email(): string {
        $vars['site_name'] = $this->option_service->get_site_name();
        $vars['site_url']  = $this->option_service->get_site_url();
        return $this->render_template( 'email-user-request-action', $vars, 'email' );
    }

    /**
     * Evaluates the redirect destination URL after login.
     *
     * @param string           $redirect_to Requested redirect URL.
     * @param WP_Error|WP_User $user        Logged in user or WP_Error.
     *
     * @return string Target redirect URL.
     */
    public function evaluate_redirect_after_login( string $redirect_to, WP_Error|WP_User $user ): string {
        if ( str_contains( $redirect_to, 'password-reset' ) ) {
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
     * Evaluates redirect URL for a logged-in user.
     *
     * @param string|null  $redirect_to Desired redirect target.
     * @param WP_User|null $user        Current user (or null to resolve via wp_get_current_user).
     *
     * @return string
     */
    public function get_logged_in_redirect_url( ?string $redirect_to = null, ?WP_User $user = null ): string {
        $user ??= wp_get_current_user();
        if ( user_can( $user, 'manage_options' ) ) {
            if ( ! empty( $redirect_to ) ) {
                return $redirect_to;
            }
            return admin_url();
        }
        return home_url();
    }

    /**
     * Render template using the injected template renderer.
     *
     * @param string               $template      Template name.
     * @param array<string, mixed> $vars       Template variables.
     * @param false|string         $template_type Template type.
     *
     * @return string
     *
     * @throws Template_Renderer_Missing_Exception If template renderer is missing.
     */
    private function render_template( string $template, array $vars, false|string $template_type = false ): string {
        if ( null === $this->template_renderer ) {
            throw new Template_Renderer_Missing_Exception( 'Template renderer is required to render login templates.' );
        }

        return $this->template_renderer->render( $template, $vars, $template_type );
    }
}
