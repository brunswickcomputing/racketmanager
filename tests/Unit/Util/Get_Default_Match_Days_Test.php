<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Util;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Util\Util;

#[AllowMockObjectsWithoutExpectations]
final class Get_Default_Match_Days_Test extends TestCase {
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_cup_with_injected_club_repository_calculates_log2(): void {
		$club_repository = $this->createMock( Club_Repository_Interface::class );
		$club_repository->expects( $this->once() )
			->method( 'find_all' )
			->with( array( 'count' => true, 'type' => 'affiliated' ) )
			->willReturn( 8 );

		$this->assertSame( 3, Util::get_default_match_days( 'cup', club_repository: $club_repository ) );
	}

	public function test_cup_with_injected_club_service_calculates_log2(): void {
		$club_service = $this->createMock( Club_Service::class );
		$club_service->expects( $this->once() )
			->method( 'get_clubs' )
			->with( array( 'count' => true, 'type' => 'affiliated' ) )
			->willReturn( 16 );

		$this->assertSame( 4, Util::get_default_match_days( 'cup', club_service: $club_service ) );
	}

	public function test_cup_with_zero_clubs_falls_back_to_option_rounds(): void {
		$club_repository = $this->createMock( Club_Repository_Interface::class );
		$club_repository->expects( $this->once() )
			->method( 'find_all' )
			->with( array( 'count' => true, 'type' => 'affiliated' ) )
			->willReturn( 0 );

		$option_service = $this->createMock( Option_Service::class );
		$option_service->expects( $this->once() )
			->method( 'get_options' )
			->willReturn( array( 'championship' => array( 'numRounds' => 5 ) ) );

		$this->assertSame( 5, Util::get_default_match_days( 'cup', option_service: $option_service, club_repository: $club_repository ) );
	}

	public function test_tournament_uses_configured_num_rounds(): void {
		$option_service = $this->createMock( Option_Service::class );
		$option_service->expects( $this->once() )
			->method( 'get_options' )
			->willReturn( array( 'championship' => array( 'numRounds' => 6 ) ) );

		$this->assertSame( 6, Util::get_default_match_days( 'tournament', option_service: $option_service ) );
	}

	public function test_tournament_defaults_to_one_when_not_configured(): void {
		$option_service = $this->createMock( Option_Service::class );
		$option_service->expects( $this->once() )
			->method( 'get_options' )
			->willReturn( array() );

		$this->assertSame( 1, Util::get_default_match_days( 'tournament', option_service: $option_service ) );
	}

	public function test_default_type_returns_zero(): void {
		$this->assertSame( 0, Util::get_default_match_days( 'league' ) );
		$this->assertSame( 0, Util::get_default_match_days( 'other' ) );
	}
}
