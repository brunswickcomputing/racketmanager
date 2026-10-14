<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Matches\Queries;

use Racketmanager\Application\Matches\DTOs\Match_Detail_Result;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Player_Service;
use function __;

/**
 * Handles the [match-detail] query: loads the fixture, its rubbers (optionally for one player)
 * and the league/tournament classification without mutating the fixture with presentation data.
 */
class Get_Match_Detail_Handler {
    public function __construct(
        private readonly Fixture_Detail_Service $fixture_detail_service,
        private readonly Player_Service $player_service
    ) {}

    public function handle( Get_Match_Detail_Query $query ): Match_Detail_Result {
        if ( 0 === $query->match_id ) {
            return Match_Detail_Result::error( __( 'Match id not found', 'racketmanager' ) );
        }

        $details = $this->fixture_detail_service->get_fixture_with_details( $query->match_id );
        if ( ! $details ) {
            return Match_Detail_Result::error( __( 'Match not found', 'racketmanager' ) );
        }

        $match = $details->fixture;
        $final = $match->get_final();
        $round = null === $final ? '' : (string) $final;
        $type  = null === $final ? 'league' : 'tournament';

        $player      = null;
        $rubber_for  = null;
        if ( $query->player_id ) {
            try {
                $player     = $this->player_service->get_player( $query->player_id );
                $rubber_for = $player->id;
            } catch ( Player_Not_Found_Exception ) {
                $player = null;
            }
        }

        $match->rubbers = $match->get_rubbers( $rubber_for );

        $template = $query->template;
        if ( ! empty( $details->league->num_rubbers ) ) {
            $template = 'teams-scores';
        }

        return new Match_Detail_Result(
            details: $details,
            match_player: $player,
            round: $round,
            type: $type,
            template: $template
        );
    }
}
