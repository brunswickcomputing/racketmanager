<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Application\Event\DTOs\Event_Standings_Result;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition_Service;
use function __;

class Get_Event_Standings_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly Event_Query_Service $event_query_service
    ) {}

    public function handle( Get_Event_Standings_Query $query ): Event_Standings_Result {
        $identifier = $query->event_id ?? $query->event_name;
        if ( null === $identifier || '' === $identifier ) {
            return Event_Standings_Result::error( __( 'Event not found', 'racketmanager' ) );
        }

        try {
            $event = $this->competition_service->get_event_by_id( $identifier );
        } catch ( Competition_Not_Found_Exception ) {
            return Event_Standings_Result::error( __( 'Event not found', 'racketmanager' ) );
        }

        $this->event_query_service->set_season( $event, $query->season );
        $event->leagues = $this->event_query_service->get_leagues( $event );

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $event->current_season['name'] ?? $query->season, $this->competition_service ) ) {
            return Event_Standings_Result::error( __( 'Event not found for competition', 'racketmanager' ) );
        }

        $filename = ! empty( $query->template ) ? 'standings-' . $query->template : 'standings';

        return new Event_Standings_Result(
            event: $event,
            leagues: $event->leagues ?? [],
            template: $filename
        );
    }
}
