<?php
/**
 * AJAX front-end club controller
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
 * WordPress entry point for club AJAX actions.
 */
class Club_Ajax_Controller {
    public function __construct(
        private RacketManager $racketmanager,
        private ?Club_Ajax_Adapter $adapter = null
    ) {}

    public function register_hooks( Ajax_Registry $registry ): void {
        $registry->register_with_unauthenticated( 'update_club', array( $this, 'update_club' ), array( $this, 'logged_out' ) );
        $registry->register_with_unauthenticated( 'club_player_request', array( $this, 'club_player_request' ), array( $this, 'logged_out' ) );
        $registry->register_with_unauthenticated( 'club_players_remove', array( $this, 'club_player_remove' ), array( $this, 'logged_out' ) );
        $registry->register_with_unauthenticated( 'club_role_modal', array( $this, 'show_club_role_modal' ), array( $this, 'logged_out_modal' ) );
        $registry->register_with_unauthenticated( 'set_club_role', array( $this, 'set_club_role' ), array( $this, 'logged_out_modal' ) );
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
    public function update_club(): void {
        $this->send( $this->get_adapter()->update_club( $this->get_request() ) );
    }

    #[NoReturn]
    public function club_player_request(): void {
        $this->send( $this->get_adapter()->club_player_request( $this->get_request() ) );
    }

    #[NoReturn]
    public function club_player_remove(): void {
        $this->send( $this->get_adapter()->club_player_remove( $this->get_request() ) );
    }

    #[NoReturn]
    public function show_club_role_modal(): void {
        $this->send( $this->get_adapter()->show_club_role_modal( $this->get_request() ) );
    }

    #[NoReturn]
    public function set_club_role(): void {
        $this->send( $this->get_adapter()->set_club_role( $this->get_request() ) );
    }

    /**
     * Get merged request parameters with POST precedence.
     *
     * @return array
     */
    public function get_request(): array {
        return array_merge( $_GET, $_POST );
    }

    protected function get_adapter(): Club_Ajax_Adapter {
        if ( $this->adapter !== null ) {
            return $this->adapter;
        }

        $c = $this->racketmanager->container;
        if ( $c->has( 'club_ajax_adapter' ) ) {
            return $c->get( 'club_ajax_adapter' );
        }

        return new Club_Ajax_Adapter(
            $c->has( 'security_service' ) ? $c->get( 'security_service' ) : new Security_Service(),
            $c->has( 'json_response_factory' ) ? $c->get( 'json_response_factory' ) : new Json_Response_Factory(),
            $c->get( 'club_service' ),
            $c->get( 'registration_service' ),
            $c->has( 'user_repository' ) ? $c->get( 'user_repository' ) : null,
            $c->has( 'club_role_repository' ) ? $c->get( 'club_role_repository' ) : null
        );
    }

    #[NoReturn]
    private function send( $response ): void {
        $response->send();
        wp_die();
    }
}
