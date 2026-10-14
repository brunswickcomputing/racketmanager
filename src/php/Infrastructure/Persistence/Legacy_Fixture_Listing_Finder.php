<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Persistence;

use Racketmanager\Application\Matches\Ports\Fixture_Listing_Finder_Interface;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\RacketManager;

/**
 * Driven adapter implementing the fixture listing port on top of the existing
 * RacketManager::get_matches() query builder (which hydrates via Fixture_Repository).
 *
 * Only already-validated, strictly typed values reach the legacy query builder.
 */
final readonly class Legacy_Fixture_Listing_Finder implements Fixture_Listing_Finder_Interface {
    public function __construct( private RacketManager $racketmanager ) {}

    public function find_by_match_date( string $match_date, Competition_Type $type ): array {
        return $this->only_fixtures(
            $this->racketmanager->get_matches(
                [
                    'match_date'       => $match_date,
                    'competition_type' => $type->value,
                ]
            )
        );
    }

    public function find_latest_results( int $days, Competition_Type $type, ?int $club_id = null, ?int $competition_id = null, ?string $age_group = null ): array {
        return $this->only_fixtures(
            $this->racketmanager->get_matches(
                [
                    'days'             => $days,
                    'competition_type' => $type->value,
                    'time'             => 'latest',
                    'history'          => $days > 0 ? $days : false,
                    'club'             => $club_id ?? false,
                    'competition_id'   => $competition_id ?? false,
                    'age_group'        => $age_group ?? false,
                ]
            )
        );
    }

    /**
     * @return Fixture[]
     */
    private function only_fixtures( array|int $matches ): array {
        if ( ! is_array( $matches ) ) {
            return [];
        }

        return array_values( array_filter( $matches, static fn( $match ): bool => $match instanceof Fixture ) );
    }
}
