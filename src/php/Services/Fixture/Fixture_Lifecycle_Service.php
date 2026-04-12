<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Fixture;

use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Services\Notification\Notification_Service;

/**
 * Service to handle higher-level fixture lifecycle events and notifications.
 */
class Fixture_Lifecycle_Service {

    private Fixture_Repository_Interface $fixture_repository;
    private Rubber_Repository_Interface $rubber_repository;
    private Fixture_Maintenance_Service $maintenance_service;
    private Notification_Service $notification_service;

    public function __construct(
        Repository_Provider $repository_provider,
        Fixture_Maintenance_Service $maintenance_service,
        Notification_Service $notification_service
    ) {
        $this->fixture_repository   = $repository_provider->get_fixture_repository();
        $this->rubber_repository    = $repository_provider->get_rubber_repository();
        $this->maintenance_service  = $maintenance_service;
        $this->notification_service = $notification_service;
    }

    /**
     * Reschedule a fixture and handle associated updates and notifications.
     *
     * @param Fixture $fixture
     * @param string $new_date
     * @param string|null $original_date
     * @return bool
     */
    public function reschedule_fixture( Fixture $fixture, string $new_date, ?string $original_date = null ): bool {
        $success = $this->maintenance_service->update_fixture_date( (int) $fixture->get_id(), $new_date, $original_date );

        if ( $success ) {
            $fixture->set_date( $new_date );
            if ( null !== $original_date ) {
                $fixture->set_date_original( $original_date );
            }

            // Update rubber dates
            $this->rubber_repository->update_date_by_fixture_id( (int) $fixture->get_id(), $new_date );

            // Send notification
            $this->notification_service->send_date_change_notification( $fixture );
        }

        return $success;
    }

    /**
     * Advance teams in a knockout competition and notify them.
     *
     * @param Fixture $fixture
     * @return void
     */
    public function advance_teams( Fixture $fixture ): void {
        if ( -1 === (int) $fixture->get_home_team() || -1 === (int) $fixture->get_away_team() ) {
            return;
        }

        $this->notification_service->send_next_fixture_notification( $fixture );
    }

    /**
     * Handle team withdrawal and notify opponent.
     *
     * @param Fixture $fixture
     * @param int $team_id
     * @return void
     */
    public function handle_withdrawal( Fixture $fixture, int $team_id ): void {
        $this->notification_service->notify_team_withdrawal( $fixture, $team_id );
    }
}
