<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Application\Club\Ports\Club_Service_Interface;
use Racketmanager\Application\Event\DTOs\Event_Clubs_Result;
use Racketmanager\Application\Player\Ports\Player_Stats_Service_Interface;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition_Service;
use function is_numeric;

/**
 * Handles queries for clubs associated with an event.
 *
 * Resolves the requested event, applies the requested season context, validates
 * that the event is available for the competition, and retrieves active clubs
 * for the event. When a club identifier is supplied, the matching club is also
 * resolved and enriched with event-specific player statistics.
 *
 * Returns an error result when the event cannot be identified, the event is not
 * allowed for the selected competition context, or the requested club cannot be
 * found.
 */
readonly class Get_Event_Clubs_Handler {
    /**
     * Initialises the service with required competition, club, player statistics, and event query dependencies.
     *
     * @param Competition_Service            $competition_service Competition service dependency.
     * @param Club_Service_Interface         $club_service Club service dependency.
     * @param Player_Stats_Service_Interface $player_stats_service Player statistics service dependency.
     * @param Event_Query_Service            $event_query_service Event query service dependency.
     *
     * @return void
     */
    public function __construct(
        private Competition_Service $competition_service,
        private Club_Service_Interface $club_service,
        private Player_Stats_Service_Interface $player_stats_service,
        private Event_Query_Service $event_query_service
    ) {}

    /**
     * Handles a request to retrieve clubs for an event, optionally including a specific club with populated player statistics.
     *
     * @param Get_Event_Clubs_Query $query Query containing the event identifier, season, optional club identifier, and template name.
     *
     * @return Event_Clubs_Result Result containing the event, matching event club when requested, event clubs list, template name, or an error message.
     */
    public function handle( Get_Event_Clubs_Query $query ): Event_Clubs_Result {
        try {
            $event      = $this->resolve_event( $query );
            $event_club = $this->resolve_event_club( $event, $query->club_id );

            $event->clubs = $this->event_query_service->get_clubs( $event, [ 'status' => 1 ] );
            $filename     = ! empty( $query->template ) ? 'clubs-' . $query->template : 'clubs';

            return new Event_Clubs_Result(
                event: $event,
                event_club: $event_club,
                clubs: $event->clubs ?? [],
                template: $filename
            );
        } catch ( Competition_Not_Found_Exception | Club_Not_Found_Exception $e ) {
            return Event_Clubs_Result::error( $e->getMessage() );
        }
    }

    /**
     * Resolves the event requested by the query and validates that it is available for the requested season.
     *
     * @param Get_Event_Clubs_Query $query Query containing the event identifier and season context.
     *
     * @return Event Resolved event instance.
     * @throws Competition_Not_Found_Exception Competition not found.
     */
    private function resolve_event( Get_Event_Clubs_Query $query ): Event {
        $identifier = $query->event_id ?? $query->event_name;
        if ( null === $identifier || '' === $identifier ) {
            throw new Competition_Not_Found_Exception( esc_html__( 'Event not found', 'racketmanager' ) );
        }

        try {
            $event = $this->competition_service->get_event_by_id( $identifier );
        } catch ( Competition_Not_Found_Exception ) {
            throw new Competition_Not_Found_Exception( esc_html__( 'Event not found', 'racketmanager' ) );
        }

        $this->event_query_service->set_season( $event, $query->season );

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $event->current_season['name'] ?? $query->season, $this->competition_service ) ) {
            throw new Competition_Not_Found_Exception( esc_html__( 'Event not found for competition', 'racketmanager' ) );
        }

        return $event;
    }

    /**
     * Resolves a club for the given event and populates its players with event statistics.
     *
     * @param Event           $event Event used to resolve the club context and player statistics.
     * @param int|string|null $club_id Club identifier or shortcode to resolve, or null to return no club.
     *
     * @return object|null Event club object with populated player statistics, or null when no club identifier is provided.
     * @throws Club_Not_Found_Exception When the club is not.
     */
    private function resolve_event_club( Event $event, int|string|null $club_id ): ?object {
        if ( empty( $club_id ) ) {
            return null;
        }

        try {
            $club = is_numeric( $club_id )
                ? $this->club_service->get_club( (int) $club_id )
                : $this->club_service->get_club_by_shortcode( (string) $club_id );

            $event_club          = $this->event_query_service->get_club( $event, $club );
            $event_club->players = $this->player_stats_service->populate_players_stats(
                $event_club->players,
                $event,
                $event->current_season['name'] ?? null,
                'event'
            );

            return $event_club;
        } catch ( Club_Not_Found_Exception ) {
            throw new Club_Not_Found_Exception( esc_html__( 'Club not found', 'racketmanager' ) );
        }
    }
}
