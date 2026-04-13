<?php

namespace Racketmanager\Cli;

use Racketmanager\RacketManager;
use Racketmanager\Services\Export\DTO\Export_Criteria;
use Racketmanager\Services\Exporter;
use WP_CLI;

/**
 * RacketManager Export Command
 *
 * @package Racketmanager\Cli
 */
class Export_Command {

    private Exporter $exporter;

    /**
     * Export_Command constructor.
     */
    public function __construct() {
        /** @var RacketManager $racketmanager */
        global $racketmanager;
        if ( ! $racketmanager ) {
            $racketmanager = RacketManager::get_instance();
        }
        $this->exporter = $racketmanager->container->get( 'exporter' );
    }

    /**
     * Export data.
     *
     * ## OPTIONS
     *
     * --type=<type>
     * : The type of export (calendar, results, fixtures, report_results).
     *
     * [--league_id=<league_id>]
     * : League ID.
     *
     * [--season=<season>]
     * : Season year.
     *
     * [--club_id=<club_id>]
     * : Club ID.
     *
     * [--competition_id=<competition_id>]
     * : Competition ID.
     *
     * [--team_id=<team_id>]
     * : Team ID.
     *
     * [--date_from=<date_from>]
     * : Start date (YYYY-MM-DD).
     *
     * [--date_to=<date_to>]
     * : End date (YYYY-MM-DD).
     *
     * [--format=<format>]
     * : Format (json, csv). Default: JSON.
     *
     * [--output=<output>]
     * : Output file path. If not provided, it will output to STDOUT.
     *
     * ## EXAMPLES
     *
     *     wp racketmanager export --type=results --league_id=1 --format=csv
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function __invoke( array $args, array $assoc_args ): void {
        $type = $assoc_args['type'] ?? '';

        $criteria = new Export_Criteria();
        $criteria->league_id      = isset( $assoc_args['league_id'] ) ? (int) $assoc_args['league_id'] : null;
        $criteria->season         = $assoc_args['season'] ?? null;
        $criteria->club_id        = isset( $assoc_args['club_id'] ) ? (int) $assoc_args['club_id'] : null;
        $criteria->competition_id = isset( $assoc_args['competition_id'] ) ? (int) $assoc_args['competition_id'] : null;
        $criteria->team_id        = isset( $assoc_args['team_id'] ) ? (int) $assoc_args['team_id'] : null;
        $criteria->date_from      = $assoc_args['date_from'] ?? null;
        $criteria->date_to        = $assoc_args['date_to'] ?? null;
        $criteria->format         = $assoc_args['format'] ?? 'json';

        $content = '';
        switch ( $type ) {
            case 'calendar':
                $content = $this->exporter->calendar( $criteria );
                break;
            case 'fixtures':
                $content = $this->exporter->fixtures( $criteria );
                break;
            case 'results':
                $content = $this->exporter->results( $criteria );
                break;
            case 'report_results':
                $content = $this->exporter->report_results( $criteria );
                break;
            default:
                WP_CLI::error( "Invalid export type: $type. Available types: calendar, results, fixtures, report_results." );
        }

        if ( isset( $assoc_args['output'] ) ) {
            $result = file_put_contents( $assoc_args['output'], $content );
            if ( false === $result ) {
                WP_CLI::error( "Failed to write to file: " . $assoc_args['output'] );
            }
            WP_CLI::success( "Export saved to " . $assoc_args['output'] );
        } else {
            WP_CLI::log( $content );
        }
    }
}
