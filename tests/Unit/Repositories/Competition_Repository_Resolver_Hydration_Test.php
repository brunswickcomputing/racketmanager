<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\DTO\Player\Player_Hydration_DTO;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Repositories\Competition_Repository;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

/**
 * Covers the club / player / event repository resolvers used by Competition_Repository row mapping.
 */
final class Competition_Repository_Resolver_Hydration_Test extends TestCase {
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$GLOBALS['wp_stubs_cache'] = [];
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	/**
	 * Install a wpdb double returning the given rows for get_results and the given scalar for get_var.
	 */
	private function install_wpdb( array $rows, mixed $var = 0 ): void {
		$GLOBALS['wpdb'] = new class( $rows, $var ) extends wpdb {
			public function __construct( private readonly array $rows, private readonly mixed $var ) {
				$this->prefix = 'wp_';
			}

			public function get_results( $query = null, $output = 'OBJECT' ): array {
				return $this->rows;
			}

			public function get_var( $query = null, $x = 0, $y = 0 ): mixed {
				return $this->var;
			}

			public function prepare( $query, ...$args ): string {
				return $query;
			}
		};
	}

	public function test_resolvers_are_not_invoked_at_construction(): void {
		$this->install_wpdb( [] );
		$calls = 0;
		$spy   = function () use ( &$calls ) {
			++$calls;
			return $this->createStub( Club_Repository_Interface::class );
		};

		$repository = new Competition_Repository( null, null, $spy, $spy, $spy );

		$this->assertInstanceOf( Competition_Repository::class, $repository );
		$this->assertSame( 0, $calls );
	}

	public function test_find_players_maps_rows_through_player_repository_resolver(): void {
		$this->install_wpdb( [ (object) [ 'id' => '7' ], (object) [ 'id' => 8 ] ] );

		$player_7 = new Player( new Player_Hydration_DTO( id: 7, display_name: 'Jane Doe' ) );
		$player_8 = new Player( new Player_Hydration_DTO( id: 8, display_name: 'John Smith' ) );

		$player_repository = $this->createMock( Player_Repository_Interface::class );
		$player_repository->expects( $this->exactly( 2 ) )
			->method( 'find' )
			->willReturnCallback( static fn( int|string $id ): ?Player => 7 === $id ? $player_7 : ( 8 === $id ? $player_8 : null ) );

		$resolver_calls = 0;
		$repository     = new Competition_Repository(
			null,
			null,
			null,
			function () use ( &$resolver_calls, $player_repository ): Player_Repository_Interface {
				++$resolver_calls;
				return $player_repository;
			}
		);

		$players = $repository->find_players( 5 );

		$this->assertSame( 1, $resolver_calls );
		$this->assertSame( [ $player_7, $player_8 ], $players );
	}

	public function test_find_players_does_not_call_repository_for_zero_or_empty_id(): void {
		$this->install_wpdb( [ (object) [ 'id' => 0 ], (object) [ 'id' => '' ], (object) [ 'id' => null ] ] );

		$player_repository = $this->createMock( Player_Repository_Interface::class );
		$player_repository->expects( $this->never() )->method( 'find' );

		$repository = new Competition_Repository( null, null, null, static fn() => $player_repository );

		$this->assertSame( [ null, null, null ], $repository->find_players( 5 ) );
	}

	public function test_find_players_with_count_returns_int_without_touching_repository(): void {
		$this->install_wpdb( [], '12' );

		$player_repository = $this->createMock( Player_Repository_Interface::class );
		$player_repository->expects( $this->never() )->method( 'find' );

		$repository = new Competition_Repository( null, null, null, static fn() => $player_repository );

		$this->assertSame( 12, $repository->find_players( 5, [ 'count' => true ] ) );
	}

	public function test_find_events_maps_rows_through_event_repository_resolver(): void {
		$this->install_wpdb( [ (object) [ 'id' => 10 ], (object) [ 'id' => 0 ] ] );

		$event = Event::from_object( (object) [ 'id' => 10, 'name' => 'Mens Singles', 'type' => 'singles', 'competition_id' => 5 ] );

		$event_repository = $this->createMock( Event_Repository_Interface::class );
		$event_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 10 )
			->willReturn( $event );

		$repository = new Competition_Repository( null, null, null, null, static fn() => $event_repository );

		$events = $repository->find_events( 5, [ 'season' => '2026' ] );

		$this->assertCount( 2, $events );
		$this->assertSame( $event, $events[0] );
		$this->assertNull( $events[1] );
	}

	public function test_find_teams_hydrates_club_through_club_repository_resolver(): void {
		$rows = [
			(object) [
				'league_title' => 'Division 1',
				'league_id'    => 3,
				'team_id'      => 21,
				'table_id'     => 100,
				'name'         => 'Leigh A',
				'rank'         => 1,
				'id'           => 3,
				'status'       => 'active',
				'profile'      => 0,
				'group'        => null,
				'roster'       => serialize( [ 7, 8 ] ),
				'club_id'      => 4,
				'team_type'    => 'club',
				'event_name'   => 'Mens Doubles',
			],
			(object) [
				'league_title' => 'Division 1',
				'league_id'    => 3,
				'team_id'      => 22,
				'table_id'     => 101,
				'name'         => 'Bye',
				'rank'         => 2,
				'id'           => 3,
				'status'       => 'active',
				'profile'      => 0,
				'group'        => null,
				'roster'       => null,
				'club_id'      => 0,
				'team_type'    => 'bye',
				'event_name'   => 'Mens Doubles',
			],
		];
		$this->install_wpdb( $rows, 3 );

		$club       = new Club();
		$club->name = 'Leigh';

		$club_repository = $this->createMock( Club_Repository_Interface::class );
		$club_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 4 )
			->willReturn( $club );

		$repository = new Competition_Repository( null, null, static fn() => $club_repository );

		$teams = $repository->find_teams( 5, [ 'season' => '2026' ] );

		$this->assertCount( 2, $teams );
		$this->assertSame( $club, $teams[0]->club );
		$this->assertSame( 'Leigh A', $teams[0]->title );
		$this->assertSame( [ 7, 8 ], $teams[0]->roster );
		$this->assertSame( 3, $teams[0]->player_count );
		$this->assertNull( $teams[1]->club );
		$this->assertSame( 'Bye', $teams[1]->title );
	}

	public function test_resolver_returning_wrong_type_falls_back_without_error(): void {
		$this->install_wpdb( [ (object) [ 'id' => 0 ] ] );

		$repository = new Competition_Repository( null, null, null, static fn() => new \stdClass() );

		// A zero id never reaches the repository, so the fallback is only ever resolved lazily.
		$this->assertSame( [ null ], $repository->find_players( 5 ) );
	}
}
