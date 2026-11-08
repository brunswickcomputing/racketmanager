<?php
declare( strict_types=1 );
/**
 * Authentication email and notification filter adapter for WordPress plugin RacketManager
 *
 * @package    RacketManager
 * @subpackage Infrastructure/Wordpress/Auth
 */

namespace Racketmanager\Infrastructure\Wordpress\Auth;

use Racketmanager\Application\Login\Services\Auth_Service;
use WP_User;

/**
 * Adapter handling WordPress authentication email filters and password hint notifications.
 */
class Auth_Email_Filter_Adapter {
    /**
     * Whether hooks have been registered.
     *
     * @var bool
     */
    private bool $hooks_registered = false;

    /**
     * Auth_Email_Filter_Adapter constructor.
     *
     * @param Auth_Service $auth_service Authentication domain service.
     */
    public function __construct(
        private readonly Auth_Service $auth_service
    ) {
    }

    /**
     * Registers all WordPress email and notification filter hooks.
     */
    public function register_hooks(): void {
        if ( $this->hooks_registered ) {
            return;
        }
        $this->hooks_registered = true;

        add_filter( 'retrieve_password_message', array( $this, 'racketmanager_retrieve_password_email' ), 10, 4 );
        add_filter( 'password_change_email', array( $this, 'racketmanager_password_change_email' ), 10, 3 );
        add_filter( 'wp_privacy_personal_data_email_content', array( $this, 'racketmanager_privacy_personal_data_email' ), 10, 3 );
        add_filter( 'user_request_action_email_content', array( $this, 'racketmanager_user_request_action_email' ), 10, 2 );
        add_filter( 'wp_new_user_notification_email_admin', array( $this, 'my_wp_new_user_notification_email_admin' ), 10, 3 );
        add_filter( 'wp_new_user_notification_email', array( $this, 'my_wp_new_user_notification_email' ), 10, 3 );
        add_filter( 'password_hint', array( $this, 'racketmanager_change_password_hint' ) );
    }

    /**
     * Return password hint message.
     *
     * @param string $hint_text Original hint text.
     *
     * @return string Password hint text.
     * @noinspection PhpUnusedParameterInspection
     * @SuppressWarnings("php:S1172")
     */
    public function racketmanager_change_password_hint( string $hint_text ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
        return $this->auth_service->get_password_hint();
    }

    /**
     * Return new user notification email details for administrator.
     *
     * @param array  $wp_new_user_notification_email Array of email details.
     * @param object $user                           User object.
     * @param string $blogname                       Site name.
     *
     * @return array New user notification email details.
     */
    public function my_wp_new_user_notification_email_admin( array $wp_new_user_notification_email, object $user, string $blogname ): array {
        return $this->auth_service->format_new_user_notification_email_admin( $wp_new_user_notification_email, $user, $blogname );
    }

    /**
     * Return new user notification email details.
     *
     * @param array   $wp_new_user_notification_email Array of email details.
     * @param WP_User $user                           User object.
     * @param string  $blogname                       Site name.
     *
     * @return array
     * @noinspection PhpUnusedParameterInspection
     * @SuppressWarnings("php:S1172")
     */
    public function my_wp_new_user_notification_email( array $wp_new_user_notification_email, WP_User $user, string $blogname ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return $this->auth_service->format_new_user_notification_email( $wp_new_user_notification_email, $user );
    }

    /**
     * Set email content type to HTML.
     *
     * @return string
     */
    public function racketmanager_wp_email_content_type(): string {
        return $this->auth_service->get_email_content_type();
    }

    /**
     * Set retrieve password email details.
     *
     * @param string $message    Original message.
     * @param string $key        Security key.
     * @param string $user_login User login.
     * @param object $user_data  User data.
     *
     * @return string Email details.
     * @noinspection PhpUnusedParameterInspection
     * @SuppressWarnings("php:S1172")
     */
    public function racketmanager_retrieve_password_email( string $message, string $key, string $user_login, object $user_data ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
        add_filter( 'wp_mail_content_type', array( $this, 'racketmanager_wp_email_content_type' ) );
        return $this->auth_service->format_retrieve_password_email( $key, $user_login, $user_data );
    }

    /**
     * Set password change email.
     *
     * @param array $password_change_mail Email data array.
     * @param array $user_data            Array of old user data.
     * @param array $user_data_new        Array of new user data.
     *
     * @return array
     * @noinspection PhpUnusedParameterInspection
     * @SuppressWarnings("php:S1172")
     */
    public function racketmanager_password_change_email( array $password_change_mail, array $user_data, array $user_data_new ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        add_filter( 'wp_mail_content_type', array( $this, 'racketmanager_wp_email_content_type' ) );
        return $this->auth_service->format_password_change_email( $password_change_mail, $user_data );
    }

    /**
     * Set privacy personal data email.
     *
     * @param string $message    Original email message.
     * @param string $request    Request identifier.
     * @param string $email_data Email data.
     *
     * @return string Email message.
     * @noinspection PhpUnusedParameterInspection
     * @SuppressWarnings("php:S1172")
     */
    public function racketmanager_privacy_personal_data_email( string $message, string $request, string $email_data ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        add_filter( 'wp_mail_content_type', array( $this, 'racketmanager_wp_email_content_type' ) );
        return $this->auth_service->format_privacy_personal_data_email();
    }

    /**
     * Set email message for user action.
     *
     * @param string $message    Original email message.
     * @param array  $email_data Email data.
     *
     * @return string Email message.
     * @noinspection PhpUnusedParameterInspection
     * @SuppressWarnings("php:S1172")
     */
    public function racketmanager_user_request_action_email( string $message, array $email_data ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        add_filter( 'wp_mail_content_type', array( $this, 'racketmanager_wp_email_content_type' ) );
        return $this->auth_service->format_user_request_action_email();
    }
}
