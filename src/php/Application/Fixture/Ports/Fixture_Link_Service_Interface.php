<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Fixture\Ports;

use Racketmanager\Domain\Fixture\Fixture;

/**
 * Port interface for fixture URL and link generation.
 */
interface Fixture_Link_Service_Interface {
    /**
     * Generate HTML link for fixture.
     *
     * @param Fixture $fixture
     * @param object $league
     * @param object|null $home_team
     * @param object|null $away_team
     *
     * @return string
     */
    public function get_fixture_link( Fixture $fixture, object $league, ?object $home_team, ?object $away_team ): string;

    /**
     * Generate HTML link for tournament fixture.
     *
     * @param Fixture $fixture
     * @param object $league
     *
     * @return string
     */
    public function get_tournament_link( Fixture $fixture, object $league ): string;

    /**
     * Resolve fixture link based on tournament flag.
     *
     * @param Fixture $fixture
     * @param object $league
     * @param object|null $home_team
     * @param object|null $away_team
     *
     * @return string
     */
    public function resolve_fixture_link( Fixture $fixture, object $league, ?object $home_team, ?object $away_team ): string;

    /**
     * Generate standard league fixture link.
     *
     * @param Fixture $fixture
     * @param object $league
     * @param object|null $home_team
     * @param object|null $away_team
     *
     * @return string
     */
    public function generate_standard_fixture_link( Fixture $fixture, object $league, ?object $home_team, ?object $away_team ): string;
}
