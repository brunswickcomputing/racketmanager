<?php
/**
 * Shortcodes_Login API: Shortcodes_Login class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerShortcodesLogin
 */

namespace Racketmanager\shortcodes;

use Racketmanager\util\Util;
use function Racketmanager\get_user;

/**
 * Class to implement shortcode functions
 */
class Shortcodes_Login extends Shortcodes {
    /**
     * Text to return if already signed in
     *
     * @var string
     */
    private string $already_signed_in = '';
    /**
     * Load translations function
     *
     * @return void
     */
    public function load_translations(): void {
		$this->already_signed_in = __( 'You are already signed in', 'racketmanager' );
    }
    /**
     * A shortcode for rendering the login form.
     *
     * @param array $vars  Shortcode vars.
     *
     * @return string  The shortcode output
     */
    public function render_login_form( array $vars ): string {
		global $racketmanager;
		if ( is_user_logged_in() ) {
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
		$recaptcha_site_key         = $keys['recaptchaSiteKey'] ?? '';
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
     *
     * @return string
     */
    public function form_register( array $vars ): string {
		// Retrieve possible errors from request parameters.
		$errors      = array();
		$error_codes = array();

		if ( isset( $_REQUEST['register-errors'] ) ) {  //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$error_codes = explode( ',', sanitize_text_field( wp_unslash( $_REQUEST['register-errors'] ) ) );  //phpcs:ignore WordPress.Security.NonceVerification.Recommended

			foreach ( $error_codes as $code ) {
				$errors[] = Util::get_error_message( $code );
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
     *
     * @return string
     */
    public function form_login( array $vars ): string {
		global $racketmanager;
		// Check if the user just registered.
		$vars['registered'] = isset( $_REQUEST['registered'] );  //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		// Pass the redirect parameter to the WordPress login functionality: by default,
		// don't specify a redirect, but if a valid redirect URL has been passed as
		// request parameter, use it.
		$vars['redirect'] = '';
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['redirect_to'] ) ) {
			$vars['redirect'] = esc_url( wp_unslash( $_REQUEST['redirect_to'] ) );
		}
		$vars['site_name'] = $racketmanager->site_name;
		$vars['site_url']  = $racketmanager->site_url;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$errors      = array();
		$error_codes = array();
		if ( isset( $_REQUEST['login'] ) ) {
			$error_codes = explode( ',', sanitize_text_field( wp_unslash( $_REQUEST['login'] ) ) );
			foreach ( $error_codes as $code ) {
				$errors[] = Util::get_error_message( $code );
			}
		}
		$vars['errors']      = $errors;
		$vars['error_codes'] = $error_codes;
		// Render the login form using an external template file.
		return $this->load_template( 'form-login', $vars, 'form' );
    }
}
