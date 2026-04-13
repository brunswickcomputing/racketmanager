<?php

namespace Racketmanager\Rest;

use Racketmanager\RacketManager;
use Racketmanager\Services\Export\DTO\Export_Criteria;
use Racketmanager\Services\Export\Formatters\Export_Formatter_Interface;
use Racketmanager\Services\Exporter;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class Export_Controller
 *
 * @package Racketmanager\Rest
 */
class Export_Controller extends WP_REST_Controller {

    private RacketManager $racketmanager;
    private Exporter $exporter;
    protected $namespace;
    protected $rest_base;

    /**
     * Export_Controller constructor.
     *
     * @param RacketManager $plugin_instance
     */
    public function __construct( RacketManager $plugin_instance ) {
        $this->racketmanager = $plugin_instance;
        $this->exporter      = $this->racketmanager->container->get( 'exporter' );
        $this->namespace     = 'racketmanager/v1';
        $this->rest_base     = 'export';
    }

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes(): void {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/calendar',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_calendar' ),
                    'permission_callback' => array( $this, 'get_item_permissions_check' ),
                    'args'                => $this->get_export_args(),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/results',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_results' ),
                    'permission_callback' => array( $this, 'get_item_permissions_check' ),
                    'args'                => $this->get_export_args(),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/fixtures',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_fixtures' ),
                    'permission_callback' => array( $this, 'get_item_permissions_check' ),
                    'args'                => $this->get_export_args(),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/report-results',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_report_results' ),
                    'permission_callback' => array( $this, 'get_item_permissions_check' ),
                    'args'                => $this->get_export_args(),
                ),
            )
        );

        add_filter( 'rest_pre_serve_request', array( $this, 'serve_raw_export' ), 10, 4 );
    }

    /**
     * Check if a given request has access to export data.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return bool
     */
    public function get_item_permissions_check( $request ): bool {
        return true;
    }

    /**
     * Get calendar export.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_calendar( WP_REST_Request $request ): WP_REST_Response {
        $criteria = $this->prepare_criteria_from_request( $request );
        $content  = $this->exporter->calendar( $criteria );

        return $this->prepare_export_response( $content, 'calendar.ics', Export_Formatter_Interface::CONTENT_TYPE_ICS );
    }

    /**
     * Get result export.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_results( WP_REST_Request $request ): WP_REST_Response {
        $criteria = $this->prepare_criteria_from_request( $request );
        $content  = $this->exporter->results( $criteria );

        $extension = 'csv' === $criteria->format ? 'csv' : 'json';
        $mime_type = 'csv' === $criteria->format ? Export_Formatter_Interface::CONTENT_TYPE_CSV : Export_Formatter_Interface::CONTENT_TYPE_JSON;

        return $this->prepare_export_response( $content, 'results.' . $extension, $mime_type );
    }

    /**
     * Get the fixture export.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_fixtures( WP_REST_Request $request ): WP_REST_Response {
        $criteria = $this->prepare_criteria_from_request( $request );
        $content  = $this->exporter->fixtures( $criteria );

        $extension = 'csv' === $criteria->format ? 'csv' : 'json';
        $mime_type = 'csv' === $criteria->format ? Export_Formatter_Interface::CONTENT_TYPE_CSV : Export_Formatter_Interface::CONTENT_TYPE_JSON;

        return $this->prepare_export_response( $content, 'fixtures.' . $extension, $mime_type );
    }

    /**
     * Get report results export.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_report_results( WP_REST_Request $request ): WP_REST_Response {
        $criteria = $this->prepare_criteria_from_request( $request );
        $content  = $this->exporter->report_results( $criteria );

        return $this->prepare_export_response( $content, 'report_results.csv', Export_Formatter_Interface::CONTENT_TYPE_CSV );
    }

    /**
     * Prepare Export_Criteria from request.
     *
     * @param WP_REST_Request $request
     * @return Export_Criteria
     */
    private function prepare_criteria_from_request( WP_REST_Request $request ): Export_Criteria {
        $criteria = new Export_Criteria();
        $criteria->league_id      = $request->get_param( 'league_id' );
        $criteria->season         = $request->get_param( 'season' );
        $criteria->club_id        = $request->get_param( 'club_id' );
        $criteria->competition_id = $request->get_param( 'competition_id' );
        $criteria->team_id        = $request->get_param( 'team_id' );
        $criteria->date_from      = $request->get_param( 'date_from' );
        $criteria->date_to        = $request->get_param( 'date_to' );
        $criteria->format         = $request->get_param( 'format' ) ?: 'json';

        return $criteria;
    }

    /**
     * Prepare REST response for file export.
     *
     * @param string $content
     * @param string $filename
     * @param string $mime_type
     * @return WP_REST_Response
     */
    private function prepare_export_response( string $content, string $filename, string $mime_type ): WP_REST_Response {
        $response = new WP_REST_Response( $content, 200 );
        $response->header( 'Content-Type', $mime_type );
        $response->header( 'Content-Disposition', 'attachment; filename="' . $filename . '"' );

        return $response;
    }

    /**
     * Serve raw export content for CSV and ICS formats to prevent JSON encoding.
     *
     * @param bool $served  Whether the request has already been served.
     * @param WP_REST_Response $result  The response object.
     * @param WP_REST_Request $request The request object.
     * @param WP_REST_Server $server  The server object.
     *
     * @return bool
     */
    public function serve_raw_export( bool $served, WP_REST_Response $result, WP_REST_Request $request, WP_REST_Server $server ): bool {
        if ( str_contains( $request->get_route(), $this->namespace . '/' . $this->rest_base ) ) {
            $headers = $result->get_headers();
            $content_type = $headers['Content-Type'] ?? '';

            if ( $content_type === Export_Formatter_Interface::CONTENT_TYPE_CSV || $content_type === Export_Formatter_Interface::CONTENT_TYPE_ICS ) {
                status_header( $result->get_status() );
                $server->send_headers( $result->get_headers() );
                
                echo $result->get_data();
                return true;
            }
        }

        return $served;
    }

    /**
     * Get common export arguments.
     *
     * @return array
     */
    private function get_export_args(): array {
        return array(
            'league_id' => array(
                'description'       => __( 'League ID', 'racketmanager' ),
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
            ),
            'season' => array(
                'description'       => __( 'Season year', 'racketmanager' ),
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
            ),
            'club_id' => array(
                'description'       => __( 'Club ID', 'racketmanager' ),
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
            ),
            'competition_id' => array(
                'description'       => __( 'Competition ID', 'racketmanager' ),
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
            ),
            'team_id' => array(
                'description'       => __( 'Team ID', 'racketmanager' ),
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
            ),
            'date_from' => array(
                'description'       => __( 'Start date', 'racketmanager' ),
                'type'              => 'string',
                'format'            => 'date',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'date_to' => array(
                'description'       => __( 'End date', 'racketmanager' ),
                'type'              => 'string',
                'format'            => 'date',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'format' => array(
                'description'       => __( 'Export format', 'racketmanager' ),
                'type'              => 'string',
                'enum'              => array( 'json', 'csv' ),
                'sanitize_callback' => 'sanitize_text_field',
            ),
        );
    }
}
