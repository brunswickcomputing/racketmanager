<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Fixture;

use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Util\Util;

/**
 * Service to encapsulate fixture link generation logic.
 */
class Fixture_Link_Service {
    private const string MATCH_URL_PATH = '/match/';
    private const string LEAGUE_URL_PATH = '/league/';
    private const string TOURNAMENT_URL_PATH = '/tournament/';

    private Tournament_Service $tournament_service;

    /**
     * Fixture_Link_Service constructor.
     */
    public function __construct() {
    }

    /**
     * Set the tournament service.
     */
    public function set_tournament_service( Tournament_Service $tournament_service ): void {
        $this->tournament_service = $tournament_service;
    }

    /**
     * Get the fixture link for a fixture.
     */
    public function get_fixture_link( Fixture $fixture, object $league, ?object $home_team, ?object $away_team ): string {
        $type = $league->get_competition_type();

        if ( 'tournament' === $type ) {
            return $this->get_tournament_link( $fixture, $league );
        }

        return $this->resolve_fixture_link( $fixture, $league, $home_team, $away_team );
    }

    /**
     * Get the tournament link for a fixture.
     */
    public function get_tournament_link( Fixture $fixture, object $league ): string {
        $tournament_code = $league->get_event_id() . ',' . $fixture->get_season();

        try {
            if ( ! isset( $this->tournament_service ) ) {
                return home_url( self::LEAGUE_URL_PATH . Util::seo_url( $league->get_name() ) . self::MATCH_URL_PATH . $fixture->get_id() . '/' );
            }

            $tournament     = $this->tournament_service->get_tournament( $tournament_code, 'shortcode' );
            $home_team_name = $fixture->get_custom()['home_team_name'] ?? '';
            $away_team_name = $fixture->get_custom()['away_team_name'] ?? '';

            if ( ! empty( $home_team_name ) && ! empty( $away_team_name ) ) {
                return home_url( self::TOURNAMENT_URL_PATH . Util::seo_url( $tournament->name ) . self::MATCH_URL_PATH . Util::seo_url( $league->get_name() ) . '/' . Util::seo_url( $home_team_name ) . '-vs-' . Util::seo_url( $away_team_name ) . '/' . $fixture->get_id() . '/' );
            }
        } catch ( Tournament_Not_Found_Exception ) {
            // Fallback to league link if tournament not found
        }

        return home_url( self::LEAGUE_URL_PATH . Util::seo_url( $league->get_name() ) . self::MATCH_URL_PATH . $fixture->get_id() . '/' );
    }

    /**
     * Resolve fixture link for a fixture.
     */
    public function resolve_fixture_link( Fixture $fixture, object $league, ?object $home_team, ?object $away_team ): string {
        $type = $league->get_competition_type();

        if ( 'box' === $type ) {
            $url = home_url( self::LEAGUE_URL_PATH );
            $url .= Util::seo_url( $league->get_name() ) . self::MATCH_URL_PATH . $fixture->get_id() . '/';

            return $url;
        }

        return $this->generate_standard_fixture_link( $fixture, $league, $home_team, $away_team );
    }

    /**
     * Generate a standard fixture link for a fixture.
     */
    public function generate_standard_fixture_link( Fixture $fixture, object $league, ?object $home_team, ?object $away_team ): string {
        if ( ! empty( $league->is_championship ) ) {
            $match_ref = $fixture->get_final();
        } else {
            $match_ref = 'day' . $fixture->get_match_day();
        }

        $home_team_name = $home_team->team->get_name() ?? '';
        $away_team_name = $away_team->team->get_name() ?? '';

        if ( ! empty( $home_team_name ) && ! empty( $away_team_name ) ) {
            $url = home_url( self::MATCH_URL_PATH . Util::seo_url( $league->get_name() ) . '/' . $fixture->get_season() . '/' . $match_ref . '/' . Util::seo_url( $home_team_name ) . '-vs-' . Util::seo_url( $away_team_name ) . '/' );
        } else {
            return '';
        }

        if ( $fixture->get_leg() ) {
            $url .= 'leg-' . $fixture->get_leg() . '/';
        }

        return $url;
    }
}
