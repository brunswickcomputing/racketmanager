<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Fixture;

use Closure;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Update_Authorizer_Interface;

final class Lazy_Fixture_Update_Authorizer implements Fixture_Update_Authorizer_Interface {
	private Closure $authorize;

	public function __construct( Closure $authorize ) {
		$this->authorize = $authorize;
	}

	public function is_update_allowed( Fixture $fixture ): object {
		return ( $this->authorize )( $fixture );
	}
}