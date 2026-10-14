<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Domain\Competition\League;
use Racketmanager\Presentation\View_Models\League_Standings_View_Model;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\League_Service;
use function is_array;

/**
 * Query handler for resolving [standings] shortcode data into a view model.
 */
class Get_League_Standings_Handler {
    public function __construct(
        private readonly ?League_Service $league_service = null,
        private readonly ?League_Repository_Interface $league_repository = null
    ) {}

    public function handle( Get_League_Standings_Query $query ): ?League_Standings_View_Model {
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
        $league->set_season( $query->season ?? false );
        $league->set_group( $query->group ?? '' );

        $team_args           = [ 'orderby' => [ 'rank' => 'ASC' ], 'active' => true ];
        if ( ! empty( $query->group ) ) {
            $team_args['group'] = $query->group;
        }

        $teams       = $league->get_league_teams( $team_args );
        $teams_array = is_array( $teams ) ? $teams : [];
        $league->teams = $teams_array;

        return new League_Standings_View_Model(
            template: $query->template,
            league: $league,
            teams: $teams_array,
            widget: $query->widget,
            season: $query->season,
            group: $query->group
        );
    }
}
