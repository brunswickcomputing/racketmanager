<?php
/**
 * Option Service
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Options
 */

declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Options;

use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Infrastructure\Gateways\Option\Wp_Option_Gateway;

/**
 * Service for managing WordPress plugin options and standard site configuration.
 */
class Option_Service {

    private Option_Gateway_Interface $option_gateway;
    private array $options = [];
    private string $date_format = 'Y-m-d';
    private string $time_format = 'H:i';
    private string $site_name = '';
    private string $admin_email = '';
    private string $site_url = '';

    public function __construct( ?Option_Gateway_Interface $option_gateway = null ) {
        $this->option_gateway = $option_gateway ?? new Wp_Option_Gateway();
        $this->load_options();
    }

    /**
     * Load options from the gateway into memory.
     */
    public function load_options(): void {
        $raw_options = $this->option_gateway->get_option( 'racketmanager', [] );
        $this->options = is_array( $raw_options ) ? $raw_options : [];

        $raw_date_format   = $this->option_gateway->get_option( 'date_format', 'Y-m-d' );
        $this->date_format = is_scalar( $raw_date_format ) ? (string) $raw_date_format : 'Y-m-d';

        $raw_time_format   = $this->option_gateway->get_option( 'time_format', 'H:i' );
        $this->time_format = is_scalar( $raw_time_format ) ? (string) $raw_time_format : 'H:i';

        $raw_blogname = $this->option_gateway->get_option( 'blogname', '' );
        $blogname_str = is_scalar( $raw_blogname ) ? (string) $raw_blogname : '';
        if ( function_exists( 'wp_specialchars_decode' ) ) {
            $this->site_name = wp_specialchars_decode( $blogname_str, ENT_QUOTES );
        } else {
            $this->site_name = htmlspecialchars_decode( $blogname_str, ENT_QUOTES );
        }

        $raw_admin_email   = $this->option_gateway->get_option( 'admin_email', '' );
        $this->admin_email = is_scalar( $raw_admin_email ) ? (string) $raw_admin_email : '';

        $raw_site_url   = $this->option_gateway->get_option( 'siteurl', '' );
        $this->site_url = is_scalar( $raw_site_url ) ? (string) $raw_site_url : '';

    }

    /**
     * Get plugin options or a specific top-level section.
     *
     * @param false|string $index Optional top-level key lookup.
     * @return mixed
     */
    public function get_options( false|string $index = false ): mixed {
        if ( false !== $index ) {
            return $this->options[ $index ] ?? null;
        }

        return $this->options;
    }

    /**
     * Get a specific top-level option or fallback default.
     *
     * @param string $key Top-level key.
     * @param mixed  $default Default value if key is not found.
     * @return mixed
     */
    public function get_option( string $key, mixed $default = null ): mixed {
        return $this->options[ $key ] ?? $default;
    }

    /**
     * Set top-level options section and persist to storage.
     *
     * @param string $type Section key.
     * @param array  $options Values array.
     */
    public function set_options( string $type, array $options ): void {
        $this->options[ $type ] = $options;
        $this->update_plugin_options( $this->options );
    }

    /**
     * Update and persist the full plugin options array.
     *
     * @param array $options Full plugin options array.
     * @return bool
     */
    public function update_plugin_options( array $options ): bool {
        $this->options = $options;

        return (bool) $this->option_gateway->update_option( 'racketmanager', $this->options );
    }

    public function get_date_format(): string {
        return $this->date_format;
    }

    public function get_time_format(): string {
        return $this->time_format;
    }

    public function get_site_name(): string {
        return $this->site_name;
    }

    public function get_admin_email(): string {
        return $this->admin_email;
    }

    public function get_site_url(): string {
        return $this->site_url;
    }

    /**
     * Determine if LTA / BTM number is required based on roster configuration options.
     *
     * @return bool
     */
    public function is_lta_number_required(): bool {
        $rosters = $this->get_options( 'rosters' );
        return is_array( $rosters ) && ! empty( $rosters['btm'] );
    }

    public function get_gateway(): Option_Gateway_Interface {
        return $this->option_gateway;
    }
}
