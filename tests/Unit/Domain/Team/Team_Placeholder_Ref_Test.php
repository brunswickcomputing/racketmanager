<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Team;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Team\Team_Placeholder_Ref;
use Racketmanager\Domain\Team\Team_Placeholder_Role;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

final class Team_Placeholder_Ref_Test extends TestCase {
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_parse_winner_reference(): void {
		$ref = Team_Placeholder_Ref::parse( '1_semi_145' );
		$this->assertNotNull( $ref );
		$this->assertSame( Team_Placeholder_Role::WINNER, $ref->role );
		$this->assertTrue( $ref->is_winner() );
		$this->assertFalse( $ref->is_loser() );
		$this->assertSame( 'Winner of', $ref->default_label() );
		$this->assertSame( 145, $ref->fixture_id );
	}

	public function test_parse_loser_reference(): void {
		$ref = Team_Placeholder_Ref::parse( '2_final_999' );
		$this->assertNotNull( $ref );
		$this->assertSame( Team_Placeholder_Role::LOSER, $ref->role );
		$this->assertFalse( $ref->is_winner() );
		$this->assertTrue( $ref->is_loser() );
		$this->assertSame( 'Loser of', $ref->default_label() );
		$this->assertSame( 999, $ref->fixture_id );
	}

	public function test_parse_without_numeric_fixture_id(): void {
		$ref = Team_Placeholder_Ref::parse( '1_semi' );
		$this->assertNotNull( $ref );
		$this->assertSame( Team_Placeholder_Role::WINNER, $ref->role );
		$this->assertTrue( $ref->is_winner() );
		$this->assertSame( 'Winner of', $ref->default_label() );
		$this->assertNull( $ref->fixture_id );
	}

	public function test_parse_invalid_prefix(): void {
		$this->assertNull( Team_Placeholder_Ref::parse( '3_semi_1' ) );
		$this->assertNull( Team_Placeholder_Ref::parse( 'invalid_format' ) );
	}
}
