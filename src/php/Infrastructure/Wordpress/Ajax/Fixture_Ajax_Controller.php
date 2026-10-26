<?php
declare( strict_types=1 );
/**
 * AJAX Front end fixture controller
 *
 * @package    RacketManager
 * @subpackage Infrastructure/Wordpress/Ajax
 */

namespace Racketmanager\Infrastructure\Wordpress\Ajax;

use JetBrains\PhpStorm\NoReturn;
use Racketmanager\Application\Fixture\Queries\Get_Fixture_Details_Handler;
use Racketmanager\Infrastructure\Security\Security_Service;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory;
use Racketmanager\Infrastructure\Wordpress\Response\Logging_Json_Response_Factory;
use Racketmanager\Presentation\Presenters\Fixture_Presenter;
use Racketmanager\RacketManager;

/**
 * Implement AJAX front end fixture responses.
 *
 * @author Paul Moffat
 */
class Fixture_Ajax_Controller {
    public function __construct(
        private RacketManager $racketmanager,
        private ?Fixture_Ajax_Adapter $adapter = null
    ) {}

    public function get_request(): array {
        return array_merge( $_GET, $_POST );
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
     * Get the Fixture AJAX Adapter with its dependencies.
     *
     * @return Fixture_Ajax_Adapter
     */
    protected function get_fixture_ajax_adapter(): Fixture_Ajax_Adapter {
        if ( $this->adapter !== null ) {
            return $this->adapter;
        }

        $c = $this->racketmanager->container;
        if ( $c->has( 'fixture_ajax_adapter' ) ) {
            return $c->get( 'fixture_ajax_adapter' );
        }

        return new Fixture_Ajax_Adapter(
            $c,
            $c->has( 'security_service' ) ? $c->get( 'security_service' ) : new Security_Service(),
            new Logging_Json_Response_Factory( $c->has( 'json_response_factory' ) ? $c->get( 'json_response_factory' ) : new Json_Response_Factory() ),
            $c->get( 'fixture_detail_service' ),
            $c->get( 'view_renderer' ),
            $c->get( 'fixture_presenter' ),
            $c->get( 'get_fixture_details_handler' )
        );
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
        $response = $adapter->print_match_card( $this->get_request() );
        $response->send();
        wp_die();
    }

    /**
     * Build screen to allow fixture status to be captured
     */
    #[NoReturn]
    public function fixture_status_options(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->fixture_status_options( $this->get_request() );
        $response->send();
        wp_die();
    }

    /**
     * Set fixture status
     */
    #[NoReturn]
    public function set_fixture_status(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->set_fixture_status( $this->get_request() );
        $response->send();
        wp_die();
    }

    /**
     * Build screen to show the selected fixture option
     */
    #[NoReturn]
    public function show_fixture_option(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->show_fixture_option( $this->get_request() );
        $response->send();
        wp_die();
    }

    /**
     * Set the fixture date function
     *
     * @return void
     */
    #[NoReturn]
    public function set_fixture_date(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->set_fixture_date( $this->get_request() );
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
        $response = $adapter->switch_home_away( $this->get_request() );
        $response->send();
        wp_die();
    }

    /**
     * Reset result and draw for fixture
     */
    #[NoReturn]
    public function reset_fixture_result(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->reset_fixture_result( $this->get_request() );
        $response->send();
        wp_die();
    }

    /**
     * Show rubber status options
     */
    #[NoReturn]
    public function rubber_status_options(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->match_rubber_status_options( $this->get_request() );
        $response->send();
        wp_die();
    }

    /**
     * Set rubber status
     */
    #[NoReturn]
    public function set_rubber_status(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->set_rubber_status( $this->get_request() );
        $response->send();
        wp_die();
    }

    /**
     * Update fixture header
     */
    #[NoReturn]
    public function update_fixture_header(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->update_fixture_header( $this->get_request() );
        $response->send();
        wp_die();
    }

    /**
     * Update match details
     */
    #[NoReturn]
    public function update_fixture_result(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->update_fixture_result( $this->get_request() );
        $response->send();
        wp_die();
    }

    /**
     * Update match details for team matches only
     */
    #[NoReturn]
    public function update_team_match(): void {
        $adapter  = $this->get_fixture_ajax_adapter();
        $response = $adapter->update_team_match( $this->get_request() );
        $response->send();
        wp_die();
    }

}
