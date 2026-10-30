<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Application\Event\DTOs\Event_Matches_Result;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\League_Service;
use function __;
use function array_reverse;
use function count;
use function is_object;
use function method_exists;

class Get_Event_Matches_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly ?League_Service $league_service,
        private readonly Event_Query_Service $event_query_service,
        private readonly League_Query_Service $league_query_service
    ) {}

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

    public function get_draw( object $event, ?string $season ): array {
        $leagues = $this->event_query_service->get_leagues( $event );
        foreach ( $leagues as $l => $league ) {
            $league_id  = is_object( $league ) ? ( $league->id ?? 0 ) : $league;
            $league_obj = $this->league_service ? $this->league_service->get_league( $league_id ) : null;
            if ( ! $league_obj ) {
                $league_obj = $league;
            }
            if ( isset( $league_obj->championship ) && is_object( $league_obj->championship ) && method_exists( $league_obj->championship, 'get_finals' ) ) {
                $finals = array_reverse( $league_obj->championship->get_finals() );
                foreach ( $finals as $f => $final ) {
                    $matches = $league_obj instanceof League ? $this->league_query_service->get_matches( $league_obj, [
                        'season'  => $season,
                        'final'   => $final['key'] ?? null,
                        'orderby' => [
                            'id' => 'ASC',
                        ],
                    ] ) : [];
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
