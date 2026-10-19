<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Fixture;

use Closure;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture_Team_Resolver_Interface;

final class Lazy_Fixture_Team_Resolver implements Fixture_Team_Resolver_Interface {
	private Closure $resolver;
	private ?Fixture_Team_Resolver_Interface $resolved = null;

	public function __construct( Closure $resolver ) {
		$this->resolver = $resolver;
	}

	private function get_resolver(): Fixture_Team_Resolver_Interface {
		if ( null === $this->resolved ) {
			$this->resolved = ( $this->resolver )();
		}

		return $this->resolved;
	}

	public function get_team_details( int|string|null $team_id ): Team_Details_DTO {
		return $this->get_resolver()->get_team_details( $team_id );
	}

	public function derive_team_details( string $team_ref ): ?Team_Details_DTO {
		return $this->get_resolver()->derive_team_details( $team_ref );
	}
}
