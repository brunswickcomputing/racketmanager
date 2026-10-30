<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

use Racketmanager\Application\Club\DTOs\Club_Competitions_Result;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition_Service;
use function __;
use function is_array;

/**
 * Handles the [club-competitions] query: either one named competition or every cup/league
 * competition in which the club has teams.
 */
class Get_Club_Competitions_Handler {
    private const COMPETITION_TYPES = [ 'cup', 'league' ];

    public function __construct(
        private readonly Club_Service $club_service,
        private readonly Competition_Service $competition_service,
        private readonly Competition_Repository_Interface $competition_repository,
        private readonly Event_Query_Service $event_query_service
    ) {
    }

    public function handle( Get_Club_Competitions_Query $query ): Club_Competitions_Result {
        try {
            $club = $this->club_service->get_club_by_shortcode( $query->club_name );
        } catch ( Club_Not_Found_Exception $e ) {
            return Club_Competitions_Result::error( $e->getMessage() );
        }

        $competitions_data = [];

        if ( ! empty( $query->competition_name ) ) {
            $competition = $this->competition_repository->find_by_name( $query->competition_name );
            if ( ! $competition ) {
                return Club_Competitions_Result::error( __( 'Competition not found', 'racketmanager' ) );
            }
            $comp_data = $this->gather_competition_data_for_club( $club, $competition );
            if ( $comp_data ) {
                $competitions_data[] = $comp_data;
            }
        } else {
            foreach ( self::COMPETITION_TYPES as $type ) {
                foreach ( $this->competition_service->get_by_criteria( [ 'type' => $type ] ) as $competition ) {
                    $comp_data = $this->gather_competition_data_for_club( $club, $competition );
                    if ( $comp_data ) {
                        $competitions_data[] = $comp_data;
                    }
                }
            }
        }

        return new Club_Competitions_Result(
            club: $club,
            competitions_data: $competitions_data,
            user_can_update: $this->club_service->can_user_update( (int) $club->get_id() ),
            template: $query->template
        );
    }

    /**
     * Gather the competition data with only those events in which the club has teams; null when there are none.
     *
     * @return array{competition: object, events: array<int, array{event: object, teams: array<int, mixed>}>}|null
     */
    private function gather_competition_data_for_club( object $club, object $competition ): ?array {
        $events      = $this->competition_service->get_events_for_competition( (int) $competition->get_id() );
        $events_data = [];

        foreach ( $events as $event ) {
            $teams = $event instanceof Event
                ? $this->event_query_service->get_teams_info(
                    $event,
                    [
                        'club'    => $club->id,
                        'orderby' => [ 'title' => 'ASC' ],
                    ]
                )
                : ( $event->teams ?? [] );
            if ( ! $teams ) {
                continue;
            }
            $events_data[] = [
                'event' => $event,
                'teams' => is_array( $teams ) ? $teams : [],
            ];
        }

        return $events_data ? [
            'competition' => $competition,
            'events'      => $events_data,
        ] : null;
    }
}
