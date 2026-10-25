<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Competition;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Competition\League_Schedule_Service;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Schedule_Round_Robin;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use RuntimeException;
use stdClass;

require_once __DIR__ . '/../../../wp-stubs.php';

class League_Schedule_Service_Import_Test extends TestCase {
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$GLOBALS['wp_stubs_leagues'] = array();
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_schedule_service_imports_resolve(): void {
		$this->assertTrue( class_exists( Util::class ) );
		$this->assertTrue( class_exists( Util_Lookup::class ) );

		$service = new League_Schedule_Service();
		$this->assertInstanceOf( League_Schedule_Service::class, $service );
		$this->assertNull( $service->get_fixture_service() );
	}

	public function test_create_match_schedule_throws_when_fixture_service_is_missing(): void {
		$service = new League_Schedule_Service();
		$league  = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot create match schedule: Fixture_Service collaborator is required.' );
		$service->create_match_schedule( $league, [], [], [], '2026', false );
	}

	public function test_create_match_schedule_delegates_to_fixture_service_when_configured(): void {
		$fixture_service = $this->createMock( Fixture_Service::class );
		$service         = new League_Schedule_Service( $fixture_service );
		$this->assertSame( $fixture_service, $service->get_fixture_service() );

		$league     = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$league->id = 10;
		$teams      = [
			(object) [ 'id' => 1, 'match_day' => 'Saturday', 'match_time' => '10:00', 'club' => (object) [ 'shortcode' => 'CL1' ] ],
			(object) [ 'id' => 2, 'match_day' => 'Sunday', 'match_time' => '14:00', 'club' => (object) [ 'shortcode' => 'CL2' ] ],
		];
		$rounds     = [
			[
				'fixtures' => [
					[ 'home' => 1, 'away' => 2 ],
				],
			],
		];

		$fixture_service->expects( $this->once() )
			->method( 'create_fixture' );

		$service->create_match_schedule( $league, $rounds, $teams, [ '2026-06-01' ], '2026', false );
	}

	public function test_generate_box_league_matches_schedules_valid_leagues(): void {
		$league1 = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$league1->id = 101;
		$league2 = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$league2->id = 102;

		$event = new class( $league1, $league2 ) {
			public function __construct( private League $league1, private League $league2 ) {}
			public function get_leagues(): array {
				return [ $this->league1, 999, $this->league2 ];
			}
		};

		$GLOBALS['wp_stubs_leagues'][999] = (object) [ 'id' => 999 ]; // Not an instance of League

		$service = $this->getMockBuilder( League_Schedule_Service::class )
			->onlyMethods( [ 'schedule_league' ] )
			->getMock();

		$service->expects( $this->exactly( 2 ) )
			->method( 'schedule_league' )
			->with( $this->logicalOr(
				$this->identicalTo( $league1 ),
				$this->identicalTo( $league2 )
			) );

		$this->assertSame( array( 'scheduled' => 2, 'skipped' => 1 ), $service->generate_box_league_matches( $event ) );
	}

	public function test_generate_box_league_matches_resolves_numeric_league_ids_from_helper(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$league->id = 55;
		$GLOBALS['wp_stubs_leagues'][55] = $league;

		$event = (object) [
			'leagues' => [ 55, 999 ],
		];
		$GLOBALS['wp_stubs_leagues'][999] = null;

		$service = $this->getMockBuilder( League_Schedule_Service::class )
			->onlyMethods( [ 'schedule_league' ] )
			->getMock();

		$service->expects( $this->once() )
			->method( 'schedule_league' )
			->with( $this->identicalTo( $league ) );

		$this->assertSame( array( 'scheduled' => 1, 'skipped' => 1 ), $service->generate_box_league_matches( $event ) );
	}

	public function test_generate_box_league_matches_resolves_raw_db_rows_via_repository(): void {
		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$league->id = 55;
		$row = (object) array( 'id' => 55, 'event_id' => 12, 'title' => 'Division 1' );
		$repository = $this->createMock( League_Repository_Interface::class );
		$repository->expects( $this->once() )->method( 'find_by_id' )->with( 55 )->willReturn( $league );
		$service = $this->getMockBuilder( League_Schedule_Service::class )
			->setConstructorArgs( array( null, null, $repository ) )
			->onlyMethods( array( 'schedule_league' ) )
			->getMock();
		$service->expects( $this->once() )->method( 'schedule_league' )->with( $league );

		$this->assertSame( array( 'scheduled' => 1, 'skipped' => 0 ), $service->generate_box_league_matches( (object) array( 'leagues' => array( $row ) ) ) );
	}

	public function test_generate_box_league_matches_counts_empty_and_unresolvable_lists(): void {
		$repository = $this->createMock( League_Repository_Interface::class );
		$repository->expects( $this->once() )->method( 'find_by_id' )->with( 999 )->willReturn( null );
		$service = $this->getMockBuilder( League_Schedule_Service::class )
			->setConstructorArgs( array( null, null, $repository ) )
			->onlyMethods( array( 'schedule_league' ) )
			->getMock();
		$service->expects( $this->never() )->method( 'schedule_league' );

		$this->assertSame( array( 'scheduled' => 0, 'skipped' => 0 ), $service->generate_box_league_matches( (object) array( 'leagues' => array() ) ) );
		$this->assertSame( array( 'scheduled' => 0, 'skipped' => 1 ), $service->generate_box_league_matches( (object) array( 'leagues' => array( (object) array( 'id' => 999 ) ) ) ) );
	}
}
