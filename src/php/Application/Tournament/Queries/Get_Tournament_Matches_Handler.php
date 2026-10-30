<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Application\Tournament\DTOs\Tournament_Matches_Result;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Tournament_Service;
use function __;

class Get_Tournament_Matches_Handler {
    public function __construct(
        private readonly Tournament_Service $tournament_service
    ) {}

    public function handle( Get_Tournament_Matches_Query $query ): Tournament_Matches_Result {
        if ( ! $query->tournament_id ) {
            return Tournament_Matches_Result::error( __( 'Tournament not found', 'racketmanager' ) );
        }

        try {
            $tournament = $this->tournament_service->get_tournament( $query->tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return Tournament_Matches_Result::error( $e->getMessage() );
        }

        $fixture_dates = $this->tournament_service->get_fixture_dates_for_tournament( $tournament );
        $fixture_date  = $query->match_date;

        if ( empty( $fixture_date ) && ! empty( $fixture_dates ) ) {
            $fixture_date = end( $fixture_dates );
        }

        $tournament_fixtures = $this->tournament_service->get_fixtures_by_date_for_tournament( $tournament, $fixture_date );

        return new Tournament_Matches_Result(
            tournament: $tournament,
            fixture_dates: $fixture_dates,
            fixture_date: $fixture_date ?: null,
            tournament_fixtures: $tournament_fixtures,
            tab: 'matches',
            template: $query->template
        );
    }
}
