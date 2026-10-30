<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Application\General\DTOs\Team_Order_Result;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Util\Util_Lookup;
use function __;

class Get_Team_Order_Handler {
    public function __construct(
        private readonly Club_Service $club_service,
        private readonly Event_Repository_Interface $event_repository
    ) {}

    public function handle( Get_Team_Order_Query $query ): Team_Order_Result {
        $clubs = $this->club_service->get_clubs( [ 'type' => 'affiliated' ] );
        if ( empty( $clubs ) ) {
            return Team_Order_Result::error( __( 'No clubs found', 'racketmanager' ) );
        }

        $events = $this->event_repository->find_all( [ 'entry_type' => 'team', 'reverse_rubbers' => true ] );
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
