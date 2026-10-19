<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Fixture;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Domain\Team;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Relation_Loader;
use Racketmanager\Services\Team_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

final class Fixture_Container_Isolation_Test extends TestCase {
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		unset( $GLOBALS['racketmanager'] );
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_repository_hydrates_fixture_with_explicit_collaborators_without_container(): void {
		$league = $this->createStub( League::class );
		$league_repository = $this->createMock( League_Repository_Interface::class );
		$league_repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 7 )
			->willReturn( $league );

		$team = $this->createStub( Team::class );
		$team->method( 'get_name' )->willReturn( 'Home Team' );
		$team->method( 'get_team_type' )->willReturn( 'T' );
		$team_details = new Team_Details_DTO( $team );
		$team_service = $this->createMock( Team_Service::class );
		$team_service->expects( $this->exactly( 2 ) )
			->method( 'get_team_details' )
			->willReturn( $team_details );

		$wpdb = $this->createStub( wpdb::class );
		$wpdb->prefix = 'wp_';
		$wpdb->method( 'prepare' )->willReturnCallback( static fn( string $query ): string => $query );
		$wpdb->method( 'get_row' )->willReturn( (object) array(
			'id'         => 998123,
			'league_id'  => 7,
			'home_team'  => '11',
			'away_team'  => '22',
		) );
		$GLOBALS['wpdb'] = $wpdb;

		$repository = new Fixture_Repository(
			relation_loader: new Fixture_Relation_Loader( $league_repository, $team_service )
		);
		$fixture = $repository->find_by_id_with_relations( 998123 );

		$this->assertInstanceOf( Fixture::class, $fixture );
		$this->assertSame( $league, $fixture->get_league() );
		$this->assertSame( 'Home Team', $fixture->get_teams()['home']->title );
		$this->assertFalse( method_exists( $fixture, 'is_update_allowed' ) );
	}

	public function test_plain_find_by_id_is_cheap_and_does_not_load_relations(): void {
		$league_repository = $this->createMock( League_Repository_Interface::class );
		$league_repository->expects( $this->never() )->method( 'find_by_id' );
		$team_service = $this->createMock( Team_Service::class );
		$team_service->expects( $this->never() )->method( 'get_team_details' );

		$wpdb = $this->createStub( wpdb::class );
		$wpdb->prefix = 'wp_';
		$wpdb->method( 'prepare' )->willReturnCallback( static fn( string $query ): string => $query );
		$wpdb->method( 'get_row' )->willReturn(
			(object) array(
				'id'        => 998123,
				'league_id' => 7,
				'home_team' => '11',
				'away_team' => '22',
			)
		);
		$GLOBALS['wpdb'] = $wpdb;

		$repository = new Fixture_Repository(
			relation_loader: new Fixture_Relation_Loader( $league_repository, $team_service )
		);
		$fixture = $repository->find_by_id( 998123 );

		$this->assertInstanceOf( Fixture::class, $fixture );
		$this->assertNull( $fixture->get_league() );
		$this->assertSame( array(), $fixture->get_loaded_teams() );
	}

	public function test_fixture_domain_methods_are_safe_without_global_container_or_injected_services(): void {
		$fixture = Fixture_Factory::from_array(
			array(
				'league_id' => 7,
				'home_team' => '11',
				'away_team' => '22',
			)
		);

		$this->assertNull( $fixture->get_league() );
		$this->assertSame( 'Unknown', $fixture->get_teams()['home']->title );
		$this->assertFalse( method_exists( $fixture, 'is_update_allowed' ) );
		$this->assertSame( array(), $fixture->get_loaded_teams() );
	}

	public function test_bulk_team_lookup_for_deletion_does_not_load_relations(): void {
		$league_repository = $this->createMock( League_Repository_Interface::class );
		$league_repository->expects( $this->never() )->method( 'find_by_id' );
		$team_service = $this->createMock( Team_Service::class );
		$team_service->expects( $this->never() )->method( 'get_team_details' );

		$wpdb = $this->createStub( wpdb::class );
		$wpdb->prefix = 'wp_';
		$wpdb->method( 'prepare' )->willReturnCallback( static fn( string $query ): string => $query );
		$wpdb->method( 'get_results' )->willReturn(
			array( (object) array( 'id' => 5, 'league_id' => 7, 'home_team' => '11', 'away_team' => '22' ) )
		);

		$repository = new Fixture_Repository( $wpdb, new Fixture_Relation_Loader( $league_repository, $team_service ) );
		$fixtures   = $repository->find_by_team( 11 );

		$this->assertCount( 1, $fixtures );
		$this->assertSame( array(), $fixtures[0]->get_loaded_teams() );
		$this->assertNull( $fixtures[0]->get_league() );
	}
}
