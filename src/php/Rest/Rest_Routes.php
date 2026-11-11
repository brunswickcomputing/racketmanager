<?php
declare( strict_types=1 );
/**
 * Handles registering Racketmanager custom REST endpoints.
 *
 * Class Rest_Routes
 *
 * @package Racketmanager
 */

namespace Racketmanager\Rest;

use Racketmanager\Application\Finance\Commands\Update_Payment_Handler;
use Racketmanager\Infrastructure\Wordpress\Rest\Rest_Controller_Adapter;
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
        $this->racketmanager = $plugin_instance;
        if ( class_exists( 'WP_REST_Controller' ) ) {
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
        $update_payment_handler = null;
        if ( method_exists( $this->racketmanager->container, 'has_definition' ) && $this->racketmanager->container->has_definition( Update_Payment_Handler::class ) ) {
            $handler = $this->racketmanager->container->get( Update_Payment_Handler::class );
            if ( $handler instanceof Update_Payment_Handler ) {
                $update_payment_handler = $handler;
            }
        }

        $resources = array(
            new Rest_Resources(
                $this->racketmanager,
                $this->racketmanager->container->get( Rest_Controller_Adapter::class ),
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                $update_payment_handler
            ),
            new Export_Controller( $this->racketmanager ),
        );
        foreach ( $resources as $resource ) {
            $resource->register_routes();
        }
    }
}
