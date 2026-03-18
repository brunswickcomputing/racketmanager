<?php

namespace Racketmanager\Services\Fixture;

use Racketmanager\Domain\Fixture;
use Racketmanager\Domain\Result;

/**
 * Service for managing fixture results and state transitions.
 * Orchestrates logic formerly in Racketmanager_Match.
 */
final class Fixture_Result_Manager
{
    /**
     * Update a fixture with a new result.
     * 
     * @param Fixture $fixture The fixture to update.
     * @param Result $result The new result.
     * @param string|null $confirmed Confirmation status ('Y', 'N', or null).
     * @return void
     */
    public function update_result(Fixture $fixture, Result $result, ?string $confirmed = null): void
    {
        $fixture->set_result($result);
        
        if ($confirmed !== null) {
            $fixture->set_confirmed($confirmed);
        }

        if ($result->get_status() !== null) {
            $fixture->set_status($result->get_status());
        }

        // TODO: Orchestrate notifications, league updates, and progression here.
        // These will be extracted from Racketmanager_Match in subsequent steps.
    }

    /**
     * Confirm a fixture result.
     * 
     * @param Fixture $fixture
     * @param string $actioned_by
     * @param string|null $comments
     * @return void
     */
    public function confirm_result(Fixture $fixture, string $actioned_by, ?string $comments = null): void
    {
        $fixture->set_confirmed('Y');
        if ($comments) {
            $fixture->set_comments($comments);
        }
        
        // TODO: Handle post-confirmation logic (e.g., updating standings).
    }
}
