<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Application\League\DTOs\League_Season_Dropdown_Result;
use Racketmanager\Services\League_Service;
use function __;

/**
 * Query handler for [season-dropdown] shortcode.
 */
class Get_League_Season_Dropdown_Handler {
    public function __construct(
        private readonly League_Service $league_service
    ) {}

    public function handle( Get_League_Season_Dropdown_Query $query ): League_Season_Dropdown_Result {
        $league = null;
        if ( ! empty( $query->league_id ) ) {
            $league = $this->league_service->get_league( $query->league_id );
        }
        if ( ! $league ) {
            return League_Season_Dropdown_Result::error( __( 'League not found', 'racketmanager' ) );
        }

        return new League_Season_Dropdown_Result(
            league: $league,
            season: $query->season,
            template: $query->template
        );
    }
}
