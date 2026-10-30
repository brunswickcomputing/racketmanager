<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Application\Event\DTOs\Event_Dropdown_Result;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition_Service;
use function __;

class Get_Event_Dropdown_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly Event_Query_Service $event_query_service
    ) {}

    public function handle( Get_Event_Dropdown_Query $query ): Event_Dropdown_Result {
        $identifier = $query->event_id ?? $query->event_name;
        if ( null === $identifier || '' === $identifier ) {
            return Event_Dropdown_Result::error( __( 'No event id supplied', 'racketmanager' ) );
        }

        try {
            $event = $this->competition_service->get_event_by_id( $identifier );
        } catch ( Competition_Not_Found_Exception ) {
            return Event_Dropdown_Result::error( __( 'Event not found', 'racketmanager' ) );
        }

        $this->event_query_service->set_season( $event, $query->season ?? null );

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $event->current_season['name'] ?? null, $this->competition_service ) ) {
            return Event_Dropdown_Result::error( __( 'Event not found for competition', 'racketmanager' ) );
        }

        $leagues  = $this->event_query_service->get_leagues( $event );
        $filename = ! empty( $query->template ) ? 'dropdown-' . $query->template : 'dropdown';

        return new Event_Dropdown_Result(
            leagues: $leagues,
            template: $filename
        );
    }
}
