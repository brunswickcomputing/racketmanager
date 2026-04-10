<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Fixture;

use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface;
use Racketmanager\Domain\Result\Result;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Domain\Enums\Fixture\Fixture_Update_Status;
use Racketmanager\Domain\Results_Checker;

use Racketmanager\Repositories\Interfaces\Results_Report_Repository_Interface;
use Racketmanager\Domain\Results_Report;

/**
 * Service for fixture maintenance tasks like chasing missing results and auto-confirming.
 */
class Fixture_Maintenance_Service {

    private League_Repository_Interface $league_repository;
    private Results_Checker_Repository_Interface $results_checker_repository;
    private Results_Report_Repository_Interface $results_report_repository;
    private Notification_Service $notification_service;
    private Settings_Service $settings_service;
    private Fixture_Result_Manager $fixture_result_manager;

    public function __construct(
        Service_Provider $service_provider,
        Repository_Provider $repository_provider,
        Fixture_Result_Manager $fixture_result_manager
    ) {
        $this->league_repository          = $repository_provider->get_league_repository();
        $this->results_checker_repository = $repository_provider->get_results_checker_repository();
        $this->results_report_repository  = $repository_provider->get_results_report_repository();
        $this->notification_service       = $service_provider->get_notification_service();
        $this->settings_service           = $service_provider->get_settings_service();
        $this->fixture_result_manager     = $fixture_result_manager;
    }

    /**
     * Chase match results for a fixture.
     *
     * @param Fixture $fixture
     * @param false|string $time_period
     * @param string|null $timeout
     * @param string|null $penalty
     *
     * @return bool
     */
    public function chase_match_result( Fixture $fixture, false|string $time_period = false, ?string $timeout = null, ?string $penalty = null ): bool {
        $league = $this->league_repository->find_by_id( (int) $fixture->get_league_id() );
        if ( ! $league ) {
            return false;
        }

        $competition_type = $league->event->competition->type;
        $admin_email      = $this->settings_service->get_option( $competition_type, 'resultConfirmationEmail' );
        if ( empty( $admin_email ) ) {
            return false;
        }

        $args = [
            'time_period' => $time_period,
            'timeout'     => $timeout,
            'penalty'     => $penalty,
            'from_email'  => $admin_email,
        ];

        $this->notification_service->send_chase_result_notification( $fixture, $args );

        return true;
    }

    /**
     * Complete match result for a fixture.
     *
     * @param Fixture $fixture
     * @param int $confirmation_timeout
     *
     * @return int
     */
    public function complete_result( Fixture $fixture, int $confirmation_timeout ): int {
        $this->chase_match_approval( $fixture, (string) $confirmation_timeout, true );

        $result = new Result(
            home_points: (float) $fixture->get_home_points(),
            away_points: (float) $fixture->get_away_points(),
            status: $fixture->get_status(),
            sets: [],
            custom: $fixture->get_custom() ? : []
        );

        $response = $this->fixture_result_manager->confirm_result( $fixture, 'system', null, $result );

        return $response->has_outcome( Fixture_Update_Status::SAVED ) || $response->has_outcome( Fixture_Update_Status::TABLE_UPDATED ) ? 1 : 0;
    }

    /**
     * Chase match approval for a fixture.
     *
     * @param Fixture $fixture
     * @param false|string $time_period
     * @param bool $override
     * @param string|null $timeout
     * @param string|null $penalty
     *
     * @return bool
     */
    public function chase_match_approval( Fixture $fixture, false|string $time_period = false, bool $override = false, ?string $timeout = null, ?string $penalty = null ): bool {
        $league = $this->league_repository->find_by_id( (int) $fixture->get_league_id() );
        if ( ! $league ) {
            return false;
        }

        $competition_type = $league->event->competition->type;
        $admin_email      = $this->settings_service->get_option( $competition_type, 'resultConfirmationEmail' );
        if ( empty( $admin_email ) ) {
            return false;
        }

        $args = [
            'time_period' => $time_period,
            'timeout'     => $timeout,
            'penalty'     => $penalty,
            'from_email'  => $admin_email,
        ];

        if ( $override ) {
            $args['msg_end'] = 'complete';
        }

        $this->notification_service->send_chase_approval_notification( $fixture, $args );

        return true;
    }

    /**
     * Check if the result entry for a fixture has timed out.
     *
     * @param Fixture $fixture
     */
    public function check_result_timeout( Fixture $fixture ): void {
        $league = $this->league_repository->find_by_id( (int) $fixture->get_league_id() );
        if ( ! $league || empty( $league->event->competition->rules['resultTimeout'] ) ) {
            return;
        }

        $competition_type = $league->event->competition->type;
        $result_timeout   = $this->settings_service->get_option( $competition_type, 'resultTimeout' );

        if ( $result_timeout && ! empty( $fixture->get_date_result_entered() ) ) {
            $date_result_entered = date_create( $fixture->get_date_result_entered() );
            $match_date          = date_create( $fixture->get_date() );
            $diff                = date_diff( $date_result_entered, $match_date );
            if ( $diff->invert ) {
                $time_diff = $diff->days * 24 * 60;
                $time_diff += $diff->h * 60;
                $time_diff += $diff->i;
                $timeout   = $result_timeout * 60;
                if ( $time_diff > $timeout ) {
                    $time_diff_hours = $time_diff / 60;
                    /* translators: %d: number of hours */
                    $reason = sprintf( __( 'Result entered %d hours after match', 'racketmanager' ), $time_diff_hours );

                    $checker              = new Results_Checker();
                    $checker->league_id   = (int) $fixture->get_league_id();
                    $checker->match_id    = (int) $fixture->get_id();
                    $checker->team_id     = (int) $fixture->get_home_team();
                    $checker->description = $reason;

                    $this->results_checker_repository->save( $checker );
                }
            }
        }
    }

    /**
     * Delete results checker entries for a fixture.
     *
     * @param int $fixture_id
     */
    public function delete_result_checks( int $fixture_id ): void {
        $this->results_checker_repository->delete_by_fixture_id( $fixture_id );
    }

    /**
     * Delete results report for a fixture.
     *
     * @param int $fixture_id
     */
    public function delete_result_report( int $fixture_id ): void {
        $this->results_report_repository->delete_by_fixture_id( $fixture_id );
    }

    /**
     * Save a results report for a fixture.
     *
     * @param int $fixture_id
     * @param object $data
     */
    public function save_result_report( int $fixture_id, object $data ): void {
        $report = new Results_Report();
        $report->match_id = $fixture_id;
        $report->data = $data;
        $report->result_object = wp_json_encode( $data );
        
        $this->results_report_repository->save( $report );
    }
}
