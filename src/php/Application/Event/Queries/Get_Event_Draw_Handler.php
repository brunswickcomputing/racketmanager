<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Application\Event\DTOs\Event_Draw_Result;
use Racketmanager\Application\Fixture\Ports\Fixture_Detail_Service_Interface;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Fixture_Not_Found_Exception;
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
 * Handles retrieval and preparation of event draw data.
 *
 * Resolves an event from a query identifier, applies the requested season, validates that the event is available for the competition context, and prepares the leagues required by the draw template. Championship events are expanded with finals, matches, and fixture details where available, while non-championship events receive their leagues directly from the event query service.
 *
 * Returns an error result when the event cannot be identified, cannot be loaded, or is not allowed for the requested competition.
 */
readonly class Get_Event_Draw_Handler {
    /**
     * Initialise the service with dependencies used to retrieve competition, league, fixture detail,
     * event query, and league query data.
     *
     * @param Competition_Service              $competition_service Competition service used to validate the event competition context.
     * @param League_Service_Interface|null    $league_service League service used to retrieve league data for championship events.
     * @param Fixture_Detail_Service_Interface $fixture_detail_service Fixture detail service used to retrieve fixture details for championship events.
     * @param Event_Query_Service              $event_query_service Event query service used to retrieve event data.
     * @param League_Query_Service_Interface   $league_query_service League query service used to retrieve league data for non-championship events.
     *
     * @return void
     */
    public function __construct(
        private Competition_Service $competition_service,
        private ?League_Service_Interface $league_service,
        private Fixture_Detail_Service_Interface $fixture_detail_service,
        private Event_Query_Service $event_query_service,
        private League_Query_Service_Interface $league_query_service
    ) {}

    /**
     * Handle an event draw query by resolving the requested event, applying the requested season,
     * validating that the event is allowed for the competition, and loading the draw leagues or
     * championship draw data needed for rendering.
     *
     * @param Get_Event_Draw_Query $query Get event draw query containing the event identifier and season.
     *
     * @return Event_Draw_Result
     */
    public function handle( Get_Event_Draw_Query $query ): Event_Draw_Result {
        $identifier = $query->event_id ?? $query->event_name;
        if ( null === $identifier || '' === $identifier ) {
            return Event_Draw_Result::error( __( 'Event not found', 'racketmanager' ) );
        }

        try {
            $event = $this->competition_service->get_event_by_id( $identifier );
        } catch ( Competition_Not_Found_Exception ) {
            return Event_Draw_Result::error( __( 'Event not found', 'racketmanager' ) );
        }

        $this->event_query_service->set_season( $event, $query->season );

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $event->current_season['name'] ?? $query->season, $this->competition_service ) ) {
            return Event_Draw_Result::error( __( 'Event not found for competition', 'racketmanager' ) );
        }

        if ( ! empty( $event->competition->is_championship ) ) {
            $event->leagues = $this->get_draw( $event, $event->current_season['name'] ?? $query->season );
        } else {
            $event->leagues = $this->event_query_service->get_leagues( $event );
        }

        $filename = ! empty( $query->template ) ? 'draw-' . $query->template : 'draw';

        return new Event_Draw_Result(
            event: $event,
            template: $filename
        );
    }

    /**
     * Build the draw data for an event by loading its leagues, resolving each league object when possible,
     * attaching championship finals that have matches for the requested season, and enriching those finals
     * with resolved fixture details for presentation.
     *
     * @param Event       $event Event object used to retrieve the related leagues.
     * @param string|null $season Season filter applied when loading final matches.
     *
     * @return array League objects or league identifiers, with available finals populated with matches and fixtures.
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
                        $final['matches']  = $matches;
                        $final['fixtures'] = $this->resolve_fixture_details( $matches );
                        $finals[ $f ]      = (object) $final;
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

    /**
     * Resolve raw Fixture entities into Fixture_Details_DTOs (teams, players, withdrawal status, links)
     * so the draw presenter can render opponents. Unresolvable fixtures are kept as raw entities.
     * Fixtures with non-empty leg != 2 (e.g. leg 1 of two-legged ties) are skipped because the draw
     * presenter discards them from rendering.
     *
     * @param array $matches Fixture entities to resolve into Fixture_Details_DTOs.
     * @return array Resolved Fixture_Details_DTOs or raw Fixture entities.
     */
    private function resolve_fixture_details( array $matches ): array {
        $fixtures = [];
        foreach ( $matches as $match ) {
            $details = null;
            if ( $match instanceof Fixture ) {
                if ( ! empty( $match->leg ) && 2 !== (int) $match->leg ) {
                    $fixtures[] = $match;
                    continue;
                }
                try {
                    $details = $this->fixture_detail_service->get_fixture_with_details( $match );
                } catch ( Fixture_Not_Found_Exception ) {
                    $details = null;
                }
            }
            $fixtures[] = $details ?? $match;
        }

        return $fixtures;
    }
}
