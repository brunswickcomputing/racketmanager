<?php
/**
 * Login class for the WordPress plugin RacketManager
 *
 * @author     Paul Moffat
 * @package    RacketManager
 * @copyright Copyright 2018
 */

namespace Racketmanager;

/**
 * Class for plugin login
 */
class RacketManager_Login {

	/**
	 * Text to return if already signed in
	 *
	 * @var string
	 */
	private $already_signed_in = '';
	/**
	 * Url for registration
	 *
	 * @var string
	 */
	private $register_link = 'member-login?action=register';

	/**
	 * Initialize shortcodes
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'do_enqueue_login_scripts' ) );

		add_shortcode( 'custom-login-form', array( $this, 'render_login_form' ) );
		add_shortcode( 'custom-password-lost-form', array( $this, 'render_password_lost_form' ) );
		add_shortcode( 'custom-password-reset-form', array( $this, 'render_password_reset_form' ) );
		add_shortcode( 'account-info', array( $this, 'render_member_account_form' ) );

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
		add_action( 'member_account_update', array( $this, 'do_member_account_update' ) );

		add_filter( 'authenticate', array( $this, 'maybe_redirect_at_authenticate' ), 101, 3 );
		add_filter( 'login_redirect', array( $this, 'redirect_after_login' ), 10, 3 );
		add_filter( 'retrieve_password_message', array( $this, 'racketmanager_retrieve_password_email' ), 10, 4 );
		add_filter( 'password_change_email', array( $this, 'racketmanager_password_change_email' ), 10, 3 );
		add_filter( 'wp_privacy_personal_data_email_content', array( $this, 'racketmanager_privacy_personal_data_email' ), 10, 3 );
		add_filter( 'user_request_action_email_content', array( $this, 'racketmanager_user_request_action_email' ), 10, 2 );
		add_filter( 'wp_new_user_notification_email_admin', array( $this, 'my_wp_new_user_notification_email_admin' ), 10, 3 );
		add_filter( 'wp_new_user_notification_email', array( $this, 'my_wp_new_user_notification_email' ), 10, 3 );
		add_filter( 'password_hint', array( $this, 'racketmanager_change_password_hint' ), 10, 1 );

		$this->already_signed_in = __( 'You are already signed in', 'racketmanager' );
	}
	/**
	 * Function to enqueue login scripts
	 *   */
	public function do_enqueue_login_scripts() {
		wp_enqueue_script( 'recaptcha', 'https://www.google.com/recaptcha/api.js', array(), RACKETMANAGER_VERSION, array( 'in_footer' => true ) );
	}
	/**
	 * Function to return password hint message
	 *
	 * @param string $hint_text original hint text.
	 * @return string password hint text.
	 */
	public function racketmanager_change_password_hint( $hint_text ) {
		return 'Please use a strong password. Passwords that consist of special characters (&#%!@), upper case/lower case characters and numbers are considered strong.';
	}

	/**
	 * Function to return new user notifcation email details for administrator
	 *
	 * @param array  $wp_new_user_notification_email array of email details.
	 * @param object $user user object.
	 * @param string $blogname site name.
	 * @return array new user notication email details.
	 */
	public function my_wp_new_user_notification_email_admin( $wp_new_user_notification_email, $user, $blogname ) {

		$user_count = count_users();

		$wp_new_user_notification_email['subject'] = sprintf( '[%s] New user %s registered.', $blogname, $user->user_login );
		$wp_new_user_notification_email['message'] = sprintf( '%s has registered to %s.', $user->user_login, $blogname ) . "\n\n\r" . sprintf( 'You now have %d users', $user_count['total_users'] );

		return $wp_new_user_notification_email;
	}
	/**
	 * Function to return new user notifcation email details
	 *
	 * @param array  $wp_new_user_notification_email array of email details.
	 * @param object $user user object.
	 * @param string $blogname site name.
	 * @return array
	 */
	public function my_wp_new_user_notification_email( $wp_new_user_notification_email, $user, $blogname ) {
		global $racketmanager_shortcodes, $racketmanager;

		$key                                       = get_password_reset_key( $user );
		$vars['site_name']                         = $racketmanager->site_name;
		$vars['site_url']                          = $racketmanager->site_url;
		$vars['user_login']                        = $user->user_login;
		$vars['display_name']                      = $user->display_name;
		$vars['action_url']                        = $racketmanager->site_url . '/member-password-reset/?key=' . $key . '&login=' . rawurlencode( $user->user_login );
		$vars['email_link']                        = $racketmanager->admin_email;
		$wp_new_user_notification_email['message'] = $racketmanager_shortcodes->load_template( 'email-welcome', $vars, 'email' );
		$wp_new_user_notification_email['headers'] = 'Content-Type: text/html; charset=UTF-8';

		return $wp_new_user_notification_email;
	}

	/**
	 * Function to set email content type to html
	 *
	 * @return string
	 */
	public function racketmanager_wp_email_content_type() {
		return 'text/html';
	}

	/**
	 * Function to set retrieve password email details
	 *
	 * @param string $message message.
	 * @param string $key security key.
	 * @param string $user_login user login.
	 * @param object $user_data user data.
	 * @return string email details.
	 */
	public function racketmanager_retrieve_password_email( $message, $key, $user_login, $user_data ) {
		global $racketmanager_shortcodes, $racketmanager;

		add_filter( 'wp_mail_content_type', array( $this, 'racketmanager_wp_email_content_type' ) );
		$vars['site_name']    = $racketmanager->site_name;
		$vars['site_url']     = $racketmanager->site_url;
		$vars['user_login']   = $user_login;
		$vars['display_name'] = $user_data->display_name;
		$vars['action_url']   = $racketmanager->site_url . '/member-password-reset/?key=' . $key . '&login=' . rawurlencode( $user_login );
		return $racketmanager_shortcodes->load_template( 'email-password-reset', $vars, 'email' );
	}
	/**
	 * Function to set password change email
	 *
	 * @param array $password_change_message message.
	 * @param array $user_data array of user data.
	 * @param array $user_data_new array of old user data.
	 * @return array
	 */
	public function racketmanager_password_change_email( $password_change_message, $user_data, $user_data_new ) {
		global $racketmanager_shortcodes, $racketmanager;

		add_filter( 'wp_mail_content_type', array( $this, 'racketmanager_wp_email_content_type' ) );
		$vars['site_name']                  = $racketmanager->site_name;
		$vars['site_url']                   = $racketmanager->site_url;
		$vars['user_login']                 = $user_data['user_login'];
		$vars['display_name']               = $user_data['display_name'];
		$vars['email_link']                 = $racketmanager->admin_email;
		$password_change_message['message'] = $racketmanager_shortcodes->load_template( 'email-password-change', $vars, 'email' );

		return $password_change_message;
	}
	/**
	 * Function to set privacy personal data email
	 *
	 * @param string $message original email message.
	 * @param string $request request.
	 * @param string $email_data email data.
	 * @return string email message.
	 */
	public function racketmanager_privacy_personal_data_email( $message, $request, $email_data ) {
		global $racketmanager_shortcodes, $racketmanager;

		add_filter( 'wp_mail_content_type', array( $this, 'racketmanager_wp_email_content_type' ) );
		$vars['site_name'] = $racketmanager->site_name;
		$vars['site_url']  = $racketmanager->site_url;
		return $racketmanager_shortcodes->load_template( 'email-privacy-personal-data', $vars, 'email' );
	}
	/**
	 * Function to set email message for user action
	 *
	 * @param string $message orginal email message.
	 * @param array  $email_data email data.
	 * @return string email message.
	 */
	public function racketmanager_user_request_action_email( $message, $email_data ) {
		global $racketmanager_shortcodes, $racketmanager;

		add_filter( 'wp_mail_content_type', array( $this, 'racketmanager_wp_email_content_type' ) );
		$vars['site_name'] = $racketmanager->site_name;
		$vars['site_url']  = $racketmanager->site_url;
		return $racketmanager_shortcodes->load_template( 'email-user-request-action', $vars, 'email' );
	}
	/**
	 * Function to disable dashboard
	 */
	public function disable_dashboard() {
		if ( current_user_can( 'subscriber' ) && is_admin() && ! DOING_AJAX ) {
			wp_safe_redirect( home_url() );
			exit;
		}
	}
	/**
	 * A shortcode for rendering the login form.
	 *
	 * @param  array $vars  Shortcode vars.
	 *
	 * @return string  The shortcode output
	 */
	public function render_login_form( $vars ) {
		global $racketmanager;

		// Parse shortcode vars.
		$default_vars      = array( 'show_title' => false );
		$vars              = shortcode_atts( $default_vars, $vars );
		$vars['site_name'] = $racketmanager->site_name;
		$vars['site_url']  = $racketmanager->site_url;

		if ( is_user_logged_in() ) {
			return $this->already_signed_in;
		}
		// Retrieve recaptcha key.
		$keys                       = $racketmanager->get_options( 'keys' );
		$recaptcha_site_key         = isset( $keys['recaptchaSiteKey'] ) ? $keys['recaptchaSiteKey'] : '';
		$vars['recaptcha_site_key'] = $recaptcha_site_key;
		$action                     = isset( $_GET[ ( 'action' ) ] ) ? sanitize_text_field( wp_unslash( $_GET[ ( 'action' ) ] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $action ) && 'register' === $action ) {
			return $this->form_register( $vars );
		} else {
			return $this->form_login( $vars );
		}
	}
	/**
	 * Function to display register form
	 *
	 * @param array $vars array of variables.
	 * @return string
	 */
	public function form_register( $vars ) {
		global $racketmanager_shortcodes;
		// Retrieve possible errors from request parameters.
		$errors      = array();
		$error_codes = array();

		if ( isset( $_REQUEST['register-errors'] ) ) {  //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$error_codes = explode( ',', sanitize_text_field( wp_unslash( $_REQUEST['register-errors'] ) ) );  //phpcs:ignore WordPress.Security.NonceVerification.Recommended

			foreach ( $error_codes as $code ) {
				$errors[] = $this->get_error_message( $code );
			}
		}
		$vars['errors']      = $errors;
		$vars['error_codes'] = $error_codes;

		if ( ! get_option( 'users_can_register' ) ) {
			$return = __( 'Registering new users is currently not allowed', 'racketmanager' );
		} else {
			$return = $racketmanager_shortcodes->load_template( 'form-login', $vars, 'form' );
		}
		return $return;
	}
	/**
	 * Function to display login form
	 *
	 * @param array $vars array of variables.
	 * @return string
	 */
	public function form_login( $vars ) {
		global $racketmanager_shortcodes, $racketmanager;
		// Check if the user just registered.
		$vars['registered'] = isset( $_REQUEST['registered'] );  //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		// Pass the redirect parameter to the WordPress login functionality: by default,
		// don't specify a redirect, but if a valid redirect URL has been passed as
		// request parameter, use it.
		$vars['redirect'] = '';
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['redirect_to'] ) ) {
			$vars['redirect'] = wp_validate_redirect( esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ), $vars['redirect'] );
		} elseif ( wp_get_referer() ) {
			if ( strpos( wp_get_referer(), 'member-login' ) > 0 ) {
				$vars['redirect'] = '';
			} elseif ( strpos( wp_get_referer(), $racketmanager->site_url ) === 0 ) {
				$vars['redirect'] = wp_validate_redirect( wp_get_referer(), $vars['redirect'] );
			}
		}
		// Error messages.
		$errors      = array();
		$error_codes = array();
		if ( isset( $_REQUEST['login'] ) ) {
			$error_codes = explode( ',', sanitize_text_field( wp_unslash( $_REQUEST['login'] ) ) );

			foreach ( $error_codes as $code ) {
				$errors [] = $this->get_error_message( $code );
			}
		}
		$vars['errors']      = $errors;
		$vars['error_codes'] = $error_codes;

		// Check if the user just requested a new password.
		$vars['lost_password_sent'] = isset( $_REQUEST['checkemail'] ) && 'confirm' === $_REQUEST['checkemail'];

		// Check if user just updated password.
		$vars['password_updated'] = isset( $_REQUEST['passwordUpdate'] ) && 'true' === $_REQUEST['passwordUpdate'];

		// Check if user just logged out.
		$vars['logged_out'] = isset( $_REQUEST['logged_out'] ) && 'true' === $_REQUEST['logged_out'];
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		// Render the login form using an external template.
		return $racketmanager_shortcodes->load_template( 'form-login', $vars, 'form' );
	}

	/**
	 * Redirect the user to the custom login page instead of wp-login.php.
	 */
	public function redirect_to_custom_login() {
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
	 * @param string $redirect_to   An optional redirect_to URL for admin users.
	 */
	public function redirect_logged_in_user( $redirect_to = null ) {
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
	 * Redirect the user after authentication if there were any errors.
	 *
	 * @param Wp_User|Wp_Error $user       The signed in user, or the errors that have occurred during login.
	 * @param string           $username   The user name used to log in.
	 * @param string           $password   The password used to log in.
	 *
	 * @return Wp_User|Wp_Error The logged in user, or error information if there were errors.
	 */
	public function maybe_redirect_at_authenticate( $user, $username, $password ) {
		// Check if the earlier authenticate filter (most likely,
		// the default WordPress authentication) functions have found errors.
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && is_wp_error( $user ) ) {
			$error_codes = join( ',', $user->get_error_codes() );

			$login_url = home_url( 'member-login' );
			$login_url = add_query_arg( 'login', $error_codes, $login_url );

			wp_safe_redirect( $login_url );
			exit;
		}

		return $user;
	}

	/**
	 * Finds and returns a matching error message for the given error code.
	 *
	 * @param string $error_code    The error code to look up.
	 *
	 * @return string               An error message.
	 */
	public function get_error_message( $error_code ) {
		switch ( $error_code ) {
			case 'empty_password':
				$message = __( 'You need to enter a password to login.', 'racketmanager' );
				break;
			case 'incorrect_password':
				/* translators: %s: lost password url */
				$err     = __( "The password you entered wasn't quite right. <a href='%s'>Did you forget your password</a>?", 'racketmanager' );
				$message = sprintf( $err, wp_lostpassword_url() );
				break;
			case 'email':
				$message = __( 'The email address you entered is not valid.', 'racketmanager' );
				break;
			case 'email_exists':
				$message = __( 'An account exists with this email address.', 'racketmanager' );
				break;
			case 'closed':
				$message = __( 'Registering new users is currently not allowed.', 'racketmanager' );
				break;
			case 'captcha':
				$message = __( 'The Google reCAPTCHA check failed. Are you a robot?', 'racketmanager' );
				break;
			case 'empty_username':
				$message = __( 'You need to enter your email address to continue.', 'racketmanager' );
				break;
			case 'invalid_email':
			case 'invalidcombo':
			case 'invalid_username':
				$message = __( 'There are no users registered with this email address.', 'racketmanager' );
				break;
			case 'expiredkey':
			case 'invalidkey':
				$message = __( 'The password reset link you used is not valid anymore.', 'racketmanager' );
				break;
			case 'password_reset_mismatch':
				$message = __( "The two passwords you entered don't match.", 'racketmanager' );
				break;
			case 'password_reset_empty':
				$message = __( "Sorry, we don't accept empty passwords.", 'racketmanager' );
				break;
			case 'firstname_field_empty':
				$message = __( 'First name must be specified', 'racketmanager' );
				break;
			case 'lastname_field_empty':
				$message = __( 'Last name must be specified', 'racketmanager' );
				break;
			case 'gender_field_empty':
				$message = __( 'Gender must be specified', 'racketmanager' );
				break;
			case 'no_updates':
				$message = __( 'No updates to be made', 'racketmanager' );
				break;
			case 'form_has_timedout':
				$message = __( 'The form has timed out.', 'racketmanager' );
				break;
			case 'btm_field_empty':
				$message = __( 'LTA tennis number missing', 'racketmanager' );
				break;
			default:
				$message = $error_code;
		}
		return $message;
	}

	/**
	 * Redirect to custom login page after the user has been logged out.
	 */
	public function redirect_after_logout() {
		$redirect_url = home_url( 'member-login?logged_out=true' );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Returns the URL to which the user should be redirected after the (successful) login.
	 *
	 * @param string           $redirect_to           The redirect destination URL.
	 * @param string           $requested_redirect_to The requested redirect destination URL passed as a parameter.
	 * @param WP_User|WP_Error $user                  WP_User object if login was successful, WP_Error object otherwise.
	 *
	 * @return string Redirect URL
	 */
	public function redirect_after_login( $redirect_to, $requested_redirect_to, $user ) {
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
	public function redirect_to_custom_register() {
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
	public function register_user( $email, $firstname, $last_name ) {
		$errors = new \WP_Error();

		// Email address is used as both username and email. It is also the only
		// parameter we need to validate.
		if ( ! is_email( $email ) ) {
			$errors->add( 'email', $this->get_error_message( 'email' ) );
			return $errors;
		}

		if ( username_exists( $email ) || email_exists( $email ) ) {
			$errors->add( 'email_exists', $this->get_error_message( 'email_exists' ) );
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
	public function do_register_user() {
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
			$errors = $result->get_error_codes();
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
	private function verify_recaptcha() {
		global $racketmanager;
		// This field is set by the recaptcha widget if check is successful.
		if ( isset( $_POST['g-recaptcha-response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$captcha_response = sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		} else {
			return false;
		}

		$keys                 = $racketmanager->get_options( 'keys' );
		$recaptcha_secret_key = isset( $keys['recaptchaSecretKey'] ) ? $keys['recaptchaSecretKey'] : '';
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
	public function redirect_to_custom_lostpassword() {
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
	 * A shortcode for rendering the form used to initiate the password reset.
	 *
	 * @param  array $vars  Shortcode vars.
	 *
	 * @return string  The shortcode output
	 */
	public function render_password_lost_form( $vars ) {

		global $racketmanager_shortcodes;

		if ( is_user_logged_in() ) {
			return $this->already_signed_in;
		}

		// Parse shortcode vars.
		$default_vars = array( 'show_title' => true );
		$vars         = shortcode_atts( $default_vars, $vars );

		// Retrieve possible errors from request parameters.
		$errors      = array();
		$error_codes = array();
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['errors'] ) ) {
			$error_codes = explode( ',', sanitize_text_field( wp_unslash( $_REQUEST['errors'] ) ) );

			foreach ( $error_codes as $code ) {
				$errors[] = $this->get_error_message( $code );
			}
		}
		$vars['errors']      = $errors;
		$vars['error_codes'] = $error_codes;
		// Check if the user just requested a new password.
		$vars['lost_password_sent'] = isset( $_REQUEST['checkemail'] ) && 'confirm' === $_REQUEST['checkemail'];
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return $racketmanager_shortcodes->load_template( 'form-password-lost', $vars, 'form' );
	}

	/**
	 * Initiates password reset.
	 */
	public function do_password_lost() {
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
	public function redirect_to_custom_password_reset() {
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
	 * A shortcode for rendering the form used to reset a user's password.
	 *
	 * @param  array $vars  Shortcode vars.
	 *
	 * @return string  The shortcode output
	 */
	public function render_password_reset_form( $vars ) {
		global $racketmanager_shortcodes;

		// Parse shortcode vars.
		$default_vars = array( 'show_title' => false );
		$vars         = shortcode_atts( $default_vars, $vars );

		if ( is_user_logged_in() ) {
			return $this->already_signed_in;
		} elseif ( isset( $_REQUEST['login'] ) && isset( $_REQUEST['key'] ) ) { // phpcs:disable WordPress.Security.NonceVerification.Recommended
			$vars['login'] = sanitize_text_field( wp_unslash( $_REQUEST['login'] ) );
			$vars['key']   = sanitize_text_field( wp_unslash( $_REQUEST['key'] ) );

			// Error messages.
			$errors      = array();
			$error_codes = array();
			if ( isset( $_REQUEST['error'] ) ) {
				$error_codes = explode( ',', sanitize_text_field( wp_unslash( $_REQUEST['error'] ) ) );

				foreach ( $error_codes as $code ) {
					$errors [] = $this->get_error_message( $code );
				}
			}
			$vars['errors']      = $errors;
			$vars['error_codes'] = $error_codes;

			return $racketmanager_shortcodes->load_template( 'form-password-reset', $vars, 'form' );
		} else {
			return __( 'Invalid password reset link.', 'racketmanager' );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Resets the user's password if the password reset form was submitted.
	 */
	public function do_password_reset() {
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

	/**
	 * A shortcode for rendering the form used to display a member account.
	 *
	 * @return string  The shortcode output
	 */
	public function render_member_account_form() {

		return $this->generate_member_account_form();
	}

	/**
	 * Generate the form used to display a member account.
	 *
	 * @return string  The output
	 */
	public function generate_member_account_form() {
		global $racketmanager_shortcodes;

		if ( ! is_user_logged_in() ) {
			return __( 'You must be signed in to access this page', 'racketmanager' );
		}

		$current_user = wp_get_current_user();
		if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
			if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
				if ( isset( $_POST['racketmanager_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'member_account' ) ) {
					$user_data = array(
						'user_name'     => isset( $_POST['username'] ) ? sanitize_email( wp_unslash( $_POST['username'] ) ) : '',
						'first_name'    => isset( $_POST['firstname'] ) ? sanitize_text_field( wp_unslash( $_POST['firstname'] ) ) : '',
						'last_name'     => isset( $_POST['lastname'] ) ? sanitize_text_field( wp_unslash( $_POST['lastname'] ) ) : '',
						'password'      => isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : '',
						'rePassword'    => isset( $_POST['rePassword'] ) ? sanitize_text_field( wp_unslash( $_POST['rePassword'] ) ) : '',
						'contactno'     => isset( $_POST['contactno'] ) ? sanitize_text_field( wp_unslash( $_POST['contactno'] ) ) : '',
						'gender'        => isset( $_POST['gender'] ) ? sanitize_text_field( wp_unslash( $_POST['gender'] ) ) : '',
						'btm'           => ! empty( $_POST['btm'] ) ? intval( $_POST['btm'] ) : '',
						'year_of_birth' => ! empty( $_POST['year_of_birth'] ) ? intval( $_POST['year_of_birth'] ) : '',
					);
				} else {
					return __( 'You are not authorised for this action', 'racketmanager' );
				}
				if ( ! empty( $_POST['action'] ) && 'update-user' === $_POST['action'] ) {
					$user_data = $this->update_user_profile( $current_user, $user_data );
				}
			} elseif ( 'GET' === $_SERVER['REQUEST_METHOD'] ) {
				$user_data = array(
					'user_name'     => $current_user->user_email,
					'first_name'    => get_user_meta( $current_user->ID, 'first_name', true ),
					'last_name'     => get_user_meta( $current_user->ID, 'last_name', true ),
					'contactno'     => get_user_meta( $current_user->ID, 'contactno', true ),
					'gender'        => get_user_meta( $current_user->ID, 'gender', true ),
					'btm'           => get_user_meta( $current_user->ID, 'btm', true ),
					'year_of_birth' => get_user_meta( $current_user->ID, 'year_of_birth', true ),
				);
			}
		}
		return $racketmanager_shortcodes->load_template( 'form-member-account', array( 'user_data' => $user_data ), 'form' );
	}

	/**
	 * Generate the form used to display a member account.
	 *
	 * @param object $current_user current user object.
	 * @param array  $user_data    user data from form.
	 * @return array $user_data    updated user data.
	 */
	private function update_user_profile( $current_user, $user_data ) {

		$updates = false;

		$user_data = $this->validate_user_profile( $user_data );

		if ( isset( $user_data['error'] ) ) {
			$user_data['message'] = __( 'Errors in form', 'racketmanager' );
			return $user_data;
		}
		$updated_user = array();
		if ( $current_user->user_email !== $user_data['user_name'] ) {
			$updates                    = true;
			$updated_user['user_email'] = $user_data['user_name'];
		}
		if ( get_user_meta( $current_user->ID, 'first_name', true ) !== $user_data['first_name'] ) {
			$updates                    = true;
			$updated_user['first_name'] = $user_data['first_name'];
		}
		if ( get_user_meta( $current_user->ID, 'last_name', true ) !== $user_data['last_name'] ) {
			$updates                   = true;
			$updated_user['last_name'] = $user_data['last_name'];
		}
		if ( empty( $user_data['contactno'] ) && ! empty( get_user_meta( $current_user->ID, 'contactno', true ) ) ) {
			$updates                   = true;
			$updated_user['contactno'] = $user_data['contactno'];
		}
		if ( get_user_meta( $current_user->ID, 'contactno', true ) !== $user_data['contactno'] ) {
			$updates                   = true;
			$updated_user['contactno'] = $user_data['contactno'];
		}
		if ( get_user_meta( $current_user->ID, 'gender', true ) !== $user_data['gender'] ) {
			$updates                = true;
			$updated_user['gender'] = $user_data['gender'];
		}
		if ( empty( $user_data['btm'] ) ) {
			if ( ! empty( get_user_meta( $current_user->ID, 'btm', true ) ) ) {
				$updates             = true;
				$updated_user['btm'] = $user_data['btm'];
			}
		} elseif ( get_user_meta( $current_user->ID, 'btm', true ) !== $user_data['btm'] ) {
			$updates             = true;
			$updated_user['btm'] = $user_data['btm'];
		}
		if ( empty( $user_data['year_of_birth'] ) ) {
			if ( ! empty( get_user_meta( $current_user->ID, 'year_of_birth', true ) ) ) {
				$updates                       = true;
				$updated_user['year_of_birth'] = $user_data['year_of_birth'];
			}
		} elseif ( intval( get_user_meta( $current_user->ID, 'year_of_birth', true ) ) !== $user_data['year_of_birth'] ) {
			$updates                       = true;
			$updated_user['year_of_birth'] = $user_data['year_of_birth'];
		}
		if ( ! empty( $user_data['password'] ) ) {
			$updates                  = true;
			$updated_user['password'] = $user_data['password'];
		}
		if ( ! $updates ) {
			$user_data['message'] = $this->get_error_message( 'no_updates' );
			return $user_data;
		}
		foreach ( $updated_user as $key => $value ) {
			// http://codex.wordpress.org/Function_Reference/wp_update_user.
			if ( 'contactno' === $key ) {
				update_user_meta( $current_user->ID, $key, $value );
			} elseif ( 'btm' === $key ) {
				update_user_meta( $current_user->ID, $key, $value );
			} elseif ( 'year_of_birth' === $key ) {
				update_user_meta( $current_user->ID, $key, $value );
			} elseif ( 'gender' === $key ) {
				update_user_meta( $current_user->ID, $key, $value );
			} elseif ( 'first_name' === $key ) {
				if ( get_user_meta( $current_user->ID, 'first_name', true ) !== $updated_user['first_name'] ) {
					update_user_meta( $current_user->ID, $key, $value );
					wp_update_user(
						array(
							'ID'           => $current_user->ID,
							'display_name' => $value . ' ' . sanitize_text_field( $updated_user['last_name'] ),
						)
					);
				}
			} elseif ( 'last_name' === $key ) {
				if ( get_user_meta( $current_user->ID, 'last_name', true ) !== $updated_user['last_name'] ) {
					update_user_meta( $current_user->ID, $key, $value );
					wp_update_user(
						array(
							'ID'           => $current_user->ID,
							'display_name' => sanitize_text_field( $updated_user['first_name'] ) . ' ' . $value,
						)
					);
				}
			} elseif ( 'password' === $key ) {
				wp_set_password( $value, $current_user->ID );
				wp_set_auth_cookie( $current_user->ID, 1, true );
			} else {
				wp_update_user(
					array(
						'ID' => $current_user->ID,
						$key => $value,
					)
				);
			}
		}
		$user_data['message'] = __( 'Your profile has been successfully updated', 'racketmanager' );
		return $user_data;
	}
	/**
	 * Validate user profile data
	 *
	 * @param array $user_data user data array.
	 * @return array updated user data.
	 */
	private function validate_user_profile( $user_data ) {
		global $racketmanager;
		if ( empty( $user_data['user_name'] ) ) {
			$user_data['user_name_error'] = $this->get_error_message( 'empty_username' );
			$user_data['error']           = true;
		}
		if ( empty( $user_data['first_name'] ) ) {
			$user_data['first_name_error'] = $this->get_error_message( 'firstname_field_empty' );
			$user_data['error']            = true;
		}
		if ( empty( $user_data['last_name'] ) ) {
			$user_data['last_name_error'] = $this->get_error_message( 'lastname_field_empty' );
			$user_data['error']           = true;
		}
		if ( empty( $user_data['gender'] ) ) {
			$user_data['gender_error'] = $this->get_error_message( 'gender_field_empty' );
			$user_data['error']        = true;
		}
		if ( empty( $user_data['btm'] ) ) {
			$player_options = $racketmanager->get_options( 'rosters' );
			if ( isset( $player_options['btm'] ) && '1' === $player_options['btm'] ) {
				$user_data['btm_error'] = $this->get_error_message( 'btm_field_empty' );
				$user_data['error']     = true;
			}
		}
		if ( $user_data['password'] !== $user_data['rePassword'] ) {
			$user_data['rePassword_error'] = $this->get_error_message( 'password_reset_mismatch' );
			$user_data['error']            = true;
		}
		return $user_data;
	}
}
