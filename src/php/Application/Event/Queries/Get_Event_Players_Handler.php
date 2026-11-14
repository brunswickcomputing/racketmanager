<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Application\Competition\Ports\Competition_Service_Interface;
use Racketmanager\Application\Competition\Ports\Event_Query_Service_Interface;
use Racketmanager\Application\Event\DTOs\Event_Players_Result;
use Racketmanager\Application\Player\Ports\Player_Query_Service_Interface;
use Racketmanager\Application\Player\Ports\Player_Service_Interface;
use Racketmanager\Application\Player\Ports\Player_Stats_Service_Interface;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Util\Util;
use function __;
use function asort;
use function is_numeric;
use function is_object;

/**
 * Handles retrieval of players for a competition event.
 *
 * Resolves an event by identifier, applies the requested season, validates that the
 * event is available for the competition context, and returns either a single
 * player's event-specific details or the list of players associated with the event.
 *
 * When a player identifier is supplied, the handler enriches the player with
 * event matches and aggregate statistics. When no player identifier is supplied,
 * the handler returns the event player list for the current season.
 */
readonly class Get_Event_Players_Handler {
    /**
     * Initialises the service with competition, player, statistics, and event query dependencies.
     *
     * @param Competition_Service_Interface  $competition_service Competition service dependency.
     * @param Player_Service_Interface       $player_service Player service dependency.
     * @param Player_Query_Service_Interface $player_query_service Player query service dependency.
     * @param Player_Stats_Service_Interface $player_stats_service Player statistics service dependency.
     * @param Event_Query_Service_Interface  $event_query_service Event query service dependency.
     *
     * @return void
     */
    public function __construct(
        private Competition_Service_Interface $competition_service,
        private Player_Service_Interface $player_service,
        private Player_Query_Service_Interface $player_query_service,
        private Player_Stats_Service_Interface $player_stats_service,
        private Event_Query_Service_Interface $event_query_service
    ) {}

    /**
     * Handles a request for event player data by resolving the event, applying season context, validating access, and loading either a specific player or the event player list.
     *
     * @param Get_Event_Players_Query $query Query containing the event identifier, season, and optional player identifier.
     *
     * @return Event_Players_Result Result containing the resolved event, player data, player list, template name, or an error message.
     */
    public function handle( Get_Event_Players_Query $query ): Event_Players_Result {
        $identifier = $query->event_id ?? $query->event_name;
        if ( null === $identifier || '' === $identifier ) {
            return Event_Players_Result::error( __( 'Event not found', 'racketmanager' ) );
        }

        try {
            $event = $this->competition_service->get_event_by_id( $identifier );
        } catch ( Competition_Not_Found_Exception ) {
            return Event_Players_Result::error( __( 'Event not found', 'racketmanager' ) );
        }

        $this->event_query_service->set_season( $event, $query->season );

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $event->current_season['name'] ?? $query->season, $this->competition_service ) ) {
            return Event_Players_Result::error( __( 'Event not found for competition', 'racketmanager' ) );
        }

        $player = null;
        if ( ! empty( $query->player_id ) ) {
            $player = $this->get_player_info( $event, $query->player_id );
            if ( is_object( $player ) ) {
                $event->player = $player;
            } else {
                return Event_Players_Result::error( __( 'Player not found', 'racketmanager' ) );
            }
        } else {
            $current_season = isset( $event->current_season['name'] ) ? (string) $event->current_season['name'] : null;
            $players        = $this->event_query_service->get_players( $event, [ 'season' => $current_season ] );
            $event->players = Util::get_players_list( $players );
        }

        return new Event_Players_Result(
            event: $event,
            player: $player,
            players: $event->players ?? [],
            template: 'players'
        );
    }

    /**
     * Retrieves player information for an event, including matches and statistics when available.
     *
     * @param object     $event Event object used to determine the current season and match context.
     * @param int|string $player_id Player identifier as a numeric ID or player name.
     *
     * @return object|null Player object with matches and stats populated, or null when the player is not found.
     */
    private function get_player_info( object $event, int|string $player_id ): ?object {
        try {
            if ( is_numeric( $player_id ) ) {
                $player = $this->player_service->get_player( (int) $player_id );
            } else {
                $player = $this->player_service->get_player_by_name( (string) $player_id );
            }
        } catch ( Player_Not_Found_Exception ) {
            return null;
        }

        if ( $player ) {
            $season          = isset( $event->current_season['name'] ) ? (string) $event->current_season['name'] : null;
            $player->matches = $this->player_query_service->get_matches( $player, $event, $season, 'event' );
            asort( $player->matches );
            $player->stats = $this->player_stats_service->get_stats( $player );
        }

        return $player;
    }
}
