<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Matches\Queries;

use Racketmanager\Application\Fixture\Ports\Fixture_Detail_Service_Interface;
use Racketmanager\Application\Matches\DTOs\Match_Detail_Result;
use Racketmanager\Application\Player\Ports\Player_Service_Interface;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use function __;

/**
 * Handles the [match-detail] query: loads the fixture, its rubbers (optionally for one player)
 * and the league/tournament classification without mutating the fixture with presentation data.
 */
readonly class Get_Match_Detail_Handler {
    /**
     * Initialises the service with fixture detail and player service dependencies.
     *
     * @param Fixture_Detail_Service_Interface $fixture_detail_service Fixture detail service dependency.
     * @param Player_Service_Interface         $player_service Player service dependency.
     *
     * @return void
     */
    public function __construct(
        private Fixture_Detail_Service_Interface $fixture_detail_service,
        private Player_Service_Interface $player_service
    ) {}

    /**
     * Handles a match detail query and returns the resolved match detail result.
     *
     * @param Get_Match_Detail_Query $query Query containing the match, player, and template context.
     *
     * @return Match_Detail_Result Match detail result, or an error result when the match cannot be resolved.
     */
    public function handle( Get_Match_Detail_Query $query ): Match_Detail_Result {
        if ( 0 === $query->match_id ) {
            return Match_Detail_Result::error( __( 'Match id not found', 'racketmanager' ) );
        }

        $player     = null;
        $rubber_for = null;
        if ( $query->player_id ) {
            try {
                $player     = $this->player_service->get_player( $query->player_id );
                $rubber_for = $player->id;
            } catch ( Player_Not_Found_Exception ) { //phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
                // Player not found; proceed without player details.
            }
        }

        $details = $this->fixture_detail_service->get_fixture_with_details( $query->match_id, false, $rubber_for ?: null );
        if ( ! $details ) {
            return Match_Detail_Result::error( __( 'Match not found', 'racketmanager' ) );
        }

        $match = $details->fixture;
        $final = $match->get_final();
        $round = null === $final ? '' : $final;
        $type  = null === $final ? 'league' : 'tournament';

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
