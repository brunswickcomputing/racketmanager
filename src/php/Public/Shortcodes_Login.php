<?php
declare( strict_types=1 );

/**
 * Shortcodes_Login API: Shortcodes_Login class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Shortcodes/Login
 */

namespace Racketmanager\Public;

use Racketmanager\Infrastructure\Wordpress\Shortcodes\Login_Shortcode_Adapter;

/**
 * Backward-compatible facade for Login and Member Account shortcodes.
 *
 * All shortcode logic is implemented in Login_Shortcode_Adapter and its Query Handlers.
 * This class preserves legacy public signatures and delegates to the container adapter.
 *
 * @deprecated Use Login_Shortcode_Adapter.
 */
class Shortcodes_Login extends Shortcodes {
    /**
     * Load translations function
     *
     * @return void
     */
    public function load_translations(): void {
        $this->adapter()->load_translations();
    }

    /**
     * A shortcode for rendering the login form.
     *
     * @param array $vars Shortcode vars.
     * @return string The shortcode output
     */
    public function render_login_form( array $vars = [] ): string {
        return $this->adapter()->render_login_form( $vars );
    }

    /**
     * Function to display register form
     *
     * @param array $vars array of variables.
     * @return string
     */
    public function form_register( array $vars = [] ): string {
        return $this->adapter()->render_login_form( $vars, 'register' );
    }

    /**
     * Function to display login form
     *
     * @param array $vars array of variables.
     * @return string
     */
    public function form_login( array $vars = [] ): string {
        return $this->adapter()->render_login_form( $vars );
    }

    /**
     * A shortcode for rendering the password lost form.
     *
     * @param array $vars Shortcode vars.
     * @return string The shortcode output
     */
    public function render_password_lost_form( array $vars = [] ): string {
        return $this->adapter()->render_password_lost_form( $vars );
    }

    /**
     * A shortcode for rendering the password reset form.
     *
     * @param array $vars Shortcode vars.
     * @return string The shortcode output
     */
    public function render_password_reset_form( array $vars = [] ): string {
        return $this->adapter()->render_password_reset_form( $vars );
    }

    /**
     * Member account form function
     *
     * @return string
     */
    public function generate_member_account_form(): string {
        return $this->adapter()->generate_member_account_form();
    }

    private function adapter(): Login_Shortcode_Adapter {
        return $this->racketmanager->container->get( 'login_shortcode_adapter' );
    }
}
