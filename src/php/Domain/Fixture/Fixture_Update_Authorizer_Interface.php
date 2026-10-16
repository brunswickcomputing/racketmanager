<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Fixture;

interface Fixture_Update_Authorizer_Interface {
	public function is_update_allowed( Fixture $fixture ): object;
}