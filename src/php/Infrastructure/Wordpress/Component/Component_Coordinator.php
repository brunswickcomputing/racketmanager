<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Component;

use Racketmanager\Infrastructure\Wordpress\Ajax\Ajax_Registry;
use Racketmanager\Infrastructure\Wordpress\Export\Export_Delivery_Adapter;
use Racketmanager\Public\Shortcodes;
use Racketmanager\RacketManager;
use Racketmanager\Rest\Rest_Routes;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Login;
use Racketmanager\Services\Rewrites;

/**
 * Coordinates frontend component resolution, REST route setup, and AJAX action registrations.
 */
final readonly class Component_Coordinator implements Component_Coordinator_Interface {

    public function __construct(
        private Simple_Container $container,
        private Ajax_Registry $ajax_registry
    ) {}

    /**
     * Initialize all components, AJAX actions, REST routes, and shortcode adapters.
     *
     * @param RacketManager $app
     */
    public function init_components( RacketManager $app ): void {
        $app->shortcodes             = new Shortcodes( $app );
        $app->shortcodes_general     = $this->container->get( 'general_shortcode_adapter' );
        $app->shortcodes_club        = $this->container->get( 'club_shortcode_adapter' );
        $app->shortcodes_competition = $this->container->get( 'competition_shortcode_adapter' );
        $app->shortcodes_email       = $this->container->get( 'email_shortcode_adapter' );
        $app->shortcodes_event       = $this->container->get( 'event_shortcode_adapter' );
        $app->shortcodes_league      = $this->container->get( 'league_shortcode_adapter' );
        $app->shortcodes_login       = $this->container->get( 'login_shortcode_adapter' );
        $app->shortcodes_match       = $this->container->get( 'matches_shortcode_adapter' );
        $app->shortcodes_message     = $this->container->get( 'message_shortcode_adapter' );
        $app->shortcodes_tournament  = $this->container->get( 'tournament_shortcode_adapter' );
        $app->rewrites               = new Rewrites();
        $app->login                  = $this->container->get( 'login' );

        Rest_Routes::single( $app );
        $this->register_ajax_actions();
        $this->handle_exports( $app );
    }

    /**
     * Register AJAX actions using the centralized registry.
     */
    private function register_ajax_actions(): void {
        $registry   = $this->ajax_registry;
        $controller = $this->container->get( 'fixture_ajax_controller' );

        $registry->register( 'match_card', [ $controller, 'print_match_card' ], true );
        $registry->register_with_unauthenticated( 'match_rubber_status', [ $controller, 'rubber_status_options' ], [ $controller, 'logged_out_modal' ] );
        $registry->register_with_unauthenticated( 'set_rubber_status', [ $controller, 'set_rubber_status' ], [ $controller, 'logged_out' ] );
        $registry->register_with_unauthenticated( 'match_status', [ $controller, 'fixture_status_options' ], [ $controller, 'logged_out_modal' ] );
        $registry->register_with_unauthenticated( 'set_fixture_status', [ $controller, 'set_fixture_status' ], [ $controller, 'logged_out' ] );
        $registry->register_with_unauthenticated( 'match_option', [ $controller, 'show_fixture_option' ], [ $controller, 'logged_out_modal' ] );
        $registry->register_with_unauthenticated( 'set_fixture_date', [ $controller, 'set_fixture_date' ], [ $controller, 'logged_out' ] );
        $registry->register_with_unauthenticated( 'switch_home_away', [ $controller, 'switch_home_away' ], [ $controller, 'logged_out' ] );
        $registry->register_with_unauthenticated( 'reset_fixture_result', [ $controller, 'reset_fixture_result' ], [ $controller, 'logged_out' ] );
        $registry->register( 'update_fixture_header', [ $controller, 'update_fixture_header' ] );
        $registry->register_with_unauthenticated( 'update_match', [ $controller, 'update_fixture_result' ], [ $controller, 'logged_out' ] );
        $registry->register_with_unauthenticated( 'update_rubbers', [ $controller, 'update_team_match' ], [ $controller, 'logged_out' ] );

        $this->container->get( 'club_ajax_controller' )->register_hooks( $registry );
        $this->container->get( 'tournament_ajax_controller' )->register_hooks( $registry );
        $this->container->get( 'account_ajax_controller' )->register_hooks( $registry );
        $this->container->get( 'finance_ajax_controller' )->register_hooks( $registry );
        $this->container->get( 'frontend_ajax_controller' )->register_hooks( $registry );
    }

    /**
     * Handle export requests if present in query parameters.
     *
     * @param RacketManager $app
     */
    public function handle_exports( RacketManager $app ): void {
        if ( isset( $_GET['racketmanager_export'] ) ) {
            $controller = $this->container->has( 'export_delivery_adapter' )
                ? $this->container->get( 'export_delivery_adapter' )
                : new Export_Delivery_Adapter( $app );
            $controller->handle_export();
        }
    }
}
