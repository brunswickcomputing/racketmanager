<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Application\Club\Ports\Club_Service_Interface;
use Racketmanager\Application\General\DTOs\Team_Order_Result;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Util\Util_Lookup;
use function __;

/**
 * Handles retrieval of data required to build the team order view.
 *
 * Loads affiliated clubs and eligible team events, returning an error result when
 * either dependency provides no matching records. On success, includes lookup data
 * for event types and age groups together with the requested template.
 */
readonly class Get_Team_Order_Handler {
    /**
     * Initialises the service with required club and event dependencies.
     *
     * @param Club_Service_Interface     $club_service Service used to handle club-related operations.
     * @param Event_Repository_Interface $event_repository Repository used to access event data.
     *
     * @return void
     */
    public function __construct(
        private Club_Service_Interface $club_service,
        private Event_Repository_Interface $event_repository
    ) {}

    /**
     * Handles a team order query by retrieving affiliated clubs, team events, and lookup data.
     *
     * @param Get_Team_Order_Query $query Query containing the template and request context for the team order result.
     *
     * @return Team_Order_Result Result containing clubs, events, event types, age groups, and template data, or an error result when required data is unavailable.
     */
    public function handle( Get_Team_Order_Query $query ): Team_Order_Result {
        $clubs = $this->club_service->get_clubs( [ 'type' => 'affiliated' ] );
        if ( empty( $clubs ) ) {
            return Team_Order_Result::error( __( 'No clubs found', 'racketmanager' ) );
        }

        $events = $this->event_repository->find_all(
            [
                'entry_type'      => 'team',
                'reverse_rubbers' => true,
            ]
        );
        if ( empty( $events ) ) {
            return Team_Order_Result::error( __( 'No events found', 'racketmanager' ) );
        }

        $event_types = Util_Lookup::get_event_types();
        $age_groups  = Util_Lookup::get_age_groups();

        return new Team_Order_Result(
            clubs: $clubs,
            events: $events,
            event_types: $event_types,
            age_groups: $age_groups,
            template: $query->template
        );
    }
}
