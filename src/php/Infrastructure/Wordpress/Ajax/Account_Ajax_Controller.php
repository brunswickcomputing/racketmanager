<?php
declare( strict_types=1 );
/**
 * AJAX front-end account controller
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
 * WordPress entry point for account AJAX actions.
 */
class Account_Ajax_Controller {
    public function __construct(
        private RacketManager $racketmanager,
        private ?Account_Ajax_Adapter $adapter = null
    ) {}

    public function register_hooks( Ajax_Registry $registry ): void {
        $registry->register( 'add_favourite', array( $this, 'add_favourite' ) );
        $registry->register_with_unauthenticated( 'get_message', array( $this, 'get_user_message' ), array( $this, 'logged_out' ) );
        $registry->register_with_unauthenticated( 'delete_message', array( $this, 'delete_message' ), array( $this, 'logged_out' ) );
        $registry->register( 'delete_messages', array( $this, 'delete_messages' ) );
        $registry->register_unauthenticated( 'deletes_message', array( $this, 'logged_out' ) );
        $registry->register_unauthenticated( 'reset_password', array( $this, 'reset_password' ) );
        $registry->register_unauthenticated( 'login', array( $this, 'login' ) );
        $registry->register_with_unauthenticated( 'update_account', array( $this, 'update_account' ), array( $this, 'login' ) );
    }

    #[NoReturn]
    public function logged_out(): void {
        $this->send( $this->get_adapter()->logged_out() );
    }

    #[NoReturn]
    public function login(): void {
        $this->send( $this->get_adapter()->login( $this->get_request() ) );
    }

    #[NoReturn]
    public function add_favourite(): void {
        $this->send( $this->get_adapter()->add_favourite( $this->get_request() ) );
    }

    #[NoReturn]
    public function get_user_message(): void {
        $this->send( $this->get_adapter()->get_user_message( $this->get_request() ) );
    }

    #[NoReturn]
    public function delete_message(): void {
        $this->send( $this->get_adapter()->delete_message( $this->get_request() ) );
    }

    #[NoReturn]
    public function delete_messages(): void {
        $this->send( $this->get_adapter()->delete_messages( $this->get_request() ) );
    }

    #[NoReturn]
    public function reset_password(): void {
        $this->send( $this->get_adapter()->reset_password( $this->get_request() ) );
    }

    #[NoReturn]
    public function update_account(): void {
        $this->send( $this->get_adapter()->update_account( $this->get_request() ) );
    }

    /**
     * Get merged request parameters with POST precedence.
     *
     * @return array
     */
    public function get_request(): array {
        return array_merge( $_GET, $_POST );
    }

    protected function get_adapter(): Account_Ajax_Adapter {
        if ( $this->adapter !== null ) {
            return $this->adapter;
        }

        $c = $this->racketmanager->container;
        if ( $c->has( 'account_ajax_adapter' ) ) {
            return $c->get( 'account_ajax_adapter' );
        }

        return new Account_Ajax_Adapter(
            $c->has( 'security_service' ) ? $c->get( 'security_service' ) : new Security_Service(),
            $c->has( 'json_response_factory' ) ? $c->get( 'json_response_factory' ) : new Json_Response_Factory()
        );
    }

    #[NoReturn]
    private function send( $response ): void {
        $response->send();
        wp_die();
    }
}
