<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Matches\Ports;

use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Fixture\Fixture;

/**
 * Driven port used by match listing query handlers to retrieve fixtures.
 */
interface Fixture_Listing_Finder_Interface {
    /**
     * Fixtures scheduled on the given date for a competition type.
     *
     * @param string           $match_date Validated date (Y-m-d).
     * @param Competition_Type $type       Competition type.
     *
     * @return Fixture[]
     */
    public function find_by_match_date( string $match_date, Competition_Type $type ): array;

    /**
     * Fixtures with a recorded score, updated within the last $days days.
     *
     * @param int              $days           Look-back window in days (0 = no window).
     * @param Competition_Type $type           Competition type.
     * @param int|null         $club_id        Restrict to fixtures involving a club's teams.
     * @param int|null         $competition_id Restrict to a competition.
     * @param string|null      $age_group      Restrict to a validated age group.
     *
     * @return Fixture[]
     */
    public function find_latest_results( int $days, Competition_Type $type, ?int $club_id = null, ?int $competition_id = null, ?string $age_group = null ): array;
}
