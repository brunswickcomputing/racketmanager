<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Fixture;

use Racketmanager\Domain\DTO\Team\Team_Details_DTO;

interface Fixture_Team_Resolver_Interface {
	public function get_team_details( int|string|null $team_id ): Team_Details_DTO;
	public function derive_team_details( string $team_ref ): ?Team_Details_DTO;
}
