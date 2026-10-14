<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Team_Service;
use function __;
use function is_iterable;
use Throwable;

/**
 * Query handler for [teams] shortcode.
 */
class Get_League_Teams_Handler {
    public function __construct(
        private readonly League_Service $league_service,
        private readonly Team_Service $team_service
    ) {}

    public function handle( Get_League_Teams_Query $query ): Shortcode_View_Model {
        $league = null;
        if ( ! empty( $query->league_id ) ) {
            $league = $this->league_service->get_league( $query->league_id );
        }
        if ( ! $league ) {
            return Shortcode_View_Model::for_error( __( 'League not found', 'racketmanager' ) );
        }

        $league->set_template( 'teams', $query->template );
        $league->set_group( $query->group ?? '' );

        if ( $query->team_id ) {
            $team_details = null;
            try {
                $team_details = $this->team_service->get_team_details( $query->team_id );
            } catch ( Throwable ) {
                $team_details = null;
            }
            $team = $team_details?->team ?? null;
            if ( ! $team ) {
                return Shortcode_View_Model::for_error( __( 'Team not found in league', 'racketmanager' ) );
            }

            $team->info      = $league->get_team_dtls( $team->id );
            $team->standings = $league->get_league_team( $team->id );
            if ( empty( $team->standings->rank ) ) {
                return Shortcode_View_Model::for_error( __( 'Team not found in league', 'racketmanager' ) );
            }

            $team->matches = $league->get_matches( [
                'team_id'          => $team->id,
                'match_day'        => false,
                'limit'            => 'false',
                'reset_query_args' => true,
            ] );
            $team->players = $league->get_players( [
                'team'  => $team->id,
                'stats' => true,
            ] );
            $league->team = $team;

            return Shortcode_View_Model::for_template(
                'team-details',
                [
                    'league'       => $league,
                    'teams'        => [],
                    'object'       => $league,
                    'team_details' => $team_details,
                ],
                false
            );
        }

        $team_args = [ 'orderby' => [ 'title' => 'ASC' ] ];
        if ( $query->group ) {
            $team_args['group'] = $query->group;
        }

        $teams        = $league->get_league_teams( $team_args );
        $league_teams = [];
        if ( is_iterable( $teams ) ) {
            foreach ( $teams as $team ) {
                $team->info     = $league->get_team_dtls( $team->id );
                $league_teams[] = $team;
            }
        }

        return Shortcode_View_Model::for_template(
            'teams-list',
            [
                'league'       => $league,
                'teams'        => $league_teams,
                'object'       => $league,
                'team_details' => [],
            ],
            false
        );
    }
}
