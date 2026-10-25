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

		$container = new Simple_Container();
		$container->set( 'fixture_repository', $fixture_repository );

		$racketmanager            = new stdClass();
		$racketmanager->container = $container;
		$GLOBALS['racketmanager'] = $racketmanager;

		$this->assertSame(
			'Loser of Westcliff Lawn Mixed 1 vs Rayleigh Mixed 1',
			Util::generate_team_name( '2_last-16_145957' )
		);
	}
}
