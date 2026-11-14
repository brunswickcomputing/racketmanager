<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

use Racketmanager\Application\Club\DTOs\Club_Competitions_Result;
use Racketmanager\Application\Club\Ports\Club_Service_Interface;
use Racketmanager\Application\Competition\Ports\Competition_Service_Interface;
use Racketmanager\Application\Competition\Ports\Event_Query_Service_Interface;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use function __;
use function is_array;

/**
 * Handles the [club-competitions] query: either one named competition or every cup/league
 * competition in which the club has teams.
 */
class Get_Club_Competitions_Handler {
    private const array COMPETITION_TYPES = [ 'cup', 'league' ];

    /**
     * Initialises the service with dependencies for club, competition, repository, and event query operations.
     *
     * @param Club_Service_Interface           $club_service Club service dependency.
     * @param Competition_Service_Interface    $competition_service Competition service dependency.
     * @param Competition_Repository_Interface $competition_repository Competition repository dependency.
     * @param Event_Query_Service_Interface    $event_query_service Event query service dependency.
     *
     * @return void
     */
    public function __construct(
        private readonly Club_Service_Interface $club_service,
        private readonly Competition_Service_Interface $competition_service,
        private readonly Competition_Repository_Interface $competition_repository,
        private readonly Event_Query_Service_Interface $event_query_service
    ) {
    }

    /**
     * Handles a club competitions query and builds the result for matching competitions.
     *
     * @param Get_Club_Competitions_Query $query Query containing the club name, optional competition name, and template.
     *
     * @return Club_Competitions_Result Result containing club competition data, update permission, and template, or an error result when the club or competition cannot be found.
     */
    public function handle( Get_Club_Competitions_Query $query ): Club_Competitions_Result {
        try {
            $club = $this->club_service->get_club_by_shortcode( $query->club_name );
        } catch ( Club_Not_Found_Exception $e ) {
            return Club_Competitions_Result::error( $e->getMessage() );
        }

        if ( ! empty( $query->competition_name ) ) {
            $competition = $this->competition_repository->find_by_name( $query->competition_name );
            if ( ! $competition ) {
                return Club_Competitions_Result::error( __( 'Competition not found', 'racketmanager' ) );
            }

            $competition_data  = $this->gather_competition_data_for_club( $club, $competition );
            $competitions_data = null !== $competition_data ? [ $competition_data ] : [];
        } else {
            $competitions_data = $this->gather_all_competitions_data_for_club( $club );
        }

        return new Club_Competitions_Result(
            club: $club,
            competitions_data: $competitions_data,
            user_can_update: $this->club_service->can_user_update( (int) $club->get_id() ),
            template: $query->template
        );
    }

    /**
     * Gathers competition data across all configured competition types for a club.
     *
     * @param object $club Club object used to filter teams.
     *
     * @return array List of competition data entries where the club has participating teams.
     */
    private function gather_all_competitions_data_for_club( object $club ): array {
        $competitions_data = [];

        foreach ( self::COMPETITION_TYPES as $type ) {
            $competitions = $this->competition_service->get_by_criteria( [ 'type' => $type ] );

            foreach ( $competitions as $competition ) {
                $competition_data = $this->gather_competition_data_for_club( $club, $competition );
                if ( null !== $competition_data ) {
                    $competitions_data[] = $competition_data;
                }
            }
        }

        return $competitions_data;
    }

    /**
     * Gathers event and team data for a club within a competition.
     *
     * @param object $club Club object used to filter teams.
     * @param object $competition Competition object whose events are inspected.
     *
     * @return array|null Competition data with matching events and teams, or null when no event contains teams for the club.
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
