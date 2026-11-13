<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use BackedEnum;
use Racketmanager\Application\Club\Ports\Team_Service_Interface;
use Racketmanager\Application\League\DTOs\League_Teams_Result;
use Racketmanager\Application\League\DTOs\League_View_State;
use Racketmanager\Application\League\Ports\League_Query_Service_Interface;
use Racketmanager\Application\League\Ports\League_Service_Interface;
use Racketmanager\Application\Player\Ports\Player_Stats_Service_Interface;
use Racketmanager\Util\Util;
use Throwable;
use function __;
use function current_user_can;
use function get_current_user_id;
use function is_array;
use function is_iterable;
use function is_object;
use function is_user_logged_in;
use function iterator_to_array;


/**
 * Query handler for [teams] shortcode.
 */
readonly class Get_League_Teams_Handler {
    /**
     * Initialises the service with league, team, player statistics, and league query dependencies.
     *
     * @param League_Service_Interface       $league_service League service dependency.
     * @param Team_Service_Interface         $team_service Team service dependency.
     * @param Player_Stats_Service_Interface $player_stats_service Player statistics service dependency.
     * @param League_Query_Service_Interface $league_query_service League query service dependency.
     *
     * @return void
     */
    public function __construct(
        private League_Service_Interface $league_service,
        private Team_Service_Interface $team_service,
        private Player_Stats_Service_Interface $player_stats_service,
        private League_Query_Service_Interface $league_query_service
    ) {}

    /**
     * Handles a league teams query and returns either team details or a league teams listing result.
     *
     * @param Get_League_Teams_Query $query Query containing league, team, group, and template selection data.
     *
     * @return League_Teams_Result Result containing league team data, team details, view state, or an error message.
     */
    public function handle( Get_League_Teams_Query $query ): League_Teams_Result {
        $league = $this->resolve_league( $query );
        if ( ! $league ) {
            return League_Teams_Result::error( __( 'League not found', 'racketmanager' ) );
        }

        return $query->team_id
            ? $this->handle_team_details( $league, $query )
            : $this->handle_teams_list( $league, $query );
    }

    /**
     * Resolves the league entity from query and initialises requested season.
     *
     * @param Get_League_Teams_Query $query Query parameters.
     *
     * @return object|null Resolved league entity or null if not found.
     */
    private function resolve_league( Get_League_Teams_Query $query ): ?object {
        $league = ! empty( $query->league_id ) ? $this->league_service->get_league( $query->league_id ) : null;
        if ( ! $league ) {
            return null;
        }

        if ( false !== $this->league_query_service->resolve_requested_season( $league ) ) {
            $this->league_query_service->set_season( $league );
        }

        return $league;
    }

    /**
     * Handles the team details query branch.
     *
     * @param object                 $league League entity.
     * @param Get_League_Teams_Query $query Query parameters.
     *
     * @return League_Teams_Result Team details result or error result.
     */
    private function handle_team_details( object $league, Get_League_Teams_Query $query ): League_Teams_Result {
        $team_details = $this->fetch_team_details( $query->team_id );
        if ( ! $team_details ) {
            return League_Teams_Result::error( __( 'Team not found', 'racketmanager' ) );
        }

        $team = $team_details->team ?? null;
        if ( ! $this->init_league_team( $league, $team ) ) {
            return League_Teams_Result::error( __( 'Team not found in league', 'racketmanager' ) );
        }

        $raw_matches = $this->get_raw_matches( $league, $team->id );
        $this->populate_team_players( $league, $team );
        $league->team = $team;

        return new League_Teams_Result(
            league: $league,
            is_team_details: true,
            team: $team,
            team_details: $team_details,
            raw_matches: $raw_matches,
            user_can_edit_team: $this->can_edit_team( $team_details ),
            team_id: (int) $team->id,
            template: $query->template,
            view_state: new League_View_State(
                tab: 'teams',
                templates: [ 'teams' => $query->template ],
                extra: [ 'group' => $query->group ?? '' ]
            )
        );
    }

    /**
     * Handles the teams listing query branch.
     *
     * @param object                 $league League entity.
     * @param Get_League_Teams_Query $query Query parameters.
     *
     * @return League_Teams_Result Teams listing result.
     */
    private function handle_teams_list( object $league, Get_League_Teams_Query $query ): League_Teams_Result {
        $teams        = $this->get_league_teams( $league, $query->group );
        $teams_state  = $this->league_query_service->set_num_teams( $league );
        $league_teams = $this->decorate_league_teams( $league, $teams );

        return new League_Teams_Result(
            league: $league,
            is_team_details: false,
            league_teams: $league_teams,
            item_link: $this->build_item_link( $league ),
            template: $query->template,
            view_state: $teams_state->with(
                [
                    'tab'       => 'teams',
                    'templates' => [ 'teams' => $query->template ],
                    'extra'     => [ 'group' => $query->group ?? '' ],
                ]
            )
        );
    }

    /**
     * Safely retrieves team details from the team service.
     *
     * @param int|string $team_id Team identifier.
     *
     * @return object|null Team details object or null if retrieval fails.
     */
    private function fetch_team_details( int|string $team_id ): ?object {
        try {
            return $this->team_service->get_team_details( $team_id );
        } catch ( Throwable ) {
            return null;
        }
    }

    /**
     * Populates team details and standings and validates league ranking.
     *
     * @param object      $league League entity.
     * @param object|null $team Team entity or null.
     *
     * @return bool True if team exists and is ranked in the league, false otherwise.
     */
    private function init_league_team( object $league, ?object $team ): bool {
        if ( ! $team ) {
            return false;
        }

        $info = $this->league_query_service->get_team_dtls( $league, $team->id );
        if ( is_object( $info ) ) {
            $team->info = $info;
        }

        $standings = $this->league_query_service->get_league_team( $league, $team->id );
        if ( is_object( $standings ) ) {
            $team->standings = $standings;
        }

        return ! empty( $team->standings->rank );
    }

    /**
     * Retrieves and normalises raw matches for a team in a league.
     *
     * @param object     $league League entity.
     * @param int|string $team_id Team identifier.
     *
     * @return array List of matches.
     */
    private function get_raw_matches( object $league, int|string $team_id ): array {
        $team_matches = $this->league_query_service->get_matches(
            $league,
            [
                'team_id'          => $team_id,
                'match_day'        => false,
                'limit'            => 'false',
                'reset_query_args' => true,
            ]
        );

        if ( is_array( $team_matches ) ) {
            return $team_matches;
        }

        if ( is_iterable( $team_matches ) ) {
            return iterator_to_array( $team_matches );
        }

        return [];
    }

    /**
     * Populates team players and their statistics for the current season.
     *
     * @param object $league League entity.
     * @param object $team Team entity.
     *
     * @return void
     */
    private function populate_team_players( object $league, object $team ): void {
        $team->players = $this->league_query_service->get_players(
            $league,
            [
                'team' => $team->id,
            ]
        );
        $team->players = $this->player_stats_service->populate_players_stats(
            $team->players,
            $league,
            $league->current_season['name'] ?? null,
            'league'
        );
    }

    /**
     * Determines whether the current user has permission to edit the team.
     *
     * @param object|null $team_details Team details object.
     *
     * @return bool True if current user can edit the team, false otherwise.
     */
    private function can_edit_team( ?object $team_details ): bool {
        return is_user_logged_in() && (
            current_user_can( 'manage_racketmanager' ) ||
            ( ! empty( $team_details->match_secretary->id ) && get_current_user_id() === (int) $team_details->match_secretary->id )
        );
    }

    /**
     * Retrieves league teams matching the query criteria.
     *
     * @param object      $league League entity.
     * @param string|null $group Optional group name.
     *
     * @return array|int Collection or array of league teams.
     */
    private function get_league_teams( object $league, ?string $group ): array|int {
        $team_args = [ 'orderby' => [ 'title' => 'ASC' ] ];
        if ( $group ) {
            $team_args['group'] = $group;
        }

        return $this->league_query_service->get_league_teams( $league, $team_args );
    }

    /**
     * Decorates league teams with additional team details.
     *
     * @param object $league League entity.
     * @param mixed  $teams Collection or array of league teams.
     *
     * @return array List of decorated league teams.
     */
    private function decorate_league_teams( object $league, mixed $teams ): array {
        $league_teams = [];
        if ( is_iterable( $teams ) ) {
            foreach ( $teams as $team ) {
                $info = $this->league_query_service->get_team_dtls( $league, $team->id );
                if ( is_object( $info ) ) {
                    $team->info = $info;
                }
                $league_teams[] = $team;
            }
        }

        return $league_teams;
    }

    /**
     * Constructs the canonical item link for the league teams list.
     *
     * @param object $league League entity.
     *
     * @return string Canonical item link URL.
     */
    private function build_item_link( object $league ): string {
        $raw_comp_type = $league->event->competition->type ?? 'league';
        $comp_type     = $raw_comp_type instanceof BackedEnum ? $raw_comp_type->value : (string) $raw_comp_type;
        $season_name   = $league->current_season['name'] ?? '';

        return '/' . $comp_type . '/' . Util::seo_url( (string) ( $league->title ?? '' ) ) . '/' . $season_name;
    }
}
