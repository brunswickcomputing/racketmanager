<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Ajax;

use JetBrains\PhpStorm\NoReturn;
use Racketmanager\Infrastructure\Security\Security_Service;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory;
use Racketmanager\RacketManager;

/**
 * WordPress entry point for admin AJAX actions.
 */
class Admin_Ajax_Controller {
    public function __construct(
        private RacketManager $racketmanager,
        private ?Admin_Ajax_Adapter $adapter = null
    ) {}

    public function register_hooks( Ajax_Registry $registry ): void {
        $registry->register( 'save_add_points', [ $this, 'save_add_points' ] );
        $registry->register( 'insert_home_stadium', [ $this, 'insert_home_stadium' ] );
        $registry->register( 'get_event_dropdown', [ $this, 'get_event_dropdown' ] );
        $registry->register( 'get_league_dropdown', [ $this, 'get_league_dropdown' ] );
        $registry->register( 'get_season_dropdown', [ $this, 'set_season_dropdown' ] );
        $registry->register( 'get_match_dropdown', [ $this, 'set_match_dropdown' ] );
        $registry->register( 'check_team_exists', [ $this, 'check_team_exists' ] );
        $registry->register( 'email_constitution', [ $this, 'email_constitution' ] );
        $registry->register( 'notify_competition_entries_open', [ $this, 'notify_competition_entries_open' ] );
        $registry->register( 'notify_tournament_entries_open', [ $this, 'notify_tournament_entries_open' ] );
        $registry->register( 'notify_teams', [ $this, 'notify_teams_fixture' ] );
        $registry->register( 'set_tournament_dates', [ $this, 'set_tournament_dates' ] );
        $registry->register( 'send_fixtures', [ $this, 'send_fixtures' ] );
    }

    #[NoReturn]
    public function save_add_points(): void {
        $this->send( $this->get_adapter()->save_add_points( $this->get_request() ) );
    }

    #[NoReturn]
    public function insert_home_stadium(): void {
        $this->send( $this->get_adapter()->insert_home_stadium( $this->get_request() ) );
    }

    #[NoReturn]
    public function get_event_dropdown(): void {
        $this->send( $this->get_adapter()->get_event_dropdown( $this->get_request() ) );
    }

    #[NoReturn]
    public function get_league_dropdown(): void {
        $this->send( $this->get_adapter()->get_league_dropdown( $this->get_request() ) );
    }

    #[NoReturn]
    public function set_season_dropdown(): void {
        $this->send( $this->get_adapter()->set_season_dropdown( $this->get_request() ) );
    }

    #[NoReturn]
    public function set_match_dropdown(): void {
        $this->send( $this->get_adapter()->set_match_dropdown( $this->get_request() ) );
    }

    #[NoReturn]
    public function check_team_exists(): void {
        $this->send( $this->get_adapter()->check_team_exists( $this->get_request() ) );
    }

    #[NoReturn]
    public function email_constitution(): void {
        $this->send( $this->get_adapter()->email_constitution( $this->get_request() ) );
    }

    #[NoReturn]
    public function notify_competition_entries_open(): void {
        $this->send( $this->get_adapter()->notify_competition_entries_open( $this->get_request() ) );
    }

    #[NoReturn]
    public function notify_tournament_entries_open(): void {
        $this->send( $this->get_adapter()->notify_tournament_entries_open( $this->get_request() ) );
    }

    #[NoReturn]
    public function notify_teams_fixture(): void {
        $this->send( $this->get_adapter()->notify_teams_fixture( $this->get_request() ) );
    }

    #[NoReturn]
    public function set_tournament_dates(): void {
        $this->send( $this->get_adapter()->set_tournament_dates( $this->get_request() ) );
    }

    #[NoReturn]
    public function send_fixtures(): void {
        $this->send( $this->get_adapter()->send_fixtures( $this->get_request() ) );
    }

    /**
     * Snapshot the current WordPress request superglobals into an array.
     *
     * @return array
     */
    public function get_request(): array {
        return array_merge( $_GET, $_POST );
    }

    protected function get_adapter(): Admin_Ajax_Adapter {
        if ( $this->adapter !== null ) {
            return $this->adapter;
        }

        $c = $this->racketmanager->container;
        if ( $c->has( 'admin_ajax_adapter' ) ) {
            return $c->get( 'admin_ajax_adapter' );
        }

        return new Admin_Ajax_Adapter(
            $c->has( 'security_service' ) ? $c->get( 'security_service' ) : new Security_Service(),
            $c->has( 'json_response_factory' ) ? $c->get( 'json_response_factory' ) : new Json_Response_Factory(),
            $this->racketmanager,
            $c->get( 'competition_service' ),
            $c->get( 'competition_entry_service' ),
            $c->get( 'notification_service' ),
            $c->get( 'fixture_repository' ),
            $c->has( 'settings_service' ) ? $c->get( 'settings_service' ) : new \Racketmanager\Services\Settings_Service(),
            null,
            $c->get( 'competition_notification_service' ),
            $c->get( 'standings_service' ),
            $c->get( 'league_repository' ),
            $c->get( 'event_repository' ),
            $c->get( 'team_repository' )
        );
    }

    #[NoReturn]
    private function send( $response ): void {
        $response->send();
        wp_die();
    }
}
