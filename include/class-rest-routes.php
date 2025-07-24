<?php
/**
 * Handles registering Racketmanager custom REST endpoints.
 *
 * Class Rest_Routes
 *
 * @package Racketmanager
 */

namespace Racketmanager;

/**
 * Class to implement the Rest_Routes object
 */
class Rest_Routes {
    /**
     * Constructor
     */
    public function __construct() {
        global $wp_version;

        if ( version_compare( $wp_version, '4.7', '>=' ) && class_exists( 'WP_REST_Controller' ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-rest-resources.php';
            add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        }
    }

    /**
     * Singleton
     *
     * @return Rest_Routes
     */
    public static function single(): Rest_Routes {
        static $single;

        if ( empty( $single ) ) {
            $single = new self();
        }

        return $single;
    }

    /**
     * Register all our REST resources.
     */
    public function register_rest_routes(): void {
        $resources = array(
            new Rest_Resources(),
        );
        foreach ( $resources as $resource ) {
            $resource->register_routes();
        }
    }
}
Rest_Routes::single();
