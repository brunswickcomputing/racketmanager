<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use Racketmanager\Tests\Helpers\League_Test_Factory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Factory;
use Racketmanager\Domain\DTO\Competition\League_Hydration_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionClass;
use stdClass;
use wpdb;

require_once dirname( __DIR__, 3 ) . '/wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class League_Purity_Test extends TestCase {
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		unset( $GLOBALS['racketmanager'], $GLOBALS['wp_stubs_cache'], $GLOBALS['wp_stubs_cache_enabled'], $GLOBALS['wp_stubs_cache_mode'] );
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_league_does_not_store_repository_or_service_collaborators(): void {
		$properties = ( new ReflectionClass( League::class ) )->getProperties();
		foreach ( $properties as $property ) {
			$name = $property->getName();
			$this->assertDoesNotMatchRegularExpression(
				'/(repository|service|container)/i',
				$name,
				"Property \$$name must not reference repository, service, or container."
			);

			$type = $property->getType();
			if ( $type instanceof \ReflectionNamedType ) {
				$typeName = $type->getName();
				$this->assertDoesNotMatchRegularExpression(
					'/(Repository|Service|Container)/i',
					$typeName,
					"Property \$$name type $typeName must not reference repository, service, or container."
				);
			} elseif ( $type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType ) {
				foreach ( $type->getTypes() as $subType ) {
					$typeName = $subType->getName();
					$this->assertDoesNotMatchRegularExpression(
						'/(Repository|Service|Container)/i',
						$typeName,
						"Property \$$name type $typeName must not reference repository, service, or container."
					);
				}
			}
		}
	}

	public function test_locator_and_repository_accessor_methods_are_removed(): void {
		$reflection = new ReflectionClass( League::class );
		foreach ( array(
			'get_instance',
			'set_league_repository',
			'set_fixture_repository',
			'set_rubber_repository',
			'get_fixture_repository',
			'get_league_repository',
			'get_crosstable_field',
			'get_players',
			'get_league_team',
			'get_team_dtls',
			'get_league_teams',
			'get_matches',
		) as $method ) {
			$this->assertFalse( $reflection->hasMethod( $method ), "League::$method must not exist (entity purity)." );
		}
		foreach ( $reflection->getMethods( \ReflectionMethod::IS_STATIC ) as $method ) {
			if ( $method->getDeclaringClass()->getName() === League::class ) {
				$this->assertNotSame( 'get_instance', $method->getName() );
			}
		}
		foreach ( $reflection->getMethods() as $method ) {
			if ( $method->getDeclaringClass()->getName() !== League::class ) {
				continue;
			}
			foreach ( $method->getParameters() as $param ) {
				$type = $param->getType();
				$type_names = array();
				if ( $type instanceof \ReflectionNamedType ) {
					$type_names[] = $type->getName();
				} elseif ( $type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType ) {
					foreach ( $type->getTypes() as $sub_type ) {
						$type_names[] = $sub_type->getName();
					}
				}
				foreach ( $type_names as $type_name ) {
					$this->assertDoesNotMatchRegularExpression(
						'/Repository/i',
						$type_name,
						"Method {$method->getName()} parameter {$param->getName()} must not typehint repository."
					);
				}
			}
		}
	}

	public function test_factory_hydrates_pure_league_with_supplied_event(): void {
		$event_raw                 = new stdClass();
		$event_raw->id             = 10;
		$event_raw->name           = 'Event 10';
		$event_raw->type           = 'singles';
		$event_raw->competition_id = 1;
		$event_raw->seasons        = '[]';
		$event                     = Event::from_object( $event_raw );

		$dto = new League_Hydration_DTO(
			id: 12,
			title: 'Division 1',
			event_id: 10
		);

		$league = League_Factory::create_from_dto( $dto, $event );

		$this->assertInstanceOf( League::class, $league );
		$this->assertSame( 12, $league->id );
		$this->assertSame( 'Division 1', $league->title );
		$this->assertSame( $event, $league->event );
	}

	public function test_repository_hydrates_league_using_event_repository(): void {
		$event_raw                 = new stdClass();
		$event_raw->id             = 10;
		$event_raw->name           = 'Event 10';
		$event_raw->type           = 'singles';
		$event_raw->competition_id = 1;
		$event_raw->seasons        = '[]';
		$event                     = Event::from_object( $event_raw );

		$event_repository = $this->createMock( Event_Repository_Interface::class );
		$event_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 10 )
			->willReturn( $event );

		$wpdb = $this->createStub( wpdb::class );
		$wpdb->prefix = 'wp_';
		$wpdb->method( 'prepare' )->willReturnCallback( static fn( string $query ): string => $query );
		$wpdb->method( 'get_row' )->willReturn(
			(object) array(
				'id'       => 12,
				'title'    => 'Division 1',
				'event_id' => 10,
				'settings' => array(),
				'seasons'  => array(),
			)
		);
		$GLOBALS['wpdb'] = $wpdb;

		$repository = new League_Repository( db: $wpdb, event_repository: $event_repository );
		$league     = $repository->find_by_id( 12 );

		$this->assertInstanceOf( League::class, $league );
		$this->assertSame( 12, $league->id );
		$this->assertSame( $event, $league->event );
	}




	public function test_construct_championship_league_without_warnings(): void {
		$event_raw                 = new stdClass();
		$event_raw->id             = 10;
		$event_raw->name           = 'Cup Competition';
		$event_raw->type           = 'singles';
		$event_raw->competition_id = 1;
		$event_raw->seasons        = '[]';

		$competition = \Racketmanager\Domain\Competition\Competition_Factory::from_object( (object) array(
			'id'       => 1,
			'name'     => 'Cup Comp',
			'settings' => array( 'mode' => 'championship' ),
		) );

		$event = Event::from_object( $event_raw, $competition );

		$league_row = (object) array(
			'id'       => 36,
			'title'    => 'Men Singles Cup',
			'event_id' => 10,
			'settings' => array( 'mode' => 'championship' ),
			'seasons'  => array(),
		);

		$league = League::from_object( $league_row, $event );

		$this->assertTrue( $league->is_championship );
		$this->assertSame( 36, $league->id );
	}

	public function test_repository_hydrates_championship_league_with_correct_team_counts_and_rounds(): void {
		$event_raw                 = new stdClass();
		$event_raw->id             = 10;
		$event_raw->name           = 'Cup Competition';
		$event_raw->type           = 'singles';
		$event_raw->competition_id = 1;
		$event_raw->seasons        = wp_json_encode( array( '2026' => array( 'name' => '2026', 'num_match_days' => 4 ) ) );

		$competition = \Racketmanager\Domain\Competition\Competition_Factory::from_object( (object) array(
			'id'       => 1,
			'name'     => 'Cup Comp',
			'settings' => array( 'mode' => 'championship' ),
		) );

		$event = Event::from_object( $event_raw, $competition );

		$event_repository = $this->createMock( Event_Repository_Interface::class );
		$event_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 10 )
			->willReturn( $event );

		$recording_db = new \Racketmanager\Tests\Helpers\Recording_Wpdb();
		$recording_db->get_row_return = (object) array(
			'id'       => 36,
			'title'    => 'Cup 36',
			'event_id' => 10,
			'settings' => array( 'mode' => 'championship' ),
			'seasons'  => array( '2026' => array( 'name' => '2026', 'num_match_days' => 4 ) ),
		);
		// count query returns 9 teams
		$recording_db->get_var_return = '9';

		$repository = new League_Repository( db: $recording_db, event_repository: $event_repository );
		$league     = $repository->find_by_id( 36 );

		$this->assertNotNull( $league );
		$this->assertSame( 9, $league->num_teams_total );
		$this->assertTrue( $league->is_championship );
		$this->assertNotNull( $league->championship );
		$this->assertSame( 4, $league->championship->num_rounds );
	}

	public function test_add_team_domain_method_has_no_repository_parameter_and_rejects_persistence(): void {
		$league = League_Test_Factory::from_object( (object) array( 'id' => 10 ) );

		$this->assertSame( 5, ( new \ReflectionMethod( League::class, 'add_team' ) )->getNumberOfParameters() );
		$this->expectException( \LogicException::class );
		$this->expectExceptionMessage( 'League_Query_Service::add_team()' );
		$league->add_team( 5, '2026' );
	}

	public function test_get_rank_domain_method_rejects_reading_from_repository(): void {
		$league = League_Test_Factory::from_object( (object) array( 'id' => 10 ) );
		$this->assertSame( 2, ( new \ReflectionMethod( League::class, 'get_rank' ) )->getNumberOfParameters() );
		$this->expectException( \LogicException::class );
		$this->expectExceptionMessage( 'League_Query_Service::get_rank()' );
		$league->get_rank( 5, '2026' );
	}

	public function test_get_status_domain_method_rejects_reading_from_repository(): void {
		$league = League_Test_Factory::from_object( (object) array( 'id' => 10 ) );
		$this->assertSame( 2, ( new \ReflectionMethod( League::class, 'get_status' ) )->getNumberOfParameters() );
		$this->expectException( \LogicException::class );
		$this->expectExceptionMessage( 'League_Query_Service::get_status()' );
		$league->get_status( 5, '2026' );
	}

	public function test_get_num_teams_domain_method_rejects_reading_from_repository(): void {
		$league = League_Test_Factory::from_object( (object) array( 'id' => 10 ) );
		$this->assertSame( 2, ( new \ReflectionMethod( League::class, 'get_num_teams' ) )->getNumberOfParameters() );
		$this->expectException( \LogicException::class );
		$this->expectExceptionMessage( 'League_Query_Service::get_num_teams()' );
		$league->get_num_teams();
	}

	public function test_set_num_teams_is_a_pure_in_memory_mutator_without_repository_parameter(): void {
		$league = League_Test_Factory::from_object( (object) array( 'id' => 10 ) );
		$league->num_teams_total = 8;
		$this->assertSame( 1, ( new \ReflectionMethod( League::class, 'set_num_teams' ) )->getNumberOfParameters() );

		$league->set_num_teams( false );
		$this->assertSame( 8, $league->num_teams );
	}

	public function test_set_num_matches_is_removed_from_entity(): void {
		$this->assertFalse( ( new \ReflectionClass( League::class ) )->hasMethod( 'set_num_matches' ) );
	}

	public function test_update_is_a_pure_in_memory_mutator_without_repository_parameter(): void {
		$league = League_Test_Factory::from_object( (object) array( 'id' => 10, 'title' => 'Original' ) );
		$this->assertSame( 2, ( new \ReflectionMethod( League::class, 'update' ) )->getNumberOfParameters() );

		$league->update( 'Changed', '02' );
		$this->assertSame( 'Changed', $league->title );
		$this->assertSame( 'Changed', $league->name );
		$this->assertSame( '02', $league->sequence );
	}

	public function test_set_season_mutates_state_in_memory_without_repository(): void {
		$league = League_Test_Factory::from_object(
			(object) array(
				'id'      => 10,
				'seasons' => array(
					'2025' => array( 'name' => '2025', 'num_match_days' => 6 ),
					'2026' => array( 'name' => '2026', 'num_match_days' => 8 ),
				),
			)
		);
		$this->assertSame( 2, ( new \ReflectionMethod( League::class, 'set_season' ) )->getNumberOfParameters() );

		$league->set_season( '2025' );
		$this->assertSame( '2025', $league->get_season() );
		$this->assertSame( 6, $league->num_match_days );
		$this->assertSame( '2025', $league->get_team_query_args()['season'] );
		$this->assertSame( '2025', $league->get_match_query_args()['season'] );
	}

	public function test_set_match_day_is_a_pure_in_memory_mutator(): void {
		$league = League_Test_Factory::from_object( (object) array( 'id' => 10 ) );
		$this->assertSame( 1, ( new \ReflectionMethod( League::class, 'set_match_day' ) )->getNumberOfParameters() );

		$league->set_match_day( 4 );
		$this->assertSame( 4, $league->match_day );
		$this->assertSame( 4, $league->get_match_query_args()['match_day'] );
	}

	public function test_set_rounds_mutates_seasons_in_memory_without_repository(): void {
		$league = League_Test_Factory::from_object( (object) array( 'id' => 10, 'seasons' => array() ) );
		$this->assertSame( 2, ( new \ReflectionMethod( League::class, 'set_rounds' ) )->getNumberOfParameters() );

		$league->set_rounds( '2026', array( 'Final' ) );

		$this->assertSame( array( 'rounds' => array( 'Final' ) ), $league->seasons['2026'] );
	}

	public function test_constructor_only_accepts_an_event_as_second_argument(): void {
		$this->expectException( \TypeError::class );
		$this->assertInstanceOf( League::class, League::from_object( (object) array( 'id' => 10 ), $this->createStub( League_Repository_Interface::class ) ) );
	}

	public function test_get_league_teams_rolls_back_team_query_args_on_exception(): void {
		$league = League_Test_Factory::from_object( (object) array( 'id' => 10 ) );
		$ref_prop = new \ReflectionProperty( League::class, 'team_query_args' );
		$initial_args = $ref_prop->getValue( $league );

		$repository = $this->createStub( League_Repository_Interface::class );
		$repository->method( 'query_league_teams' )->willReturn( array( (object) array( 'table_id' => 50 ) ) );
		$service = new League_Query_Service( $repository );

		try {
			$service->get_league_teams( $league, array( 'club' => 7, 'get_details' => true ) );
			$this->fail( 'Expected missing League_Team_Repository to throw.' );
		} catch ( \InvalidArgumentException $e ) {
			$this->assertSame( 'League_Team_Repository_Interface collaborator is required to hydrate league teams.', $e->getMessage() );
			$this->assertSame( $initial_args, $ref_prop->getValue( $league ) );
		}
	}

	public function test_get_matches_rolls_back_match_query_args_on_exception(): void {
		$league = League_Test_Factory::from_object( (object) array( 'id' => 10 ) );
		$ref_prop = new \ReflectionProperty( League::class, 'match_query_args' );
		$initial_args = $ref_prop->getValue( $league );

		$repository = $this->createStub( League_Repository_Interface::class );
		$repository->method( 'query_matches' )->willThrowException( new \RuntimeException( 'DB error' ) );
		$service = new League_Query_Service( $repository, $this->createStub( Fixture_Repository_Interface::class ) );

		try {
			$service->get_matches( $league, array( 'team' => 42, 'season' => 'any' ) );
			$this->fail( 'Expected DB error exception.' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'DB error', $e->getMessage() );
			$this->assertSame( $initial_args, $ref_prop->getValue( $league ) );
		}
	}

	public function test_helper_fallback_league_repository_hydrates_league_using_event_repository(): void {
		unset( $GLOBALS['racketmanager'] );
		$repo = \Racketmanager\get_league_repository_for_helpers();
		$this->assertInstanceOf( League_Repository_Interface::class, $repo );

		$db = new \Racketmanager\Tests\Helpers\Recording_Wpdb();
		$db->prefix = 'wp_';
		$db->get_row_return = (object) array(
			'id' => 10,
			'title' => 'Division 1',
			'event_id' => 20,
			'seasons' => serialize( array( '2026' => array( 'name' => '2026' ) ) ),
			'settings' => serialize( array() ),
		);

		$event_repo = $this->createMock( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class );
		$event_repo->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 20 )
			->willReturn( Event::from_object( (object) array( 'id' => 20, 'name' => 'Test Event', 'type' => 'singles', 'competition_id' => 1 ) ) );

		$fallback_repo = new League_Repository( db: $db, event_repository: $event_repo );
		$league = $fallback_repo->find_by_id( 10 );
		$this->assertNotNull( $league );
		$this->assertSame( 10, $league->get_id() );
		$this->assertSame( 'Division 1', $league->get_name() );
		$this->assertNotNull( $league->event );
		$this->assertSame( 20, $league->event->get_id() );
	}

	public function test_sport_tennis_registers_import_filter_hooks(): void {
		require_once dirname( __DIR__, 4 ) . '/src/php/Sports/tennis.php';

		$matches_res = apply_filters( 'racketmanager_import_matches_tennis', array( 'custom_data' ), array(), 1, 0 );
		$this->assertSame( array( 'custom_data' ), $matches_res );

		$teams_res = apply_filters( 'racketmanager_import_teams_tennis', array( 'custom_data' ), array(), 0 );
		$this->assertSame( array( 'custom_data' ), $teams_res );
	}
}
