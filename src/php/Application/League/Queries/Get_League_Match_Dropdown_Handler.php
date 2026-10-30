<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Application\League\DTOs\League_Match_Dropdown_Result;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;
use function __;
use function is_array;

/**
 * Query handler for [match-dropdown] shortcode.
 */
class Get_League_Match_Dropdown_Handler {
    public function __construct(
        private readonly League_Service $league_service,
        private readonly League_Query_Service $league_query_service
    ) {}

    public function handle( Get_League_Match_Dropdown_Query $query ): League_Match_Dropdown_Result {
        $league = null;
        if ( ! empty( $query->league_id ) ) {
            $league = $this->league_service->get_league( $query->league_id );
        }
        if ( ! $league ) {
            return League_Match_Dropdown_Result::error( __( 'League not found', 'racketmanager' ) );
        }

        if ( ! $query->season ) {
            return League_Match_Dropdown_Result::error( __( 'Season not found', 'racketmanager' ) );
        }

        $matches = $this->league_query_service->get_matches( $league, [
            'limit'            => false,
            'match_day'        => -1,
            'season'           => $query->season,
            'reset_query_args' => true,
        ] );

        return new League_Match_Dropdown_Result(
            league: $league,
            matches: is_array( $matches ) ? $matches : [],
            match_id: $query->match_id,
            season: $query->season,
            template: $query->template
        );
    }
}
