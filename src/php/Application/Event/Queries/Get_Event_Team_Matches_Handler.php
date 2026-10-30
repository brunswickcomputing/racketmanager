<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Application\Event\DTOs\Event_Team_Matches_Result;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition_Service;
use function __;

class Get_Event_Team_Matches_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly Event_Query_Service $event_query_service
    ) {}

    public function handle( Get_Event_Team_Matches_Query $query ): Event_Team_Matches_Result {
        $identifier = $query->event_id ?? $query->event_name;
        if ( null === $identifier || '' === $identifier ) {
            return Event_Team_Matches_Result::error( __( 'No event id supplied', 'racketmanager' ) );
        }

        try {
            $event = $this->competition_service->get_event_by_id( $identifier );
        } catch ( Competition_Not_Found_Exception ) {
            return Event_Team_Matches_Result::error( __( 'Event not found', 'racketmanager' ) );
        }

        $this->event_query_service->set_season( $event, $query->season ?? null );

        if ( empty( $query->team_id ) ) {
            return Event_Team_Matches_Result::error( __( 'No team id supplied', 'racketmanager' ) );
        }

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $event->current_season['name'] ?? null, $this->competition_service ) ) {
            return Event_Team_Matches_Result::error( __( 'Event not found for competition', 'racketmanager' ) );
        }

        $match_args = [
            'season'  => $event->current_season['name'] ?? '',
            'team_id' => $query->team_id,
            'pending' => true,
        ];
        $matches    = $this->event_query_service->get_matches( $event, $match_args );
        $filename   = ! empty( $query->template ) ? 'team-matches-' . $query->template : 'team-matches';

        return new Event_Team_Matches_Result(
            matches: $matches,
            template: $filename
        );
    }
}
