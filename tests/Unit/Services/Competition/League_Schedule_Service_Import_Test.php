<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Competition;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Services\Competition\League_Schedule_Service;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Schedule_Round_Robin;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use RuntimeException;

class League_Schedule_Service_Import_Test extends TestCase {
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
}
