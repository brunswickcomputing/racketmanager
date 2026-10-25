<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Fixture;

use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;

/**
 * Service orchestrating scheduled cron workflows for pending results and approvals.
 */
final readonly class Pending_Result_Orchestration_Service implements Pending_Result_Orchestrator_Interface {

    public function __construct(
        private Option_Service $option_service,
        private Fixture_Repository_Interface $fixture_repository,
        private Fixture_Maintenance_Service $fixture_maintenance_service
    ) {}

    /**
     * Chase pending results for a competition type.
     *
     * @param string $competition Competition type (e.g. 'league').
     */
    public function chase_pending_results( string $competition = 'league' ): void {
        $options        = $this->option_service->get_options( $competition );
        $result_pending = $options['resultPending'] ?? null;
        $result_penalty = $options['resultPenalty'] ?? null;
        $result_timeout = $options['resultTimeout'] ?? null;

        $match_args = [
            'time'             => 'outstanding',
            'competition_type' => $competition,
            'orderby'          => [
                'date' => 'ASC',
                'id'   => 'ASC',
            ],
            'timeOffset'       => $result_pending,
        ];

        $matches = $this->fixture_repository->find_all( $match_args );
        if ( is_array( $matches ) ) {
            foreach ( $matches as $match ) {
                $fixture = $this->fixture_repository->find_by_id( (int) $match->id );
                if ( $fixture ) {
                    $this->fixture_maintenance_service->chase_match_result( $fixture, $result_pending, $result_timeout, $result_penalty );
                }
            }
        }
    }

    /**
     * Chase pending approvals for a competition type.
     *
     * @param string $competition Competition type (e.g. 'league').
     */
    public function chase_pending_approvals( string $competition = 'league' ): void {
        $options              = $this->option_service->get_options( $competition );
        $confirmation_timeout = $options['confirmationTimeout'] ?? null;

        $match_args = [
            'confirmed'        => 'true',
            'competition_type' => $competition,
            'orderby'          => [
                'date' => 'ASC',
                'id'   => 'ASC',
            ],
            'timeOffset'       => $confirmation_timeout,
        ];

        $matches = $this->fixture_repository->find_all( $match_args );
        if ( is_array( $matches ) ) {
            foreach ( $matches as $match ) {
                $fixture = $this->fixture_repository->find_by_id( (int) $match->id );
                if ( $fixture ) {
                    $this->fixture_maintenance_service->complete_result( $fixture, (int) $confirmation_timeout );
                }
            }
        }

        $confirmation_required = $options['confirmationRequired'] ?? false;
        if ( $confirmation_required ) {
            $confirmation_pending = $options['confirmationPending'] ?? null;
            $confirmation_penalty = $options['confirmationPenalty'] ?? null;
            $confirmation_timeout = $options['confirmationTimeout'] ?? null;

            $match_args = [
                'confirmed'        => 'true',
                'competition_type' => $competition,
                'orderby'          => [
                    'updated' => 'ASC',
                    'id'      => 'ASC',
                ],
                'timeOffset'       => $confirmation_pending,
            ];

            $matches = $this->fixture_repository->find_all( $match_args );
            if ( is_array( $matches ) ) {
                foreach ( $matches as $match ) {
                    $fixture = $this->fixture_repository->find_by_id( (int) $match->id );
                    if ( $fixture ) {
                        $this->fixture_maintenance_service->chase_match_approval( $fixture, $confirmation_pending, false, $confirmation_timeout, $confirmation_penalty );
                    }
                }
            }
        }
    }
}
