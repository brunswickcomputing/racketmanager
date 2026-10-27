<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Settings;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\Event_Factory;
use Racketmanager\Domain\DTO\Competition\Event_Hydration_DTO;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class Event_Mutation_Test extends TestCase {
	use Manages_Wordpress_Globals;

	private $event_repository;
	private Event $event;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		unset( $GLOBALS['racketmanager'] );

		$this->event_repository = $this->createMock( Event_Repository_Interface::class );

		$competition          = $this->createMock( Competition::class );
		$competition_settings = new Competition_Settings( [ 'mode' => 'league' ] );
		$competition->settings = $competition_settings;
		$competition->type = Competition_Type::LEAGUE;
		$competition->is_player_entry = false;
		$competition->standings = [];

		$event_raw = new stdClass();
		$event_raw->id = 42;
		$event_raw->name = 'Men\'s Doubles';
		$event_raw->type = 'doubles';
		$event_raw->competition_id = 1;
		$event_raw->seasons = '[]';
		$event_raw->num_rubbers = 4;
		$event_raw->num_sets = 3;

		$this->event = Event::from_object( $event_raw, $competition );
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_query_service_get_event_delegates_to_event_repository_find_by_id(): void {
		$dto = new Event_Hydration_DTO(
			id: 42,
			name: 'Men\'s Doubles',
			type: 'doubles',
			num_sets: 3,
			num_rubbers: 4,
			competition_id: 1
		);
		$mock_event = Event_Factory::create_from_dto( $dto );

		$this->event_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 42 )
			->willReturn( $mock_event );

		$instance = ( new \Racketmanager\Services\Competition\Event_Query_Service( $this->event_repository ) )->get_event( 42, 'id' );
		$this->assertInstanceOf( Event::class, $instance );
		$this->assertSame( 42, $instance->id );
		$this->assertSame( 'Men\'s Doubles', $instance->name );
	}

	public function test_query_service_get_event_delegates_to_event_repository_find_by_name(): void {
		$dto = new Event_Hydration_DTO(
			id: 42,
			name: 'Men\'s Doubles',
			type: 'doubles',
			num_sets: 3,
			num_rubbers: 4,
			competition_id: 1
		);
		$mock_event = Event_Factory::create_from_dto( $dto );

		$this->event_repository->expects( $this->once() )
			->method( 'find_by_name' )
			->with( 'Men\'s Doubles' )
			->willReturn( $mock_event );

		$instance = ( new \Racketmanager\Services\Competition\Event_Query_Service( $this->event_repository ) )->get_event( 'Men\'s Doubles', 'name' );
		$this->assertInstanceOf( Event::class, $instance );
		$this->assertSame( 'Men\'s Doubles', $instance->name );
	}

	public function test_set_name_updates_property(): void {
		$this->event->set_name( 'Updated Event Name' );
		$this->assertSame( 'Updated Event Name', $this->event->name );
	}

	public function test_delete_delegates_to_event_repository_delete(): void {
		$this->event_repository->expects( $this->once() )
			->method( 'query_leagues' )
			->with( 42 )
			->willReturn( [] );

		$this->event_repository->expects( $this->once() )
			->method( 'delete' )
			->with( 42 )
			->willReturn( true );

		$comp_service = new Competition_Service(
			$this->createStub( RacketManager::class ),
			$this->createStub( \Racketmanager\Repositories\Interfaces\Competition_Repository_Interface::class ),
			$this->createStub( \Racketmanager\Repositories\Interfaces\Club_Repository_Interface::class ),
			$this->event_repository,
			$this->createStub( \Racketmanager\Repositories\Interfaces\League_Repository_Interface::class ),
			$this->createStub( League_Team_Repository_Interface::class ),
			$this->createStub( \Racketmanager\Repositories\Interfaces\Season_Repository_Interface::class ),
			$this->createStub( \Racketmanager\Repositories\Interfaces\Team_Repository_Interface::class )
		);
		$comp_service->delete_event( $this->event );
	}

	public function test_set_num_leagues_delegates_to_event_repository_count_leagues(): void {
		$this->event_repository->expects( $this->once() )
			->method( 'count_leagues' )
			->with( 42 )
			->willReturn( 5 );

		$service = new Event_Query_Service( $this->event_repository );
		$service->set_num_leagues( $this->event, true );
		$this->assertSame( 5, $this->event->num_leagues );
	}

	public function test_mark_teams_withdrawn_delegates_to_event_repository(): void {
		$this->event_repository->expects( $this->once() )
			->method( 'mark_teams_withdrawn' )
			->with( 42, '2026', 10, 20 )
			->willReturn( true );

		$service = new Event_Query_Service( $this->event_repository );
		$this->assertTrue( $service->mark_teams_withdrawn( $this->event, '2026', 10, 20 ) );
	}

	public function test_mark_teams_entered_delegates_to_event_repository(): void {
		$this->event_repository->expects( $this->once() )
			->method( 'mark_teams_entered' )
			->with( 42, 20, '2026' )
			->willReturn( true );

		$service = new Event_Query_Service( $this->event_repository );
		$this->assertTrue( $service->mark_teams_entered( $this->event, 20, '2026' ) );
	}

	public function test_event_repository_find_by_id_and_name_hydrates_via_factory(): void {
		$dto = new Event_Hydration_DTO(
			id: 99,
			name: 'Mixed Doubles',
			type: 'doubles',
			num_sets: 3,
			num_rubbers: 4,
			competition_id: 1
		);
		$event = Event_Factory::create_from_dto( $dto );

		$this->assertInstanceOf( Event::class, $event );
		$this->assertSame( 99, $event->id );
		$this->assertSame( 'Mixed Doubles', $event->name );
	}

	public function test_set_season_explicit_unknown_season_falls_back_to_latest(): void {
		$event = Event::from_object( (object) [
			'id'      => 10,
			'name'    => 'Men Singles',
			'type'    => 'singles',
			'seasons' => json_encode( [
				'2025' => [ 'name' => '2025' ],
				'2026' => [ 'name' => '2026' ],
			] ),
		] );

		// Explicit unknown season 1999 without strict mode falls back to latest season (2026)
		$event->set_season( '1999', force_overwrite: true, strict: false );
		$this->assertIsArray( $event->current_season );
		$this->assertSame( '2026', $event->current_season['name'] );
	}

	public function test_set_season_default_arguments_unknown_season_falls_back_to_latest_and_sets_match_days(): void {
		$event = Event::from_object( (object) [
			'id'      => 10,
			'name'    => 'Men Singles',
			'type'    => 'singles',
			'seasons' => json_encode( [
				'2025' => [ 'name' => '2025', 'match_days' => [ '2025-05-01' ] ],
				'2026' => [ 'name' => '2026', 'match_days' => [ '2026-05-01', '2026-05-08' ] ],
			] ),
		] );

		$event->set_season( '1999' );
		$this->assertIsArray( $event->current_season );
		$this->assertSame( '2026', $event->current_season['name'] );
		$this->assertSame( 2, $event->num_match_days );
	}

	public function test_set_season_strict_mode_evaluates_to_false_for_unknown_season(): void {
		$event = Event::from_object( (object) [
			'id'      => 10,
			'name'    => 'Men Singles',
			'type'    => 'singles',
			'seasons' => json_encode( [
				'2025' => [ 'name' => '2025' ],
				'2026' => [ 'name' => '2026' ],
			] ),
		] );

		// Request-style strict mode: unknown season yields false, not a latest-season fallback.
		$event->set_season( '1999', force_overwrite: false, strict: true );
		$this->assertFalse( $event->current_season );
	}

	public function test_set_season_force_overwrite_updates_current_season(): void {
		$event = Event::from_object( (object) [
			'id'      => 10,
			'name'    => 'Men Singles',
			'type'    => 'singles',
			'seasons' => json_encode( [
				'2025' => [ 'name' => '2025' ],
				'2026' => [ 'name' => '2026' ],
			] ),
		] );

		$event->set_season();
		$this->assertSame( '2026', $event->current_season['name'] );

		// A known season is applied whether or not force_overwrite is set.
		$event->set_season( '2025', force_overwrite: false );
		$this->assertSame( '2025', $event->current_season['name'] );

		$event->set_season( '2026', force_overwrite: true );
		$this->assertSame( '2026', $event->current_season['name'] );
	}

	public function test_constructor_fallback_when_competition_season_not_in_event_seasons(): void {
		$competition = new Competition(
			'Summer League',
			Competition_Type::LEAGUE,
			'Open',
			[ '2024' => [ 'name' => '2024' ] ],
			null,
			1
		);

		// Event configured only for 2025 and 2026
		$event = Event::from_object(
			(object) [
				'id'      => 10,
				'name'    => 'Men Singles',
				'type'    => 'singles',
				'seasons' => json_encode( [
					'2025' => [ 'name' => '2025' ],
					'2026' => [ 'name' => '2026' ],
				] ),
			],
			$competition
		);

		// Event must fall back to its latest configured season (2026) without warning/false
		$this->assertIsArray( $event->current_season );
		$this->assertSame( '2026', $event->current_season['name'] );
	}
}
