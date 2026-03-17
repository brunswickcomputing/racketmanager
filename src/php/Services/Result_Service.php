<?php

namespace Racketmanager\Services;

use Racketmanager\Domain\Championship;
use Racketmanager\Domain\Result;
use Racketmanager\Domain\Fixture;
use Racketmanager\Repositories\Fixture_Repository;
use function Racketmanager\get_league;

class Result_Service {
    /**
     * @var Fixture_Repository
     */
    private Fixture_Repository $fixture_repository;

    public function __construct( Fixture_Repository $fixture_repository ) {
        $this->fixture_repository = $fixture_repository;
    }

    /**
     * Applies a result to a fixture and triggers subsequent actions (draw updates, etc.)
     *
     * @param Fixture $fixture The fixture to update.
     * @param Result $result The result to apply.
     */
    public function apply_to_fixture( Fixture $fixture, Result $result ): void {
        // 1. Update the fixture object with the result details
        $fixture->set_result( $result );

        // 2. Persist to database (via Repository)
        $this->fixture_repository->save( $fixture );

        // 3. For tournaments, trigger championship progression
        if ( ! empty( $fixture->get_final() ) ) {
            $championship_manager = new Championship_Manager();
            $championship         = $this->get_championship_for_fixture( $fixture );
            if ( $championship ) {
                $round = $this->get_round_for_fixture( $fixture, $championship );
                if ( $round !== null ) {
                    $championship_manager->proceed( $championship, $round );
                }
            }
        }
    }

    /**
     * @param Fixture $fixture
     * @return Championship|null
     */
    private function get_championship_for_fixture( Fixture $fixture ): ?Championship {
        $league = get_league( $fixture->get_league_id() );
        return $league?->championship ?? null;
    }

    /**
     * @param Fixture $fixture
     * @param Championship $championship
     * @return int|null
     */
    private function get_round_for_fixture( Fixture $fixture, Championship $championship ): ?int {
        $final_key = $fixture->get_final();
        if ( ! $final_key ) {
            return null;
        }

        $finals_by_key = $championship->get_finals_by_key();
        if ( isset( $finals_by_key[ $final_key ]['round'] ) ) {
            return (int) $finals_by_key[ $final_key ]['round'];
        }

        return null;
    }
}
