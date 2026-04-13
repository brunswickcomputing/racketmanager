<?php

namespace Racketmanager\Admin\Controllers;

use JetBrains\PhpStorm\NoReturn;
use Racketmanager\RacketManager;
use Racketmanager\Services\Export\DTO\Export_Criteria;
use Racketmanager\Services\Export\Formatters\Export_Formatter_Interface;
use Racketmanager\Services\Exporter;

/**
 * Class Export_Admin_Controller
 *
 * @package Racketmanager\Admin\Controllers
 */
class Export_Admin_Controller {

    private RacketManager $racketmanager;
    private Exporter $exporter;

    /**
     * Export_Admin_Controller constructor.
     *
     * @param RacketManager $racketmanager
     */
    public function __construct( RacketManager $racketmanager ) {
        $this->racketmanager = $racketmanager;
        $this->exporter      = $this->racketmanager->container->get( 'exporter' );
    }

    /**
     * Handle export request.
     */
    public function handle_export(): void {
        if ( ! isset( $_GET['racketmanager_export'] ) ) {
            return;
        }

        // Only enforce nonce for admin-side triggers if we are in admin.
        // Public calendar exports might not have nonce.
        if ( is_admin() ) {
            check_admin_referer( 'racketmanager_export_nonce' );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'racketmanager' ) );
        }

        $criteria = $this->prepare_criteria_from_globals();
        $type     = sanitize_text_field( wp_unslash( $_GET['racketmanager_export'] ) );

        $content      = '';
        $filename     = 'export';
        $content_type = 'text/plain';

        switch ( $type ) {
            case 'calendar':
                $content      = $this->exporter->calendar( $criteria );
                $content_type = Export_Formatter_Interface::CONTENT_TYPE_ICS;
                $filename     = 'calendar.ics';
                break;
            case 'fixtures':
                $content = $this->exporter->fixtures( $criteria );
                if ( 'csv' === $criteria->format ) {
                    $content_type = Export_Formatter_Interface::CONTENT_TYPE_CSV;
                    $filename     = 'fixtures.csv';
                } else {
                    $content_type = Export_Formatter_Interface::CONTENT_TYPE_JSON;
                    $filename     = 'fixtures.json';
                }
                break;
            case 'results':
                $content = $this->exporter->results( $criteria );
                if ( 'csv' === $criteria->format ) {
                    $content_type = Export_Formatter_Interface::CONTENT_TYPE_CSV;
                    $filename     = 'results.csv';
                } else {
                    $content_type = Export_Formatter_Interface::CONTENT_TYPE_JSON;
                    $filename     = 'results.json';
                }
                break;
            case 'report_results':
                $content      = $this->exporter->report_results( $criteria );
                $content_type = Export_Formatter_Interface::CONTENT_TYPE_CSV;
                $filename     = 'report_results.csv';
                break;
            default:
                wp_die( esc_html__( 'Export function not found', 'racketmanager' ) );
        }

        $this->send_response( $content, $filename, $content_type );
    }

    /**
     * Send response to browser.
     *
     * @param string $content
     * @param string $filename
     * @param string $content_type
     */
    #[NoReturn]
    protected function send_response( string $content, string $filename, string $content_type ): void {
        if ( ! headers_sent() ) {
            header( 'Content-Type: ' . $content_type );
            header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        }
        echo $content;
        $this->terminate();
    }

    /**
     * Terminate execution.
     */
    #[NoReturn]
    protected function terminate(): void {
        exit();
    }

    /**
     * Prepare Export_Criteria from global $_GET.
     *
     * @return Export_Criteria
     */
    private function prepare_criteria_from_globals(): Export_Criteria {
        $criteria = new Export_Criteria();
        $criteria->league_id      = isset( $_GET['league_id'] ) ? (int) $_GET['league_id'] : null;
        $criteria->season         = isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : null;
        $criteria->club_id        = isset( $_GET['club_id'] ) ? (int) $_GET['club_id'] : null;
        $criteria->competition_id = isset( $_GET['competition_id'] ) ? (int) $_GET['competition_id'] : null;
        $criteria->team_id        = isset( $_GET['team_id'] ) ? (int) $_GET['team_id'] : null;
        $criteria->date_from      = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : null;
        $criteria->date_to        = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : null;
        $criteria->format         = isset( $_GET['format'] ) ? sanitize_text_field( wp_unslash( $_GET['format'] ) ) : 'json';

        return $criteria;
    }
}
