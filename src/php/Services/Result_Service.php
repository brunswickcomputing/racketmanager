<?php

namespace Racketmanager\Services;

use Racketmanager\Domain\Result;
use Racketmanager\Domain\Fixture;

class Result_Service {
    /**
     * Applies a result to a fixture and triggers subsequent actions (draw updates, etc.)
     *
     * @param Fixture $fixture The fixture to update.
     * @param Result $result The result to apply.
     */
    public function apply_to_fixture( Fixture $fixture, Result $result ): void {
        // 1. Update the fixture object with the result details
        // $fixture->set_result( $result );

        // 2. Persist to database (via Repository)
        // $this->fixture_repository->save( $fixture );

        // 3. For tournaments, trigger championship progression
        /*
        if ( $fixture->is_championship_match() ) {
            $championship_manager = new Championship_Manager();
            $championship_manager->proceed( $fixture->league->championship, $fixture->round );
        }
        */
    }
}
