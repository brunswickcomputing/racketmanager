<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;

/**
 * Builds real Competition aggregates for tests.
 *
 * Seasons are passed through unchanged: the Season_Collection keys by array key, so a list
 * (`[ [ 'name' => '2026' ] ]`) and a map (`[ '2026' => [ 'name' => '2026' ] ]`) are not equivalent.
 * Non-empty seasons are applied via set_seasons(), so `current_season` is only set when the caller asks for it.
 */
final class Competition_Test_Factory {
    /**
     * @param array|null $seasons       Seasons exactly as the test needs them; null or [] leaves the competition without seasons.
     * @param array|null $finals        Overrides the finals generated for cup competitions.
     * @param array|null $current_season Assigned to the public current_season property.
     */
    public static function create(
        int $id = 5,
        string $name = 'Summer League',
        Competition_Type $type = Competition_Type::LEAGUE,
        ?array $seasons = null,
        ?array $settings = null,
        ?array $finals = null,
        ?array $current_season = null
    ): Competition {
        $competition = new Competition( $name, $type, 'open', null, [], $id );
        if ( ! empty( $seasons ) ) {
            $competition->set_seasons( $seasons );
        }
        if ( null !== $settings ) {
            $competition->set_settings( $settings );
        }
        if ( null !== $finals ) {
            $competition->finals = $finals;
        }
        if ( null !== $current_season ) {
            $competition->current_season = $current_season;
        }
        return $competition;
    }

    /**
     * A single season in list shape, as most shortcode tests use it.
     */
    public static function season_list( string $name = '2026', array $extra = [] ): array {
        return [ array_merge( [ 'name' => $name ], $extra ) ];
    }

    /**
     * A single season in map shape (keyed by season name), as the season-lookup tests use it.
     */
    public static function season_map( string $name = '2026', array $extra = [] ): array {
        return [ $name => array_merge( [ 'name' => $name ], $extra ) ];
    }
}
