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
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class Event_Mutation_Test extends TestCase {
	use Manages_Wordpress_Globals;

	private Simple_Container $container;
	private $event_repository;
	private Event $event;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();

		$this->container        = new Simple_Container();
		$this->event_repository = $this->createMock( Event_Repository_Interface::class );

		$this->container->set( 'event_repository', $this->event_repository );

		$competition_service = $this->createMock( Competition_Service::class );
		$this->container->set( 'competition_service', $competition_service );

		$competition          = $this->createMock( Competition::class );
		$competition_settings = new Competition_Settings( [ 'mode' => 'league' ] );
		$competition->settings = $competition_settings;
		$competition->type = Competition_Type::LEAGUE;
		$competition->is_player_entry = false;
		$competition->standings = [];
		$competition_service->method( 'get_by_id' )->willReturn( $competition );

		$racketmanager = $this->getMockBuilder( RacketManager::class )
			->disableOriginalConstructor()
			->getMock();
		$racketmanager->container = $this->container;
		$GLOBALS['racketmanager'] = $racketmanager;

		$event_raw = new stdClass();
		$event_raw->id = 42;
		$event_raw->name = 'Men\'s Doubles';
		$event_raw->type = 'doubles';
		$event_raw->competition_id = 1;
		$event_raw->seasons = '[]';
		$event_raw->num_rubbers = 4;
		$event_raw->num_sets = 3;

		$this->event = new Event( $event_raw );
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_get_instance_delegates_to_event_repository_find_by_id(): void {
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

		$instance = Event::get_instance( 42, 'id' );
		$this->assertInstanceOf( Event::class, $instance );
		$this->assertSame( 42, $instance->id );
		$this->assertSame( 'Men\'s Doubles', $instance->name );
	}

	public function test_get_instance_delegates_to_event_repository_find_by_name(): void {
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

		$instance = Event::get_instance( 'Men\'s Doubles', 'name' );
		$this->assertInstanceOf( Event::class, $instance );
		$this->assertSame( 'Men\'s Doubles', $instance->name );
	}

	public function test_set_name_delegates_to_event_repository_update_name(): void {
		$this->event_repository->expects( $this->once() )
			->method( 'update_name' )
			->with( 42, 'Updated Event Name' )
			->willReturn( true );

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

		$this->event->delete();
	}

	public function test_set_num_leagues_delegates_to_event_repository_count_leagues(): void {
		$this->event_repository->expects( $this->once() )
			->method( 'count_leagues' )
			->with( 42 )
			->willReturn( 5 );

		$this->event->set_num_leagues( true );
		$this->assertSame( 5, $this->event->num_leagues );
	}

	public function test_mark_teams_withdrawn_delegates_to_event_repository(): void {
		$this->event_repository->expects( $this->once() )
			->method( 'mark_teams_withdrawn' )
			->with( 42, '2026', 10, 20 )
			->willReturn( true );

		$this->event->mark_teams_withdrawn( '2026', 10, 20 );
	}

	public function test_mark_teams_entered_delegates_to_event_repository(): void {
		$this->event_repository->expects( $this->once() )
			->method( 'mark_teams_entered' )
			->with( 42, 20, '2026' )
			->willReturn( true );

		$this->event->mark_teams_entered( 20, '2026' );
	}

	public function test_get_players_defaults_to_current_season(): void {
		$this->event->current_season = array( 'name' => '2026' );
		$this->event_repository->expects( $this->once() )
			->method( 'query_players' )
			->with(
				42,
				false,
				$this->callback(
					static function ( array $args ): bool {
						return '2026' === ( $args['season'] ?? null );
					}
				)
			)
			->willReturn( array() );

		$this->event->get_players();
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
}
