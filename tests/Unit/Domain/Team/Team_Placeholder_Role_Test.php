<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Team;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Team\Team_Placeholder_Role;

final class Team_Placeholder_Role_Test extends TestCase {

	public function test_enum_cases_and_values(): void {
		$this->assertSame( 'winner', Team_Placeholder_Role::WINNER->value );
		$this->assertSame( 'loser', Team_Placeholder_Role::LOSER->value );
	}

	public function test_enum_can_be_instantiated_from_string(): void {
		$this->assertSame( Team_Placeholder_Role::WINNER, Team_Placeholder_Role::from( 'winner' ) );
		$this->assertSame( Team_Placeholder_Role::LOSER, Team_Placeholder_Role::from( 'loser' ) );
		$this->assertSame( Team_Placeholder_Role::WINNER, Team_Placeholder_Role::tryFrom( 'winner' ) );
		$this->assertSame( Team_Placeholder_Role::LOSER, Team_Placeholder_Role::tryFrom( 'loser' ) );
		$this->assertNull( Team_Placeholder_Role::tryFrom( 'unknown' ) );
	}

	public function test_enum_autoloads_independently(): void {
		$this->assertTrue( enum_exists( Team_Placeholder_Role::class ) );
		$this->assertCount( 2, Team_Placeholder_Role::cases() );
	}
}
