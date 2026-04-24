<?php
/**
 * AJAX Front end match response methods (PSR-4 relocated)
 *
 * @package    RacketManager
 * @subpackage RacketManager_Ajax_Frontend_Match
 */

namespace Racketmanager\Ajax;

use JetBrains\PhpStorm\NoReturn;
use Racketmanager\Infrastructure\Security\Security_Service;
use Racketmanager\Infrastructure\Wordpress\Ajax\Fixture_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory;
use Racketmanager\Infrastructure\Wordpress\Response\Logging_Json_Response_Factory;
use Racketmanager\Presenters\Fixture_Presenter;
use Racketmanager\RacketManager;

/**
 * Implement AJAX front end match responses.
 *
 * @author Paul Moffat
 */
class Ajax_Fixture {
    private RacketManager $racketmanager;

    /**
     * Register ajax actions.
     *
     * @param $plugin_instance
     */
    public function __construct( $plugin_instance ) {
        $this->racketmanager = $plugin_instance;
        add_action( 'wp_ajax_racketmanager_match_card', array( &$this, 'print_match_card' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_match_card', array( &$this, 'print_match_card' ) );
        add_action( 'wp_ajax_racketmanager_match_rubber_status', array( &$this, 'match_rubber_status_options' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_match_rubber_status', array( &$this, 'logged_out_modal' ) );
        add_action( 'wp_ajax_racketmanager_set_match_rubber_status', array( &$this, 'set_match_rubber_status' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_set_match_rubber_status', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_match_status', array( &$this, 'match_status_options' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_match_status', array( &$this, 'logged_out_modal' ) );
        add_action( 'wp_ajax_racketmanager_set_match_status', array( &$this, 'set_match_status' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_set_match_status', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_match_option', array( &$this, 'show_match_option' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_match_option', array( &$this, 'logged_out_modal' ) );
        add_action( 'wp_ajax_racketmanager_set_match_date', array( &$this, 'set_match_date' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_set_match_date', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_switch_home_away', array( &$this, 'switch_home_away' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_switch_home_away', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_reset_match_result', array( &$this, 'reset_match_result' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_reset_match_result', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_update_match_header', array( &$this, 'update_match_header' ) );
        add_action( 'wp_ajax_racketmanager_update_match', array( &$this, 'update_fixture_result' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_update_match', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_update_rubbers', array( &$this, 'update_team_match' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_update_rubbers', array( &$this, 'logged_out' ) );
    }

    /**
     * Build error response for logged-out users.
     */
    #[NoReturn]
    public function logged_out(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->logged_out();
        $response->send();
        wp_die();
    }

    /**
     * Build error response for logged-out users (modal style).
     */
    #[NoReturn]
    public function logged_out_modal(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->logged_out_modal();
        $response->send();
        wp_die();
    }

    /**
     * Build screen to allow printing of match cards
     */
    #[NoReturn]
    public function print_match_card(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->print_match_card();
        $response->send();
        wp_die();
    }

    /**
     * Get the Fixture AJAX Adapter with its dependencies.
     *
     * @return Fixture_Ajax_Adapter
     */
    private function get_fixture_ajax_adapter(): Fixture_Ajax_Adapter {
        $c = $this->racketmanager->container;

        return new Fixture_Ajax_Adapter(
            $c,
            new Security_Service(),
            new Logging_Json_Response_Factory( new Json_Response_Factory() ),
            $c->get( 'fixture_detail_service' ),
            $c->get( 'view_renderer' ),
            new Fixture_Presenter( $c->get( 'fixture_link_service' ) )
        );

    }

    /**
     * Build screen to allow match status to be captured
     */
    #[NoReturn]
    public function match_status_options(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->match_status_options();
        $response->send();
        wp_die();
    }

    /**
     * Set match status
     */
    #[NoReturn]
    public function set_match_status(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->set_match_status();
        $response->send();
        wp_die();
    }

    /**
     * Build screen to show the selected match option
     */
    #[NoReturn]
    public function show_match_option(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->show_match_option();
        $response->send();
        wp_die();
    }

    /**
     * Set the match date function
     *
     * @return void
     */
    #[NoReturn]
    public function set_match_date(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->set_match_date();
        $response->send();
        wp_die();
    }

    /**
     * Switch home and away teams function
     *
     * @return void
     */
    #[NoReturn]
    public function switch_home_away(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->switch_home_away();
        $response->send();
        wp_die();
    }

    /**
     * Reset result and draw for fixture
     */
    #[NoReturn]
    public function reset_match_result(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->reset_match_result();
        $response->send();
        wp_die();
    }

    /**
     * Show rubber status options
     */
    #[NoReturn]
    public function match_rubber_status_options(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->match_rubber_status_options();
        $response->send();
        wp_die();
    }

    /**
     * Set match rubber status
     */
    #[NoReturn]
    public function set_match_rubber_status(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->set_match_rubber_status();
        $response->send();
        wp_die();
    }

    /**
     * Update match header
     */
    #[NoReturn]
    public function update_match_header(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->update_match_header();
        $response->send();
        wp_die();
    }

    /**
     * Update match details
     */
    #[NoReturn]
    public function update_fixture_result(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->update_fixture_result();
        $response->send();
        wp_die();
    }

    /**
     * Update match details for team matches only
     */
    #[NoReturn]
    public function update_team_match(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->update_team_match();
        $response->send();
        wp_die();
    }

}
