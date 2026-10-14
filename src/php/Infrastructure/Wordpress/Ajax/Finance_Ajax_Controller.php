<?php
/**
 * AJAX front-end finance controller
 *
 * @package    RacketManager
 * @subpackage Infrastructure/Wordpress/Ajax
 */

namespace Racketmanager\Infrastructure\Wordpress\Ajax;

use JetBrains\PhpStorm\NoReturn;
use Racketmanager\Infrastructure\Security\Security_Service;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory;
use Racketmanager\RacketManager;

/**
 * WordPress entry point for finance AJAX actions.
 */
class Finance_Ajax_Controller {
    public function __construct(
        private RacketManager $racketmanager,
        private ?Finance_Ajax_Adapter $adapter = null
    ) {}

    public function register_hooks( Ajax_Registry $registry ): void {
        $registry->register_with_unauthenticated( 'purchase_order_modal', array( $this, 'show_purchase_order_modal' ), array( $this, 'logged_out_modal' ) );
        $registry->register_with_unauthenticated( 'set_purchase_order', array( $this, 'set_purchase_order' ), array( $this, 'logged_out_modal' ) );
    }

    #[NoReturn]
    public function logged_out_modal(): void {
        $this->send( $this->get_adapter()->logged_out_modal() );
    }

    #[NoReturn]
    public function show_purchase_order_modal(): void {
        $this->send( $this->get_adapter()->show_purchase_order_modal( $this->get_request() ) );
    }

    #[NoReturn]
    public function set_purchase_order(): void {
        $this->send( $this->get_adapter()->set_purchase_order( $this->get_request() ) );
    }

    /**
     * Get merged request parameters with POST precedence.
     *
     * @return array
     */
    public function get_request(): array {
        return array_merge( $_GET, $_POST );
    }

    protected function get_adapter(): Finance_Ajax_Adapter {
        if ( $this->adapter !== null ) {
            return $this->adapter;
        }

        $c = $this->racketmanager->container;
        if ( $c->has( 'finance_ajax_adapter' ) ) {
            return $c->get( 'finance_ajax_adapter' );
        }

        return new Finance_Ajax_Adapter(
            $c->has( 'security_service' ) ? $c->get( 'security_service' ) : new Security_Service(),
            $c->has( 'json_response_factory' ) ? $c->get( 'json_response_factory' ) : new Json_Response_Factory(),
            $c->get( 'finance_service' )
        );
    }

    #[NoReturn]
    private function send( $response ): void {
        $response->send();
        wp_die();
    }
}
