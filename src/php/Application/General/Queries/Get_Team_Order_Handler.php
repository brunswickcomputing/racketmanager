<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Util\Util_Lookup;
use function __;

class Get_Team_Order_Handler {
    public function __construct(
        private readonly Club_Service $club_service,
        private readonly Event_Repository_Interface $event_repository
    ) {}

    public function handle( Get_Team_Order_Query $query ): Shortcode_View_Model {
        $clubs = $this->club_service->get_clubs( [ 'type' => 'affiliated' ] );
        if ( empty( $clubs ) ) {
            return Shortcode_View_Model::for_error( __( 'No clubs found', 'racketmanager' ) );
        }

        $events = $this->event_repository->find_all( [ 'entry_type' => 'team', 'reverse_rubbers' => true ] );
        if ( empty( $events ) ) {
            return Shortcode_View_Model::for_error( __( 'No events found', 'racketmanager' ) );
        }

        $event_types = Util_Lookup::get_event_types();
        $age_groups  = Util_Lookup::get_age_groups();

        $filename = ! empty( $query->template ) ? 'team-order-' . $query->template : 'team-order';

        return Shortcode_View_Model::for_template( $filename, [
            'clubs'       => $clubs,
            'events'      => $events,
            'event_types' => $event_types,
            'age_groups'  => $age_groups,
        ] );
    }
}
