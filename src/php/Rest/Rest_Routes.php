<?php
/**
 * Handles registering Racketmanager custom REST endpoints.
 *
 * Class Rest_Routes
 *
 * @package Racketmanager
 */

namespace Racketmanager\Rest;

use Racketmanager\RacketManager;

/**
 * Class to implement the Rest_Routes object
 */
class Rest_Routes {
    private RacketManager $racketmanager;

    /**
     * Constructor
     */
    public function __construct( $plugin_instance ) {
        global $wp_version;
        $this->racketmanager = $plugin_instance;
        if ( version_compare( $wp_version, '4.7', '>=' ) && class_exists( 'WP_REST_Controller' ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'Rest_Resources.php';
            add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        }
    }

    /**
     * Singleton
     *
     * @param RacketManager $plugin_instance
     *
     * @return Rest_Routes
     */
    public static function single( RacketManager $plugin_instance ): Rest_Routes {
        static $single;
        if ( empty( $single ) ) {
            $single = new self( $plugin_instance );
        }

        return $single;
    }

    /**
     * Register all our REST resources.
     */
    public function register_rest_routes(): void {
        $resources = array(
            new Rest_Resources( $this->racketmanager ),
        );
        foreach ( $resources as $resource ) {
            $resource->register_routes();
        }
    }
}
