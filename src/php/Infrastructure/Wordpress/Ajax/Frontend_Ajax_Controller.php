<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Ajax;

use JetBrains\PhpStorm\NoReturn;
use Racketmanager\RacketManager;

/**
 * WordPress entry point for frontend AJAX actions.
 */
class Frontend_Ajax_Controller {
    public function __construct(
        private RacketManager $racketmanager,
        private ?Frontend_Ajax_Adapter $adapter = null
    ) {}

    public function register_hooks( Ajax_Registry $registry ): void {
        $registry->register_with_unauthenticated( 'update_team', [ $this, 'update_team' ], [ $this, 'logged_out' ] );
        $registry->register_with_unauthenticated( 'update_player', [ $this, 'update_player' ], [ $this, 'logged_out' ] );
        $registry->register_with_unauthenticated( 'cup_entry', [ $this, 'cup_entry' ], [ $this, 'logged_out' ] );
        $registry->register_with_unauthenticated( 'league_entry', [ $this, 'league_entry' ], [ $this, 'logged_out' ] );
        $registry->register_with_unauthenticated( 'team_edit_modal', [ $this, 'show_team_edit_modal' ], [ $this, 'logged_out_modal' ] );

        $registry->register( 'get_team_info', [ $this, 'get_team_event_info' ] );
        $registry->register( 'search_players', [ $this, 'search_players' ], true );
        $registry->register( 'get_tab_data', [ $this, 'tab_data' ], true );
        $registry->register( 'show_team_order_players', [ $this, 'show_team_order_players' ], true );
        $registry->register( 'validate_team_order', [ $this, 'validate_team_order' ], true );
        $registry->register( 'get_event_team_match_dropdown', [ $this, 'get_event_team_match_dropdown' ], true );
        $registry->register( 'get_player_details', [ $this, 'get_player_details' ] );
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
    public function update_team(): void {
        $this->send( $this->get_adapter()->update_team( $this->get_request() ) );
    }

    #[NoReturn]
    public function update_player(): void {
        $this->send( $this->get_adapter()->update_player( $this->get_request() ) );
    }

    #[NoReturn]
    public function get_team_event_info(): void {
        $this->send( $this->get_adapter()->get_team_event_info( $this->get_request() ) );
    }

    #[NoReturn]
    public function cup_entry(): void {
        $this->send( $this->get_adapter()->cup_entry( $this->get_request() ) );
    }

    #[NoReturn]
    public function league_entry(): void {
        $this->send( $this->get_adapter()->league_entry( $this->get_request() ) );
    }

    #[NoReturn]
    public function search_players(): void {
        $this->send( $this->get_adapter()->search_players( $this->get_request() ) );
    }

    #[NoReturn]
    public function tab_data(): void {
        $this->send( $this->get_adapter()->tab_data( $this->get_request() ) );
    }

    #[NoReturn]
    public function show_team_order_players(): void {
        $this->send( $this->get_adapter()->show_team_order_players( $this->get_request() ) );
    }

    #[NoReturn]
    public function validate_team_order(): void {
        $this->send( $this->get_adapter()->validate_team_order( $this->get_request() ) );
    }

    #[NoReturn]
    public function show_team_edit_modal(): void {
        $this->send( $this->get_adapter()->show_team_edit_modal( $this->get_request() ) );
    }

    #[NoReturn]
    public function get_event_team_match_dropdown(): void {
        $this->send( $this->get_adapter()->get_event_team_match_dropdown( $this->get_request() ) );
    }

    #[NoReturn]
    public function get_player_details(): void {
        $this->send( $this->get_adapter()->get_player_details( $this->get_request() ) );
    }

    /**
     * Snapshot the current WordPress request superglobals into an array.
     *
     * @return array
     */
    public function get_request(): array {
        return array_merge( $_GET, $_POST );
    }

    protected function get_adapter(): Frontend_Ajax_Adapter {
        if ( $this->adapter !== null ) {
            return $this->adapter;
        }

        return $this->racketmanager->container->get( Frontend_Ajax_Adapter::class );
    }

    #[NoReturn]
    private function send( $response ): void {
        $response->send();
        wp_die();
    }
}
