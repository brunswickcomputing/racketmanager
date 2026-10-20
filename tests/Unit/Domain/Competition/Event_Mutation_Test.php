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
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Competition\Event_Hydration_DTO;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
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

	public function test_add_season_persists_constitution_details_without_reloading_league_team(): void {
		$competition = $this->createStub( Competition::class );
		$competition->is_league = true;

		$team                  = new stdClass();
		$team->old_league_id    = 12;
		$team->team_id          = 34;
		$team->captain          = 56;
		$team->match_day        = 'Tuesday';
		$team->match_time       = '18:30';

		$event = $this->getMockBuilder( Event::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'build_constitution' ] )
			->getMock();
		$event->id          = 42;
		$event->competition = $competition;
		$event->seasons     = [ '2025' => [ 'name' => '2025' ] ];
		$event->expects( $this->once() )
			->method( 'build_constitution' )
			->with( [ 'season' => '2025' ] )
			->willReturn( [ $team ] );

		$league = $this->createMock( League::class );
		$league->expects( $this->once() )
			->method( 'add_team' )
			->with( 34, '2026', 1, '', '0' )
			->willReturn( 78 );
		$GLOBALS['wp_stubs_leagues'][12] = $league;

		$season = [
			'name'           => '2026',
			'home_away'      => true,
			'num_match_days' => 10,
			'match_dates'    => [ '1' => '2026-06-02' ],
		];
		$this->event_repository->expects( $this->once() )
			->method( 'update_seasons' )
			->with( 42, [ '2025' => [ 'name' => '2025' ], '2026' => $season ] )
			->willReturn( true );

		$league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
		$league_team_repository->expects( $this->once() )
			->method( 'update_details' )
			->with( 78, 56, 'Tuesday', '18:30' )
			->willReturn( true );

		$event->add_season( $season, $league_team_repository );

		$this->assertSame( 78, $team->league_team_id );
	}

	public function test_promote_and_relegate_persists_constitution_without_reloading_league_team(): void {
		$competition = $this->createMock( Competition::class );
		$competition->expects( $this->once() )
			->method( 'get_season_by_name' )
			->with( '2026' )
			->willReturn(
				[
					'teams_prom_relg'  => 2,
					'teams_per_club'   => 2,
					'max_teams'        => 4,
					'lowest_promotion' => 1,
				]
			);

		$league = $this->createMock( League::class );
		$league->id               = 50;
		$league->num_teams_total  = 4;
		$league->expects( $this->once() )
			->method( 'get_league_teams' )
			->with( [ 'club' => 7, 'season' => 2026, 'count' => true, 'cache' => false ] )
			->willReturn( 1 );

		$event = $this->getMockBuilder( Event::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'get_leagues' ] )
			->getMock();
		$event->competition = $competition;
		$event->expects( $this->once() )
			->method( 'get_leagues' )
			->with( [ 'season' => 2026 ] )
			->willReturn( [ $league ] );

		$team = (object) [
			'team_id'  => 34,
			'league_id' => 50,
			'old_rank' => 4,
			'rank'     => 1,
			'table_id' => 78,
			'status'   => '',
		];
		$GLOBALS['wp_stubs_teams'][34] = (object) [ 'club_id' => 7 ];

		$league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
		$league_team_repository->expects( $this->once() )
			->method( 'update_constitution' )
			->with( 78, 50, 'BT' )
			->willReturn( true );

		$this->assertTrue( $event->promote_and_relegate( [ $team ], 2026, $league_team_repository ) );
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
