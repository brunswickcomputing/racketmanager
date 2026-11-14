<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Application\Club\Ports\Team_Service_Interface;
use Racketmanager\Application\Competition\Ports\Competition_Service_Interface;
use Racketmanager\Application\Competition\Ports\Event_Query_Service_Interface;
use Racketmanager\Application\Event\DTOs\Event_Teams_Result;
use Racketmanager\Application\Player\Ports\Player_Stats_Service_Interface;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Throwable;
use function __;

/**
 * Handles retrieval of team data for an event.
 *
 * Resolves an event from the query identifier, applies the requested season,
 * validates that the event is allowed for the competition context, and loads
 * the event teams ordered by name. When a team is requested and a team service
 * is available, the handler also loads that team's event information, matches,
 * players, and player statistics.
 *
 * Returns an error result when the event identifier is missing, the event cannot
 * be found, or the event is not allowed for the resolved competition context.
 * Otherwise, returns an event teams result containing the event, optional team,
 * team list, current season, and resolved template name.
 */
readonly class Get_Event_Teams_Handler {
    /**
     * Initialises the instance with the required service dependencies.
     *
     * @param Competition_Service_Interface  $competition_service Competition service dependency.
     * @param Player_Stats_Service_Interface $player_stats_service Player statistics service dependency.
     * @param Team_Service_Interface|null    $team_service Optional team service dependency.
     * @param Event_Query_Service_Interface  $event_query_service Event query service dependency.
     *
     * @return void
     */
    public function __construct(
        private Competition_Service_Interface $competition_service,
        private Player_Stats_Service_Interface $player_stats_service,
        private ?Team_Service_Interface $team_service,
        private Event_Query_Service_Interface $event_query_service
    ) {}

    /**
     * Handles a query for event teams, optionally resolving a selected team and populating its related details.
     *
     * @param Get_Event_Teams_Query $query Query containing the event identifier, season, team identifier, and template selection.
     *
     * @return Event_Teams_Result Result containing the resolved event, teams, optional team details, selected season, and template name, or an error result when the event cannot be resolved.
     */
    public function handle( Get_Event_Teams_Query $query ): Event_Teams_Result {
        $identifier = $query->event_id ?? $query->event_name;
        if ( null === $identifier || '' === $identifier ) {
            return Event_Teams_Result::error( __( 'Event not found', 'racketmanager' ) );
        }

        try {
            $event = $this->competition_service->get_event_by_id( $identifier );
        } catch ( Competition_Not_Found_Exception ) {
            return Event_Teams_Result::error( __( 'Event not found', 'racketmanager' ) );
        }

        $this->event_query_service->set_season( $event, $query->season );
        $current_season = $event->current_season['name'] ?? '';

        if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $current_season ?: $query->season, $this->competition_service ) ) {
            return Event_Teams_Result::error( __( 'Event not found for competition', 'racketmanager' ) );
        }

        $event->teams = $this->event_query_service->get_teams(
            $event,
            [
                'season'  => $current_season,
                'orderby' => [ 'name' => 'ASC' ],
            ]
        );

        $team_identifier = $query->team;
        $team            = null;

        if ( ! empty( $team_identifier ) && $this->team_service ) {
            try {
                $team = $this->team_service->get_team_by_id( $team_identifier );
            } catch ( Throwable ) {
                $team = null;
            }
            if ( $team ) {
                $team->info    = $this->event_query_service->get_team_info( $event, $team->id );
                $team->matches = $this->event_query_service->get_matches(
                    $event,
                    [
                        'team_id'          => $team->id,
                        'match_day'        => false,
                        'limit'            => 'false',
                        'reset_query_args' => true,
                        'season'           => $current_season,
                        'orderby'          => [ 'date' => 'ASC' ],
                    ]
                );
                $team->players = $this->event_query_service->get_players(
                    $event,
                    [
                        'team' => $team->id,
                    ]
                );
                $team->players = $this->player_stats_service->populate_players_stats( $team->players, $event, $current_season, 'event' );
                $event->team   = $team;
            }
        }

        $template = $query->template;
        if ( null === $team && ! empty( $event->competition->is_championship ) && empty( $template ) ) {
            $template = 'list';
        }

        $filename = empty( $template ) ? 'teams' : 'teams-' . $template;

        return new Event_Teams_Result(
            event: $event,
            team: $team,
            teams: $event->teams,
            curr_season: $current_season,
            template: $filename
        );
    }
}
