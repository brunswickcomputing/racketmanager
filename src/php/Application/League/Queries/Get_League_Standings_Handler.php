<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Application\League\DTOs\League_Standings_Result;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;
use function is_array;

/**
 * Query handler for resolving [standings] shortcode data.
 */
class Get_League_Standings_Handler {
    public function __construct(
        private readonly ?League_Service $league_service,
        private readonly ?League_Repository_Interface $league_repository,
        private readonly League_Query_Service $league_query_service
    ) {}

    public function handle( Get_League_Standings_Query $query ): ?League_Standings_Result {
        $league = null;
        if ( ! empty( $query->league_id ) ) {
            if ( $this->league_service ) {
                $league = $this->league_service->get_league( $query->league_id );
            } elseif ( $this->league_repository ) {
                $league = $this->league_repository->find_by_id( $query->league_id );
            }
        }

        if ( ! $league ) {
            return null;
        }

        $league->set_template( 'standings', $query->template );
        $this->league_query_service->set_season( $league, $query->season ?? false );
        $league->set_group( $query->group ?? '' );

        $team_args           = [ 'orderby' => [ 'rank' => 'ASC' ], 'active' => true ];
        if ( ! empty( $query->group ) ) {
            $team_args['group'] = $query->group;
        }

        $teams       = $this->league_query_service->get_league_teams( $league, $team_args );
        $teams_array = is_array( $teams ) ? $teams : [];
        $league->teams = $teams_array;

        return new League_Standings_Result(
            league: $league,
            teams: $teams_array,
            template: $query->template,
            widget: $query->widget,
            season: $query->season,
            group: $query->group
        );
    }
}
