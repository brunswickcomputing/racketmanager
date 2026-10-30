<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Application\Event\DTOs\Event_Clubs_Result;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Player_Stats_Service;
use function is_numeric;
use function __;

class Get_Event_Clubs_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly Club_Service $club_service,
        private readonly Player_Stats_Service $player_stats_service,
        private readonly Event_Query_Service $event_query_service
    ) {}

    public function handle( Get_Event_Clubs_Query $query ): Event_Clubs_Result {
        $identifier = $query->event_id ?? $query->event_name;
        if ( null === $identifier || '' === $identifier ) {
            return Event_Clubs_Result::error( __( 'Event not found', 'racketmanager' ) );
        }

        try {
            $event = $this->competition_service->get_event_by_id( $identifier );
        } catch ( Competition_Not_Found_Exception ) {
            return Event_Clubs_Result::error( __( 'Event not found', 'racketmanager' ) );
        }

        $event_club = null;
        $this->event_query_service->set_season( $event, $query->season );

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $event->current_season['name'] ?? $query->season, $this->competition_service ) ) {
            return Event_Clubs_Result::error( __( 'Event not found for competition', 'racketmanager' ) );
        }

        if ( ! empty( $query->club_id ) ) {
            try {
                if ( is_numeric( $query->club_id ) ) {
                    $club = $this->club_service->get_club( (int) $query->club_id );
                } else {
                    $club = $this->club_service->get_club_by_shortcode( (string) $query->club_id );
                }
                $event_club = $this->event_query_service->get_club( $event, $club );
                $event_club->players = $this->player_stats_service->populate_players_stats(
                    $event_club->players,
                    $event,
                    $event->current_season['name'] ?? null,
                    'event'
                );
            } catch ( Club_Not_Found_Exception ) {
                return Event_Clubs_Result::error( __( 'Club not found', 'racketmanager' ) );
            }
        }

        $event->clubs = $this->event_query_service->get_clubs( $event, [ 'status' => 1 ] );
        $filename     = ! empty( $query->template ) ? 'clubs-' . $query->template : 'clubs';

        return new Event_Clubs_Result(
            event: $event,
            event_club: $event_club,
            clubs: $event->clubs ?? [],
            template: $filename
        );
    }
}
