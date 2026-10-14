<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Matches\Queries;

use Racketmanager\Application\Matches\DTOs\Match_Score_Result;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use function __;

/**
 * Handles the [score] query: resolves the fixture and works out which side/outcome to show.
 */
class Get_Match_Score_Handler {
    public function __construct( private readonly Fixture_Detail_Service $fixture_detail_service ) {}

    public function handle( Get_Match_Score_Query $query ): Match_Score_Result {
        if ( 0 === $query->match_id ) {
            return Match_Score_Result::error( __( 'Match id not found', 'racketmanager' ) );
        }

        $details = $this->fixture_detail_service->get_fixture_with_details( $query->match_id );
        if ( ! $details ) {
            return Match_Score_Result::error( __( 'Match not found', 'racketmanager' ) );
        }

        $match = $details->fixture;
        if ( null === $match->get_home_points() && null === $match->get_away_points() ) {
            return new Match_Score_Result(
                details: $details,
                played: false,
                home_away: $query->home_away,
                template: $query->template
            );
        }

        $team_id      = (string) $query->team_id;
        $opponent_id  = (string) $query->opponent_id;
        $home_team    = (string) $match->get_home_team();
        $score_team_1 = null;
        $score_team_2 = null;
        if ( $team_id === $home_team ) {
            $score_team_1 = $match->get_home_points();
            $score_team_2 = $match->get_away_points();
        } elseif ( $opponent_id === $home_team ) {
            $score_team_1 = $match->get_away_points();
            $score_team_2 = $match->get_home_points();
        }

        if ( $team_id === (string) $match->get_winner_id() ) {
            $score_class = 'winner';
        } elseif ( $team_id === (string) $match->get_loser_id() ) {
            $score_class = 'loser';
        } elseif ( -1 === $match->get_winner_id() ) {
            $score_class = 'tie';
        } else {
            $score_class = null;
        }

        return new Match_Score_Result(
            details: $details,
            played: true,
            score_class: $score_class,
            score_team_1: $score_team_1,
            score_team_2: $score_team_2,
            home_away: $query->home_away,
            template: $query->template
        );
    }
}
