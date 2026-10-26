<?php
declare( strict_types=1 );
/**
 * AJAX front-end tournament controller
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
 * WordPress entry point for tournament AJAX actions.
 */
class Tournament_Ajax_Controller {
    public function __construct(
        private RacketManager $racketmanager,
        private ?Tournament_Ajax_Adapter $adapter = null
    ) {}

    public function get_request(): array {
        return array_merge( $_GET, $_POST );
    }

    public function register_hooks( Ajax_Registry $registry ): void {
        $registry->register_with_unauthenticated( 'tournament_entry', array( $this, 'tournament_entry_request' ), array( $this, 'logged_out' ) );
        $registry->register_with_unauthenticated( 'tournament_payment_create', array( $this, 'tournament_payment_create' ), array( $this, 'logged_out' ) );
        $registry->register_with_unauthenticated( 'tournament_withdrawal', array( $this, 'tournament_withdrawal' ), array( $this, 'logged_out_modal' ) );
        $registry->register_with_unauthenticated( 'confirm_tournament_withdrawal', array( $this, 'confirm_tournament_withdrawal' ), array( $this, 'logged_out_modal' ) );
        $registry->register_with_unauthenticated( 'team_partner', array( $this, 'team_partner' ), array( $this, 'logged_out_modal' ) );
        $registry->register_with_unauthenticated( 'validate_partner', array( $this, 'validate_partner' ), array( $this, 'logged_out_modal' ) );
    }

    #[NoReturn]
    public function logged_out(): void {
        $this->send( $this->get_adapter()->logged_out() );
    }

    #[NoReturn]
    public function logged_out_modal(): void {
        $this->send( $this->get_adapter()->logged_out_modal() );
    }

    #[NoReturn]
    public function tournament_entry_request(): void {
        $this->send( $this->get_adapter()->tournament_entry_request( $this->get_request() ) );
    }

    #[NoReturn]
    public function tournament_payment_create(): void {
        $this->send( $this->get_adapter()->tournament_payment_create( $this->get_request() ) );
    }

    #[NoReturn]
    public function tournament_withdrawal(): void {
        $this->send( $this->get_adapter()->tournament_withdrawal( $this->get_request() ) );
    }

    #[NoReturn]
    public function confirm_tournament_withdrawal(): void {
        $this->send( $this->get_adapter()->confirm_tournament_withdrawal( $this->get_request() ) );
    }

    #[NoReturn]
    public function team_partner(): void {
        $this->send( $this->get_adapter()->team_partner( $this->get_request() ) );
    }

    #[NoReturn]
    public function validate_partner(): void {
        $this->send( $this->get_adapter()->validate_partner( $this->get_request() ) );
    }

    protected function get_adapter(): Tournament_Ajax_Adapter {
        if ( $this->adapter !== null ) {
            return $this->adapter;
        }

        $c = $this->racketmanager->container;
        if ( $c->has( 'tournament_ajax_adapter' ) ) {
            return $c->get( 'tournament_ajax_adapter' );
        }

        return new Tournament_Ajax_Adapter(
            $c->has( 'security_service' ) ? $c->get( 'security_service' ) : new Security_Service(),
            $c->has( 'json_response_factory' ) ? $c->get( 'json_response_factory' ) : new Json_Response_Factory(),
            $c->get( 'competition_entry_service' ),
            $c->get( 'finance_service' )
        );
    }

    #[NoReturn]
    private function send( $response ): void {
        $response->send();
        wp_die();
    }
}
