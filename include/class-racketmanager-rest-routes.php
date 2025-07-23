<?php
/**
 * Handles registering Racketmanager custom REST endpoints.
 *
 * Class Racketmanager_Rest_Routes
 *
 * @package Racketmanager
 */

namespace Racketmanager;

/**
 * Class to implement the Racketmanager_Rest_Routes object
 */
class Racketmanager_Rest_Routes {
    /**
     * Constructor
     */
    public function __construct() {
        global $wp_version;

        if ( version_compare( $wp_version, '4.7', '>=' ) && class_exists( 'WP_REST_Controller' ) ) {
            include plugin_dir_path( __FILE__ ) . 'class-racketmanager-rest-resources.php';
            add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        }
    }

    /**
     * Singleton
     *
     * @return Racketmanager_Rest_Routes
     */
    public static function single(): Racketmanager_Rest_Routes {
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
            new Racketmanager_Rest_Resources(),
        );
        foreach ( $resources as $resource ) {
            $resource->register_routes();
        }
    }
}
Racketmanager_Rest_Routes::single();
