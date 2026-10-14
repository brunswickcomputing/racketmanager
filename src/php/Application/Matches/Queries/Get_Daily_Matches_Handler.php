<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Matches\Queries;

use Racketmanager\Application\Matches\DTOs\Match_List_Result;
use Racketmanager\Application\Matches\Ports\Fixture_Listing_Finder_Interface;
use Racketmanager\Domain\Competition\Competition_Type;

/**
 * Handles the [dailymatches] query.
 */
class Get_Daily_Matches_Handler {
    public function __construct( private readonly Fixture_Listing_Finder_Interface $finder ) {}

    public function handle( Get_Daily_Matches_Query $query ): Match_List_Result {
        $type = Competition_Type::tryFrom( $query->competition_type );
        if ( null === $type ) {
            // Unknown competition types never matched any fixture in the legacy SQL filter.
            return new Match_List_Result( [], $query->match_date );
        }

        return new Match_List_Result(
            $this->finder->find_by_match_date( $query->match_date, $type ),
            $query->match_date
        );
    }
}
