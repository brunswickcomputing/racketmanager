<?php
declare( strict_types=1 );

namespace Racketmanager\Cli;

use LogicException;
use Racketmanager\Application\Export\DTO\Export_Criteria;
use Racketmanager\Infrastructure\Wordpress\Bootstrap\Plugin_Bootstrap;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Export\Export_Service;
use WP_CLI;
use WP_CLI\ExitException;

/**
 * RacketManager Export Command
 *
 * @package Racketmanager\Cli
 */
class Export_Command {

    /**
     * Export service.
     *
     * @var Export_Service
     */
    private Export_Service $exporter;

    /**
     * Export_Command constructor.
     *
     * @param Simple_Container|null $container Active dependency injection container.
     * @param Export_Service|null   $exporter  Optional explicit exporter service.
     *
     * @throws LogicException When the container is not initialized and no exporter is provided.
     */
    public function __construct( ?Simple_Container $container = null, ?Export_Service $exporter = null ) {
        if ( null === $exporter ) {
            $container ??= Plugin_Bootstrap::container();
            if ( null === $container ) {
                throw new LogicException( 'Export_Command requires an active container or an injected Export_Service.' );
            }
            /** @var Export_Service $exporter */
            $exporter = $container->get( Export_Service::class );
        }
        $this->exporter = $exporter;
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
     * @param array $args Positional CLI arguments.
     * @param array $assoc_args Associative CLI options and flags.
     *
     * @throws ExitException Exit exception.
     */
    public function __invoke( array $args, array $assoc_args ): void {
        $type = $assoc_args['type'] ?? '';

        $criteria                 = new Export_Criteria();
        $criteria->league_id      = isset( $assoc_args['league_id'] ) ? (int) $assoc_args['league_id'] : null;
        $criteria->season         = $assoc_args['season'] ?? null;
        $criteria->club_id        = isset( $assoc_args['club_id'] ) ? (int) $assoc_args['club_id'] : null;
        $criteria->competition_id = isset( $assoc_args['competition_id'] ) ? (int) $assoc_args['competition_id'] : null;
        $criteria->team_id        = isset( $assoc_args['team_id'] ) ? (int) $assoc_args['team_id'] : null;
        $criteria->date_from      = $assoc_args['date_from'] ?? null;
        $criteria->date_to        = $assoc_args['date_to'] ?? null;
        $criteria->format         = $assoc_args['format'] ?? 'json';

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
                WP_CLI::error( 'Invalid export type: ' . $type . '. Available types: calendar, results, fixtures, report_results.' );
                return;
        }

        if ( isset( $assoc_args['output'] ) ) {
            // Direct file output is intended for CLI exports.
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            $result = file_put_contents( $assoc_args['output'], $content );
            if ( false === $result ) {
                WP_CLI::error( 'Failed to write to file: ' . $assoc_args['output'] );
                return;
            }
            WP_CLI::success( 'Export saved to ' . $assoc_args['output'] );
        } else {
            WP_CLI::log( $content );
        }
    }
}
