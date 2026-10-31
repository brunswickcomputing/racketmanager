<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Util;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Util\Util;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
final class Generate_Team_Name_Test extends TestCase {
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_generate_team_name_uses_fixture_relations_for_placeholder_title(): void {
		$fixture         = Fixture_Factory::from_object( (object) array( 'id' => 145957 ) );
		$fixture->teams  = array(
			'home' => (object) array( 'title' => 'Westcliff Lawn Mixed 1' ),
			'away' => (object) array( 'title' => 'Rayleigh Mixed 1' ),
		);

		$fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
		$fixture_repository->expects( $this->once() )
			->method( 'find_by_id_with_relations' )
			->with( 145957 )
			->willReturn( $fixture );
		$fixture_repository->expects( $this->never() )
			->method( 'find_by_id' );

		$this->assertSame(
			'Loser of Westcliff Lawn Mixed 1 vs Rayleigh Mixed 1',
			Util::generate_team_name( '2_last-16_145957', $fixture_repository )
		);
	}

	public function test_generate_team_name_winner_placeholder_title(): void {
		$fixture        = Fixture_Factory::from_object( (object) array( 'id' => 145958 ) );
		$fixture->teams = array(
			'home' => (object) array( 'title' => 'Team Alpha' ),
			'away' => (object) array( 'title' => 'Team Beta' ),
		);

		$fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
		$fixture_repository->expects( $this->once() )
			->method( 'find_by_id_with_relations' )
			->with( 145958 )
			->willReturn( $fixture );

		$this->assertSame(
			'Winner of Team Alpha vs Team Beta',
			Util::generate_team_name( '1_final_145958', $fixture_repository )
		);
	}

	public function test_generate_team_name_returns_null_when_prefix_is_invalid(): void {
		$this->assertNull( Util::generate_team_name( 'invalid_format' ) );
		$this->assertNull( Util::generate_team_name( '3_round_145957' ) );
	}

	public function test_generate_team_name_returns_prefix_only_when_match_not_found(): void {
		$fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
		$fixture_repository->expects( $this->once() )
			->method( 'find_by_id_with_relations' )
			->with( 999 )
			->willReturn( null );

		$this->assertSame(
			'Winner of',
			Util::generate_team_name( '1_semi_999', $fixture_repository )
		);
	}
}
