<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Fixture;

use Racketmanager\Domain\DTO\Team\Team_Details_DTO;

/**
 * Defines a contract for resolving fixture team information from either a team identifier or a team reference.
 */
interface Fixture_Team_Resolver_Interface {
    /**
     * Retrieves the details for a team identified by the given team ID.
     *
     * @param int|string|null $team_id The team identifier. May be null when no team is specified.
     *
     * @return Team_Details_DTO The team details data transfer object.
     */
    public function get_team_details( int|string|null $team_id ): Team_Details_DTO;

    /**
     * Derives team details for the specified team reference.
     *
     * @param string $team_ref The unique reference used to identify the team.
     *
     * @return Team_Details_DTO|null The derived team details, or null if no matching team details are available.
     */
    public function derive_team_details( string $team_ref ): ?Team_Details_DTO;
}
