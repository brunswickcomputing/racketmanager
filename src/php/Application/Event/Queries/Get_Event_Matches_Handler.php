<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Application\Event\DTOs\Event_Matches_Result;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Application\League\Ports\League_Query_Service_Interface;
use Racketmanager\Application\League\Ports\League_Service_Interface;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition_Service;
use function __;
use function array_reverse;
use function count;
use function is_object;
use function method_exists;

/**
 * Handles requests for event match data and resolves the template used to render the result.
 *
 * The handler locates an event by identifier, applies the requested season, validates that the
 * event is available for the relevant competition, and loads the associated leagues or
 * championship draw data.
 *
 * Championship events are expanded with draw rounds and their matches when available. Standard
 * events are populated with their event leagues directly from the event query service.
 */
readonly class Get_Event_Matches_Handler {
    /**
     * Initialises the service with the dependencies required to handle competition, league, and event queries.
     *
     * @param Competition_Service            $competition_service Service for competition-related operations.
     * @param League_Service_Interface|null  $league_service Optional service for league-related operations.
     * @param Event_Query_Service            $event_query_service Service for event query operations.
     * @param League_Query_Service_Interface $league_query_service Service for league query operations.
     *
     * @return void
     */
    public function __construct(
        private Competition_Service $competition_service,
        private ?League_Service_Interface $league_service,
        private Event_Query_Service $event_query_service,
        private League_Query_Service_Interface $league_query_service
    ) {}

    /**
     * Handles a request for event matches by resolving the event, applying the requested season, validating access, and preparing match data.
     *
     * @param Get_Event_Matches_Query $query Query containing the event identifier, season, and optional template selection.
     *
     * @return Event_Matches_Result Result containing the resolved event and template name, or an error result when the event cannot be found or accessed.
     */
    public function handle( Get_Event_Matches_Query $query ): Event_Matches_Result {
        $identifier = $query->event_id ?? $query->event_name;
        if ( null === $identifier || '' === $identifier ) {
            return Event_Matches_Result::error( __( 'Event not found', 'racketmanager' ) );
        }

        try {
            $event = $this->competition_service->get_event_by_id( $identifier );
        } catch ( Competition_Not_Found_Exception ) {
            return Event_Matches_Result::error( __( 'Event not found', 'racketmanager' ) );
        }

        $this->event_query_service->set_season( $event, $query->season );

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $event->current_season['name'] ?? $query->season, $this->competition_service ) ) {
            return Event_Matches_Result::error( __( 'Event not found for competition', 'racketmanager' ) );
        }

        if ( ! empty( $event->competition->is_championship ) ) {
            $event->leagues = $this->get_draw( $event, $event->current_season['name'] ?? $query->season );
        } else {
            $event->leagues = $this->event_query_service->get_leagues( $event );
        }

        $filename = ! empty( $query->template ) ? 'matches-' . $query->template : 'matches';

        return new Event_Matches_Result(
            event: $event,
            template: $filename
        );
    }

    /**
     * Retrieves the draw data for an event, including associated leagues, championship finals, and matching fixtures for the given season.
     *
     * @param Event       $event Event object used to resolve related leagues.
     * @param string|null $season Optional season identifier used to filter matches.
     *
     * @return array List of league objects or identifiers enriched with finals and matches when available.
     */
    public function get_draw( Event $event, ?string $season ): array {
        $leagues = $this->event_query_service->get_leagues( $event );
        foreach ( $leagues as $l => $league ) {
            $league_id  = is_object( $league ) ? ( $league->id ?? 0 ) : $league;
            $league_obj = $this->league_service?->get_league( $league_id );
            if ( ! $league_obj ) {
                $league_obj = $league;
            }
            if ( isset( $league_obj->championship ) && is_object( $league_obj->championship ) && method_exists( $league_obj->championship, 'get_finals' ) ) {
                $finals = array_reverse( $league_obj->championship->get_finals() );
                foreach ( $finals as $f => $final ) {
                    $matches = $league_obj instanceof League ? $this->league_query_service->get_matches(
                        $league_obj,
                        [
                            'season'  => $season,
                            'final'   => $final['key'] ?? null,
                            'orderby' => [
                                'id' => 'ASC',
                            ],
                        ]
                    ) : [];
                    if ( count( $matches ) ) {
                        $final['matches'] = $matches;
                        $finals[ $f ]     = (object) $final;
                    } else {
                        unset( $finals[ $f ] );
                    }
                }
                $league_obj->finals = $finals;
            }
            $leagues[ $l ] = $league_obj;
        }

        return $leagues;
    }
}
