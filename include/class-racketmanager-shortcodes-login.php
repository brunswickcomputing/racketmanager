<?php
/**
 * RacketManager_Shortcodes_Login API: Shortcodes_Login class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerShortcodesLogin
 */

namespace Racketmanager;

use stdClass;

/**
 * Class to implement shortcode functions
 */
class RacketManager_Shortcodes_Login extends RacketManager_Shortcodes {
	/**
	 * Text to return if already signed in
	 *
	 * @var string
	 */
	private $already_signed_in = '';
	/**
	 * Initialize shortcodes
	 */
	public function __construct() {
		add_shortcode( 'custom-login-form', array( $this, 'render_login_form' ) );
		add_shortcode( 'login-form', array( $this, 'login_form' ) );
		add_shortcode( 'custom-password-lost-form', array( $this, 'render_password_lost_form' ) );
		add_shortcode( 'custom-password-reset-form', array( $this, 'render_password_reset_form' ) );
		add_shortcode( 'account-info', array( $this, 'generate_member_account_form' ) );
		add_action( 'init', array( $this, 'load_translations' ) );
	}
	/**
	 * Load translations function
	 *
	 * @return void
	 */
	public function load_translations() {
		$this->already_signed_in = __( 'You are already signed in', 'racketmanager' );
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
		if ( is_user_logged_in() ) {
			$redirect_to = null;
			if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'GET' === $_SERVER['REQUEST_METHOD'] ) {
				$redirect_to = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} else {
				$redirect_to = home_url();
			}
			echo '<script>location.href = "' . esc_url( $redirect_to ) . '"</script>';
			exit;
		}
		// Parse shortcode vars.
		$default_vars      = array( 'show_title' => false );
		$vars              = shortcode_atts( $default_vars, $vars );
		$vars['site_name'] = $racketmanager->site_name;
		$vars['site_url']  = $racketmanager->site_url;
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
		// Retrieve possible errors from request parameters.
		$errors      = array();
		$error_codes = array();

		if ( isset( $_REQUEST['register-errors'] ) ) {  //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$error_codes = explode( ',', sanitize_text_field( wp_unslash( $_REQUEST['register-errors'] ) ) );  //phpcs:ignore WordPress.Security.NonceVerification.Recommended

			foreach ( $error_codes as $code ) {
				$errors[] = Racketmanager_Util::get_error_message( $code );
			}
		}
		$vars['errors']      = $errors;
		$vars['error_codes'] = $error_codes;

		if ( ! get_option( 'users_can_register' ) ) {
			return __( 'Registering new users is currently not allowed', 'racketmanager' );
		} else {
			return $this->load_template( 'form-login', $vars, 'form' );
		}
	}
	/**
	 * Function to display login form
	 *
	 * @param array $vars array of variables.
	 * @return string
	 */
	public function form_login( $vars ) {
		global $racketmanager;
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
				$errors [] = Racketmanager_Util::get_error_message( $code );
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
		return $this->load_template( 'form-login', $vars, 'form' );
	}
	/**
	 * A shortcode for rendering the form used to initiate the password reset.
	 *
	 * @param  array $vars  Shortcode vars.
	 *
	 * @return string  The shortcode output
	 */
	public function render_password_lost_form( $vars ) {
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
				$errors[] = Racketmanager_Util::get_error_message( $code );
			}
		}
		$vars['errors']      = $errors;
		$vars['error_codes'] = $error_codes;
		// Check if the user just requested a new password.
		$vars['lost_password_sent'] = isset( $_REQUEST['checkemail'] ) && 'confirm' === $_REQUEST['checkemail'];
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		return $this->load_template( 'form-password-lost', $vars, 'form' );
	}
	/**
	 * A shortcode for rendering the form used to reset a user's password.
	 *
	 * @param  array $vars  Shortcode vars.
	 *
	 * @return string  The shortcode output
	 */
	public function render_password_reset_form( $vars ) {
		if ( is_user_logged_in() ) {
//			echo '<script>location.href = "' . esc_url( home_url() ) . '"</script>';
//			exit;
		}
		// Parse shortcode vars.
		$default_vars = array( 'show_title' => false );
		$vars         = shortcode_atts( $default_vars, $vars );
		if ( isset( $_REQUEST['login'] ) && isset( $_REQUEST['key'] ) ) { // phpcs:disable WordPress.Security.NonceVerification.Recommended
			$vars['login'] = sanitize_text_field( wp_unslash( $_REQUEST['login'] ) );
			$vars['key']   = sanitize_text_field( wp_unslash( $_REQUEST['key'] ) );
			// Error messages.
			$errors      = array();
			$error_codes = array();
			if ( isset( $_REQUEST['error'] ) ) {
				$error_codes = explode( ',', sanitize_text_field( wp_unslash( $_REQUEST['error'] ) ) );
				foreach ( $error_codes as $code ) {
					$errors [] = Racketmanager_Util::get_error_message( $code );
				}
			}
			$vars['errors']      = $errors;
			$vars['error_codes'] = $error_codes;
			return $this->load_template( 'form-password-reset', $vars, 'form' );
		} else {
			return __( 'Invalid password reset link.', 'racketmanager' );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}
	/**
	 * Generate the form used to display a member account.
	 *
	 * @return string  The output
	 */
	public function generate_member_account_form() {
		if ( ! is_user_logged_in() ) {
			return __( 'You must be signed in to access this page', 'racketmanager' );
		}
		$current_user   = wp_get_current_user();
		$user           = get_user( $current_user->ID );
		$opt_in_choices = Racketmanager_Util::get_email_opt_ins();
		if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
			if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
				if ( isset( $_POST['racketmanager_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'member_account' ) ) {
					$user_update                = clone $user;
					$user_update->email         = isset( $_POST['username'] ) ? sanitize_email( wp_unslash( $_POST['username'] ) ) : null;
					$user_update->firstname     = isset( $_POST['firstname'] ) ? sanitize_text_field( wp_unslash( $_POST['firstname'] ) ) : null;
					$user_update->surname       = isset( $_POST['lastname'] ) ? sanitize_text_field( wp_unslash( $_POST['lastname'] ) ) : null;
					$user_update->contactno     = isset( $_POST['contactno'] ) ? sanitize_text_field( wp_unslash( $_POST['contactno'] ) ) : null;
					$user_update->gender        = isset( $_POST['gender'] ) ? sanitize_text_field( wp_unslash( $_POST['gender'] ) ) : null;
					$user_update->btm           = empty( $_POST['btm'] ) ? null : intval( $_POST['btm'] );
					$user_update->year_of_birth = empty( $_POST['year_of_birth'] ) ? "" : intval( $_POST['year_of_birth'] );
					$user_update->password      = isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : null;
					$user_update->re_password   = isset( $_POST['rePassword'] ) ? sanitize_text_field( wp_unslash( $_POST['rePassword'] ) ) : null;
					$user_update->opt_ins       = isset( $_POST['opt_in'] ) ? wp_unslash( $_POST['opt_in'] ) : array();
				} else {
					return __( 'You are not authorised for this action', 'racketmanager' );
				}
				if ( ! empty( $_POST['action'] ) && 'update-user' === $_POST['action'] ) {
					$user = $user->update( $user_update );
				}
			}
		}
		return $this->load_template(
									'form-member-account',
									array(
										  'user'           => $user,
										  'opt_in_choices' => $opt_in_choices
										  ),
									'form'
									);
	}
}
