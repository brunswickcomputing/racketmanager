<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Fixture\Listeners;

use Racketmanager\Domain\Fixture\Events\Fixture_Result_Finalized_Event;
use Racketmanager\Services\Result\Result_Reporting_Service;

/**
 * Listens for Fixture_Result_Finalized_Event and schedules external fixture result reporting.
 */
final class Schedule_Fixture_Report_Listener {
    public function __construct(
        private readonly ?Result_Reporting_Service $reporting_service = null,
    ) {
    }

    /**
     * Handle the event.
     *
     * @param Fixture_Result_Finalized_Event $event
     * @return void
     */
    public function __invoke( Fixture_Result_Finalized_Event $event ): void {
        if ( null === $this->reporting_service ) {
            return;
        }

        $competition_type = $event->competition_type;
        if ( ! empty( $competition_type ) ) {
            wp_schedule_single_event(
                time(),
                'racketmanager_report_fixture_result',
                [ $event->fixture_id, $competition_type ]
            );
        }
    }
}
