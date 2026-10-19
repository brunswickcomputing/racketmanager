<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Player_Factory;
use Racketmanager\Repositories\Competition_Repository;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Fixture\Fixture_Relation_Loader;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

final class Competition_Repository_Player_Query_Test extends TestCase {
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_competition_matches_are_league_enriched_before_player_grouping_and_null_rows_are_filtered(): void {
		$rows = array( (object) array( 'id' => 42 ), (object) array( 'id' => 99 ) );
		$wpdb = new class( $rows ) extends wpdb {
			private array $rows;

			public function __construct( array $rows ) {
				$this->prefix = 'wp_';
				$this->rows  = $rows;
			}

			public function get_results( $query = null, $output = 'OBJECT' ): array {
				return $this->rows;
			}

			public function prepare( $query, ...$args ): string {
				return $query;
			}
		};
		$GLOBALS['wpdb'] = $wpdb;

		$fixture = new Fixture();
		$fixture->set_id( 42 );
		$fixture->set_league_id( 7 );
		$fixture->set_season( '2026' );

		$league = $this->createStub( League::class );
		$league->id    = 7;
		$league->title = 'Division 7';

		$fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
		$fixture_repository->expects( $this->exactly( 2 ) )
			->method( 'find_by_id' )
			->willReturnCallback( static fn( $id ): ?Fixture => 42 === (int) $id ? $fixture : null );

		$league_repository = $this->createMock( League_Repository_Interface::class );
		$league_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 7 )
			->willReturn( $league );

		$container = new Simple_Container();
		$container->set( 'fixture_repository', $fixture_repository );
		$container->set( 'fixture_relation_loader', new Fixture_Relation_Loader( $league_repository ) );
		$GLOBALS['racketmanager'] = (object) array( 'container' => $container );

		$competition_repository = new Competition_Repository();
		$competition = new class( $competition_repository ) extends Competition {
			public function __construct( private readonly Competition_Repository $repository ) {
				parent::__construct( 'Test Competition', Competition_Type::LEAGUE, 'open', id: 5 );
			}

			public function get_matches( array $match_args ): array {
				return $this->repository->find_matches( 5, $match_args );
			}
		};

		$player  = Player_Factory::from_array( array( 'ID' => '7', 'display_name' => 'Test Player' ) );
		$service = new Player_Query_Service( $wpdb, $competition_repository );
		$matches = $service->get_matches( $player, $competition, '2026', 'competition' );

		$this->assertSame( $league, $fixture->get_league() );
		$this->assertSame( array( $fixture ), $matches['Division 7']['matches'] );
		$this->assertSame( $league, $player->matches['Division 7']['league'] );
	}
}
