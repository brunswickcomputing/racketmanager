<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Fixture;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Status_Policy;

class Fixture_Status_Policy_Test extends TestCase {
	public function test_calculate_status_flags_walkover(): void {
		$flags = Fixture_Status_Policy::calculate_status_flags( 1, 10 );
		$this->assertTrue( $flags['is_walkover'] );
		$this->assertFalse( $flags['is_retired'] );
		$this->assertFalse( $flags['is_shared'] );
		$this->assertFalse( $flags['is_abandoned'] );
		$this->assertFalse( $flags['is_withdrawn'] );
		$this->assertFalse( $flags['is_cancelled'] );
		$this->assertFalse( $flags['is_pending'] );
	}

	public function test_calculate_status_flags_pending_when_winner_id_empty(): void {
		$flags = Fixture_Status_Policy::calculate_status_flags( null, null );
		$this->assertTrue( $flags['is_pending'] );

		$flags2 = Fixture_Status_Policy::calculate_status_flags( null, 0 );
		$this->assertTrue( $flags2['is_pending'] );
	}

	public function test_apply_to_fixture(): void {
		$fixture = new Fixture();
		$fixture->status = 2; // Retired
		$fixture->winner_id = 5;

		Fixture_Status_Policy::apply_to_fixture( $fixture );

		$this->assertFalse( $fixture->is_walkover );
		$this->assertTrue( $fixture->is_retired );
		$this->assertFalse( $fixture->is_shared );
		$this->assertFalse( $fixture->is_pending );
	}
}
