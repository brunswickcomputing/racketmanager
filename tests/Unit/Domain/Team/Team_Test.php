<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Team;

use Error;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\DTO\Team\Team_Hydration_DTO;
use Racketmanager\Domain\Enums\Team_Profile;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Tests\Helpers\Player_Test_Factory;
use Racketmanager\Domain\Team\Team_Factory;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Team_Service;
use TypeError;
use wpdb;

require_once dirname( __DIR__, 3 ) . '/wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Team_Test extends TestCase {
    private ?wpdb $original_wpdb = null;

    protected function setUp(): void {
        parent::setUp();
        $this->original_wpdb = $GLOBALS['wpdb'] ?? null;
    }

    protected function tearDown(): void {
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring original wpdb after test.
        $GLOBALS['wpdb'] = $this->original_wpdb;
        $GLOBALS['wp_stubs_cache_enabled'] = false;
        $GLOBALS['wp_stubs_cache_deleted'] = [];
        $GLOBALS['wp_stubs_cache'] = [];
        parent::tearDown();
    }

    public function test_hydration_dto_type_conversions_and_fallbacks(): void {
        $data = (object) [
            'id'          => '42',
            'club_id'     => '100',
            'name'        => 'Westcliff Men 1',
            'player_1_id' => '10',
            'player_2_id' => '20',
            'player_ids'  => 'not-an-array',
            'players'     => 'not-an-array',
            'matches'     => 'not-an-array',
            'roster'      => 'custom_roster_data',
        ];

        $dto = Team_Hydration_DTO::from_object( $data );
        $this->assertSame( 42, $dto->id );
        $this->assertSame( 100, $dto->club_id );
        $this->assertSame( 'Westcliff Men 1', $dto->title );
        $this->assertSame( 10, $dto->player_1_id );
        $this->assertSame( 20, $dto->player_2_id );
        $this->assertSame( [], $dto->player_ids );
        $this->assertSame( [], $dto->players );
        $this->assertSame( [], $dto->matches );
        $this->assertFalse( property_exists( $dto, 'roster' ) );

        $array_dto = Team_Hydration_DTO::from_array(
            [
                'id'     => '99',
                'title'  => 'Array Team',
                'roster' => [ 'legacy' => true ],
            ]
        );
        $this->assertSame( 99, $array_dto->id );
        $this->assertSame( 'Array Team', $array_dto->title );
        $this->assertFalse( property_exists( $array_dto, 'roster' ) );
    }

    public function test_hydration_ignores_legacy_roster_payload_without_defining_roster_property(): void {
        $legacy_obj = (object) [
            'id'     => 42,
            'title'  => 'Legacy Object Team',
            'roster' => [ 'player_id' => 10, 'captain' => 'John' ],
        ];
        $team_from_obj = Team_Factory::from_object( $legacy_obj );
        $this->assertSame( 42, $team_from_obj->get_id() );
        $this->assertSame( 'Legacy Object Team', $team_from_obj->get_title() );
        $this->assertFalse( property_exists( $team_from_obj, 'roster' ) );

        $legacy_arr = [
            'id'     => 43,
            'title'  => 'Legacy Array Team',
            'roster' => 'serialized_roster_blob',
        ];
        $team_from_arr = Team_Factory::from_array( $legacy_arr );
        $this->assertSame( 43, $team_from_arr->get_id() );
        $this->assertSame( 'Legacy Array Team', $team_from_arr->get_title() );
        $this->assertFalse( property_exists( $team_from_arr, 'roster' ) );
    }

    public function test_factory_creation(): void {
        $dto = new Team_Hydration_DTO( id: 1, title: 'Factory Team' );
        $team_from_dto = Team_Factory::create_from_dto( $dto );
        $this->assertSame( 1, $team_from_dto->get_id() );
        $this->assertSame( 'Factory Team', $team_from_dto->get_name() );

        $team_from_obj = Team_Factory::from_object( (object) [ 'id' => 2, 'title' => 'Obj Team' ] );
        $this->assertSame( 2, $team_from_obj->get_id() );
        $this->assertSame( 'Obj Team', $team_from_obj->get_name() );

        $team_from_arr = Team_Factory::from_array( [ 'id' => 3, 'title' => 'Arr Team' ] );
        $this->assertSame( 3, $team_from_arr->get_id() );
        $this->assertSame( 'Arr Team', $team_from_arr->get_name() );

        $data = [ 'id' => 42, 'title' => 'Westcliff Men 1', 'club_id' => 10 ];
        $this->assertEquals( Team_Factory::from_object( (object) $data ), Team::from_object( (object) $data ) );
        $this->assertEquals( Team_Factory::from_array( $data ), Team::from_array( $data ) );
        $this->assertEquals( Team::from_object( (object) $data ), Team::from_array( $data ) );
    }

    public function test_team_entity_title_expansion_and_has_player(): void {
        $team = Team::from_object( (object) [ 'id' => 5, 'title' => '1_semi-final_1' ] );
        $this->assertSame( '1_semi-final_1', $team->team_ref );
        $this->assertSame( 'Winner of', $team->title );

        $loser_team = Team::from_object( (object) [ 'id' => 6, 'title' => '2_final_1' ] );
        $this->assertSame( '2_final_1', $loser_team->team_ref );
        $this->assertSame( 'Loser of', $loser_team->title );

        $plain_team = Team::from_object( (object) [ 'id' => 7, 'title' => 'Leigh 1' ] );
        $this->assertNull( $plain_team->team_ref );
        $this->assertSame( 'Leigh 1', $plain_team->title );

        // Bye team
        $bye = Team::from_object( (object) [ 'id' => -1, 'title' => 'Bye' ] );
        $this->assertFalse( $bye->has_player( 10 ) );

        // Loaded players array
        $p1 = Player_Test_Factory::create( id: 10 );
        $team->set_players( [ $p1 ] );
        $this->assertTrue( $team->has_player( 10 ) );
        $this->assertFalse( $team->has_player( 11 ) );

        // Fallback to player_ids array
        $team_ids = new Team();
        $team_ids->set_player_ids( [ 1 => 20, 2 => 30 ] );
        $this->assertTrue( $team_ids->has_player( 20 ) );
        $this->assertTrue( $team_ids->has_player( 30 ) );
        $this->assertFalse( $team_ids->has_player( 40 ) );
    }

    /**
     * Test has_player always returns false for Bye teams (-1 === id) even with populated roster.
     */
    public function test_has_player_returns_false_for_bye_team_with_players(): void {
        $bye_team = Team::from_object( (object) [ 'id' => -1, 'title' => 'Bye' ] );
        $player   = Player_Test_Factory::create( id: 10 );
        $bye_team->set_players( [ $player ] );
        $bye_team->set_player_ids( [ 1 => 10, 2 => 20 ] );

        $this->assertFalse( $bye_team->has_player( 10 ) );
        $this->assertFalse( $bye_team->has_player( 20 ) );
    }

    /**
     * Test has_player with objects having property id instead of get_id() method.
     */
    public function test_has_player_with_object_property_id(): void {
        $team = new Team();
        $team->set_id( 5 );

        // Object with integer id property.
        $obj_player = (object) [ 'id' => 15 ];
        $team->set_players( [ $obj_player ] );

        $this->assertTrue( $team->has_player( 15 ) );
        $this->assertFalse( $team->has_player( 16 ) );

        // Object with numeric string id property.
        $str_obj_player = (object) [ 'id' => '25' ];
        $team->set_players( [ $str_obj_player ] );

        $this->assertTrue( $team->has_player( 25 ) );
        $this->assertFalse( $team->has_player( 26 ) );
    }

    /**
     * Test has_player handles objects missing id property or non-player elements gracefully.
     */
    public function test_has_player_handles_missing_id_gracefully(): void {
        $team = new Team();
        $team->set_id( 5 );

        $invalid_obj = (object) [ 'name' => 'Unknown' ];
        $team->set_players( [ $invalid_obj, null ] );

        $this->assertFalse( $team->has_player( 10 ) );
    }

    /**
     * Test has_player with player_ids array containing string integers and empty rosters.
     */
    public function test_has_player_with_player_ids_fallback_and_empty_state(): void {
        $team = new Team();
        $team->set_id( 5 );

        // Empty state.
        $this->assertFalse( $team->has_player( 10 ) );

        // String integers in player_ids.
        $team->set_player_ids( [ '1' => '50', '2' => '60' ] );
        $this->assertTrue( $team->has_player( 50 ) );
        $this->assertTrue( $team->has_player( 60 ) );
        $this->assertFalse( $team->has_player( 70 ) );
    }

    public function test_team_repository_find_by_id_and_cache_invalidation(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache_deleted'] = [];
        $GLOBALS['wp_stubs_cache'] = [];

        $wpdb = $this->createMock( wpdb::class );
        $wpdb->prefix = 'wp_';
        $player_repo = $this->createMock( Player_Repository_Interface::class );

        $p_player = Player_Test_Factory::create( id: 77 );
        $player_repo->expects( $this->once() )->method( 'find' )->with( 77 )->willReturn( $p_player );

        $wpdb->method( 'prepare' )->willReturnCallback( fn() => 'PREPARED_QUERY' );
        $wpdb->expects( $this->once() )->method( 'get_row' )->willReturn(
            (object) [
                'id'        => 50,
                'title'     => 'Player Team',
                'team_type' => 'P',
                'stadium'   => null,
                'home'      => null,
                'profile'   => null,
                'status'    => null,
                'club_id'   => null,
                'type'      => null,
            ]
        );
        $wpdb->expects( $this->once() )->method( 'get_results' )->willReturn(
            [
                (object) [ 'player_id' => 77 ],
            ]
        );

        $repo = new Team_Repository( $wpdb, $player_repo );

        $team = $repo->find_by_id( 50 );
        $this->assertNotNull( $team );
        $this->assertCount( 1, $team->get_players() );
        $this->assertSame( 77, $team->get_players()[0]->id );
        $this->assertSame( [ 1 => 77 ], $team->player_ids );

        // Second call should hit cache without querying $wpdb again
        $cached_team = $repo->find_by_id( 50 );
        $this->assertEquals( $team, $cached_team );

        // Test save_team_players invalidates cache
        $wpdb->method( 'insert' )->willReturn( 1 );
        $repo->save_team_players( 50, [ 77, 88 ] );
        $this->assertContains( [ 50, 'teams' ], $GLOBALS['wp_stubs_cache_deleted'] );
        $this->assertArrayNotHasKey( '50', $GLOBALS['wp_stubs_cache']['teams'] ?? [] );
    }

    public function test_team_repository_update_event_settings(): void {
        $wpdb = $this->createMock( wpdb::class );
        $wpdb->prefix = 'wp_';
        $wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->with(
                $this->stringContains( 'UPDATE `wp_racketmanager_league_teams`' ),
                2,
                'Tuesday',
                '19:30',
                10,
                '2026',
                5
            )
            ->willReturn( 'UPDATE_SQL' );
        $wpdb->expects( $this->once() )
            ->method( 'query' )
            ->with( 'UPDATE_SQL' )
            ->willReturn( 1 );

        $repo = new Team_Repository( $wpdb );
        $result = $repo->update_event_settings( 10, 5, '2026', '2', 'Tuesday', '19:30' );
        $this->assertTrue( $result );
    }

    public function test_team_repository_delete_success_and_failures(): void {
        // Success case
        $wpdb_success = $this->createMock( wpdb::class );
        $wpdb_success->prefix = 'wp_';
        $wpdb_success->expects( $this->exactly( 3 ) )
            ->method( 'delete' )
            ->willReturn( 1 );

        $repo_success = new Team_Repository( $wpdb_success );
        $this->assertTrue( $repo_success->delete( 10 ) );
        $this->assertContains( [ 10, 'teams' ], $GLOBALS['wp_stubs_cache_deleted'] );

        // Failure on team_players
        $wpdb_fail_players = $this->createMock( wpdb::class );
        $wpdb_fail_players->prefix = 'wp_';
        $wpdb_fail_players->expects( $this->once() )
            ->method( 'delete' )
            ->willReturn( false );
        $repo_fail_players = new Team_Repository( $wpdb_fail_players );
        $this->assertFalse( $repo_fail_players->delete( 10 ) );

        // Failure on league_teams
        $wpdb_fail_league = $this->createMock( wpdb::class );
        $wpdb_fail_league->prefix = 'wp_';
        $wpdb_fail_league->expects( $this->exactly( 2 ) )
            ->method( 'delete' )
            ->willReturnOnConsecutiveCalls( 1, false );
        $repo_fail_league = new Team_Repository( $wpdb_fail_league );
        $this->assertFalse( $repo_fail_league->delete( 10 ) );

        // Failure on teams table
        $wpdb_fail_team = $this->createMock( wpdb::class );
        $wpdb_fail_team->prefix = 'wp_';
        $wpdb_fail_team->expects( $this->exactly( 3 ) )
            ->method( 'delete' )
            ->willReturnOnConsecutiveCalls( 1, 1, false );
        $repo_fail_team = new Team_Repository( $wpdb_fail_team );
        $this->assertFalse( $repo_fail_team->delete( 10 ) );
    }

    public function test_fixture_repository_find_by_team_and_delete_by_team(): void {
        $wpdb = $this->createMock( wpdb::class );
        $wpdb->prefix = 'wp_';
        $wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->with(
                $this->logicalAnd(
                    $this->stringContains( 'FROM wp_racketmanager_matches' ),
                    $this->stringContains( '`home_team` = %s OR `away_team` = %s' )
                ),
                '50',
                '50'
            )
            ->willReturn( 'PREPARED_FIND' );

        $wpdb->expects( $this->once() )
            ->method( 'get_results' )
            ->with( 'PREPARED_FIND' )
            ->willReturn(
                [
                    (object) [ 'id' => 101, 'home_team' => '50', 'away_team' => '60' ],
                    (object) [ 'id' => 102, 'home_team' => '70', 'away_team' => '50' ],
                ]
            );

        $fixture_repo = new Fixture_Repository( $wpdb );

        $wpdb->expects( $this->exactly( 4 ) )
            ->method( 'delete' )
            ->willReturn( 1 ); // 2 rubbers deleted + 2 fixtures deleted

        $deleted = $fixture_repo->delete_by_team( 50 );
        $this->assertTrue( $deleted );
    }

    public function test_team_service_delete_team_delegates_to_repository_composite(): void {
        $team_repo    = $this->createMock( Team_Repository::class );
        $club_repo    = $this->createMock( Club_Repository::class );
        $event_repo   = $this->createMock( Event_Repository::class );
        $player_serv  = $this->createMock( Player_Service::class );
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );

        $team_repo->expects( $this->once() )
            ->method( 'delete_with_fixtures' )
            ->with( 100, $this->identicalTo( $fixture_repo ) )
            ->willReturn( true );
        $team_repo->expects( $this->never() )->method( 'delete' );
        $fixture_repo->expects( $this->never() )->method( 'delete_by_team' );

        $service = new Team_Service( $team_repo, $club_repo, $event_repo, $player_serv, $fixture_repo, new Event_Query_Service( $event_repo, $fixture_repo ) );
        $result  = $service->delete_team( 100 );
        $this->assertTrue( $result );
    }

    public function test_team_service_delete_team_casts_string_id_and_returns_repository_failure(): void {
        $team_repo    = $this->createMock( Team_Repository::class );
        $club_repo    = $this->createMock( Club_Repository::class );
        $event_repo   = $this->createMock( Event_Repository::class );
        $player_serv  = $this->createMock( Player_Service::class );
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );

        $team_repo->expects( $this->once() )
            ->method( 'delete_with_fixtures' )
            ->with( $this->identicalTo( 100 ), $this->identicalTo( $fixture_repo ) )
            ->willReturn( false );

        $service = new Team_Service( $team_repo, $club_repo, $event_repo, $player_serv, $fixture_repo, new Event_Query_Service( $event_repo, $fixture_repo ) );
        $result  = $service->delete_team( '100' );
        $this->assertFalse( $result );
    }

    public function test_team_service_delete_team_returns_false_for_empty_id_without_calling_repository(): void {
        $team_repo    = $this->createMock( Team_Repository::class );
        $club_repo    = $this->createMock( Club_Repository::class );
        $event_repo   = $this->createMock( Event_Repository::class );
        $player_serv  = $this->createMock( Player_Service::class );
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );

        $team_repo->expects( $this->never() )->method( 'delete_with_fixtures' );

        $service = new Team_Service( $team_repo, $club_repo, $event_repo, $player_serv, $fixture_repo, new Event_Query_Service( $event_repo, $fixture_repo ) );
        $this->assertFalse( $service->delete_team( null ) );
        $this->assertFalse( $service->delete_team( 0 ) );
        $this->assertFalse( $service->delete_team( '' ) );
    }

    public function test_team_service_update_team_event_success(): void {
        $team_repo    = $this->createMock( Team_Repository::class );
        $club_repo    = $this->createMock( Club_Repository::class );
        $event_repo   = $this->createMock( Event_Repository::class );
        $player_serv  = $this->createMock( Player_Service::class );
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );

        $event = $this->getMockBuilder( Event::class )->disableOriginalConstructor()->onlyMethods( array( 'get_season' ) )->getMock();
        $event->set_competition( (object) [ 'is_team_entry' => true ] );
        $event->method( 'get_season' )->willReturn( '2026' );
        $query = $this->createMock( Event_Query_Service::class );
        $query->expects( $this->once() )->method( 'get_team_info' )->with( $event, 10 )->willReturn(
            (object) [
                'captain_id'   => 1,
                'match_day'    => 'Monday',
                'match_time'   => '18:00',
                'contactno'    => '111',
                'contactemail' => 'old@example.com',
            ]
        );

        $event_repo->expects( $this->once() )->method( 'find_by_id' )->with( 5 )->willReturn( $event );

        $team_repo->expects( $this->once() )
            ->method( 'update_event_settings' )
            ->with( 10, 5, '2026', '2', 'Wednesday', '19:30' )
            ->willReturn( true );

        $p2 = Player_Test_Factory::create( id: 2 );
        $player_serv->expects( $this->once() )->method( 'get_player' )->with( 2 )->willReturn( $p2 );
        $player_serv->expects( $this->once() )
            ->method( 'update_contact_details' )
            ->with( 2, '222', 'new@example.com' )
            ->willReturn( true );

        $service = new Team_Service( $team_repo, $club_repo, $event_repo, $player_serv, $fixture_repo, $query );
        $msg     = $service->update_team_event( 10, 5, 2, '222', 'new@example.com', 2, '19:30' );
        $this->assertSame( 'Team updated', $msg );
    }

    public function test_team_service_update_team_event_no_updates(): void {
        $team_repo    = $this->createMock( Team_Repository::class );
        $club_repo    = $this->createMock( Club_Repository::class );
        $event_repo   = $this->createMock( Event_Repository::class );
        $player_serv  = $this->createMock( Player_Service::class );
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );

        $event = $this->getMockBuilder( Event::class )->disableOriginalConstructor()->onlyMethods( array( 'get_season' ) )->getMock();
        $event->set_competition( (object) [ 'is_team_entry' => true ] );
        $event->method( 'get_season' )->willReturn( '2026' );
        $query = $this->createMock( Event_Query_Service::class );
        $query->expects( $this->once() )->method( 'get_team_info' )->with( $event, 10 )->willReturn(
            (object) [
                'captain_id'   => 2,
                'match_day'    => 'Wednesday',
                'match_time'   => '19:30',
                'contactno'    => '222',
                'contactemail' => 'new@example.com',
            ]
        );

        $event_repo->expects( $this->once() )->method( 'find_by_id' )->with( 5 )->willReturn( $event );
        $team_repo->expects( $this->never() )->method( 'update_event_settings' );
        $player_serv->expects( $this->never() )->method( 'update_contact_details' );

        $service = new Team_Service( $team_repo, $club_repo, $event_repo, $player_serv, $fixture_repo, $query );
        $msg     = $service->update_team_event( 10, 5, 2, '222', 'new@example.com', 2, '19:30' );
        $this->assertSame( 'No updates', $msg );
    }

    public function test_team_service_update_team_event_missing_details(): void {
        $team_repo    = $this->createMock( Team_Repository::class );
        $club_repo    = $this->createMock( Club_Repository::class );
        $event_repo   = $this->createMock( Event_Repository::class );
        $player_serv  = $this->createMock( Player_Service::class );
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );

        $event = $this->getMockBuilder( Event::class )->disableOriginalConstructor()->onlyMethods( array( 'get_season' ) )->getMock();
        $event->set_competition( (object) [ 'is_team_entry' => true ] );
        $event->method( 'get_season' )->willReturn( '2026' );
        $query = $this->createMock( Event_Query_Service::class );
        $query->expects( $this->once() )->method( 'get_team_info' )->with( $event, 10 )->willReturn(
            (object) [
                'captain_id'   => 1,
                'match_day'    => 'Monday',
                'match_time'   => '18:00',
                'contactno'    => '111',
                'contactemail' => 'old@example.com',
            ]
        );

        $event_repo->expects( $this->once() )->method( 'find_by_id' )->with( 5 )->willReturn( $event );
        $team_repo->expects( $this->never() )->method( 'update_event_settings' );

        $service = new Team_Service( $team_repo, $club_repo, $event_repo, $player_serv, $fixture_repo, $query );
        // Missing captain (0)
        $msg = $service->update_team_event( 10, 5, 0, '111', 'old@example.com', 2, '19:30' );
        $this->assertSame( 'Team details missing', $msg );
    }

    public function test_team_service_update_team_event_settings_update_error(): void {
        $team_repo    = $this->createMock( Team_Repository::class );
        $club_repo    = $this->createMock( Club_Repository::class );
        $event_repo   = $this->createMock( Event_Repository::class );
        $player_serv  = $this->createMock( Player_Service::class );
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );

        $event = $this->getMockBuilder( Event::class )->disableOriginalConstructor()->onlyMethods( array( 'get_season' ) )->getMock();
        $event->set_competition( (object) [ 'is_team_entry' => true ] );
        $event->method( 'get_season' )->willReturn( '2026' );
        $query = $this->createMock( Event_Query_Service::class );
        $query->expects( $this->once() )->method( 'get_team_info' )->with( $event, 10 )->willReturn(
            (object) [
                'captain_id'   => 1,
                'match_day'    => 'Monday',
                'match_time'   => '18:00',
                'contactno'    => '111',
                'contactemail' => 'old@example.com',
            ]
        );

        $event_repo->expects( $this->once() )->method( 'find_by_id' )->with( 5 )->willReturn( $event );
        $team_repo->expects( $this->once() )
            ->method( 'update_event_settings' )
            ->willReturn( false );

        $service = new Team_Service( $team_repo, $club_repo, $event_repo, $player_serv, $fixture_repo, $query );
        $msg     = $service->update_team_event( 10, 5, 2, '111', 'old@example.com', 2, '19:30' );
        $this->assertSame( 'Team update error', $msg );
    }

    public function test_team_service_update_team_event_contact_update_error(): void {
        $team_repo    = $this->createMock( Team_Repository::class );
        $club_repo    = $this->createMock( Club_Repository::class );
        $event_repo   = $this->createMock( Event_Repository::class );
        $player_serv  = $this->createMock( Player_Service::class );
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );

        $event = $this->getMockBuilder( Event::class )->disableOriginalConstructor()->onlyMethods( array( 'get_season' ) )->getMock();
        $event->set_competition( (object) [ 'is_team_entry' => true ] );
        $event->method( 'get_season' )->willReturn( '2026' );
        $query = $this->createMock( Event_Query_Service::class );
        $query->expects( $this->once() )->method( 'get_team_info' )->with( $event, 10 )->willReturn(
            (object) [
                'captain_id'   => 2,
                'match_day'    => 'Tuesday',
                'match_time'   => '19:30',
                'contactno'    => '111',
                'contactemail' => 'old@example.com',
            ]
        );

        $event_repo->expects( $this->once() )->method( 'find_by_id' )->with( 5 )->willReturn( $event );
        $p2 = Player_Test_Factory::create( id: 2 );
        $player_serv->expects( $this->once() )->method( 'get_player' )->with( 2 )->willReturn( $p2 );
        $player_serv->expects( $this->once() )
            ->method( 'update_contact_details' )
            ->willReturn( false );

        $service = new Team_Service( $team_repo, $club_repo, $event_repo, $player_serv, $fixture_repo, $query );
        $msg     = $service->update_team_event( 10, 5, 2, '222', 'new@example.com', 2, '19:30' );
        $this->assertSame( 'Error updating team contact', $msg );
    }

    public function test_team_service_update_team_event_player_not_found(): void {
        $team_repo    = $this->createMock( Team_Repository::class );
        $club_repo    = $this->createMock( Club_Repository::class );
        $event_repo   = $this->createMock( Event_Repository::class );
        $player_serv  = $this->createMock( Player_Service::class );
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );

        $event = $this->getMockBuilder( Event::class )->disableOriginalConstructor()->onlyMethods( array( 'get_season' ) )->getMock();
        $event->set_competition( (object) [ 'is_team_entry' => true ] );
        $event->method( 'get_season' )->willReturn( '2026' );
        $query = $this->createMock( Event_Query_Service::class );
        $query->expects( $this->once() )->method( 'get_team_info' )->with( $event, 10 )->willReturn(
            (object) [
                'captain_id'   => 2,
                'match_day'    => 'Tuesday',
                'match_time'   => '19:30',
                'contactno'    => '111',
                'contactemail' => 'old@example.com',
            ]
        );

        $event_repo->expects( $this->once() )->method( 'find_by_id' )->with( 5 )->willReturn( $event );
        $player_serv->expects( $this->once() )->method( 'get_player' )->with( 2 )->willThrowException( new Player_Not_Found_Exception( 'Player not found' ) );

        $service = new Team_Service( $team_repo, $club_repo, $event_repo, $player_serv, $fixture_repo, $query );
        $msg     = $service->update_team_event( 10, 5, 2, '222', 'new@example.com', 2, '19:30' );
        $this->assertSame( 'Player not found', $msg );
    }

    public function test_constructor_only_accepts_hydration_dto_and_lenient_from_object_matches_factory(): void {
        $row = (object) [ 'id' => '3', 'title' => 'Leigh_A', 'club_id' => '7', 'unknown' => 'dropped' ];

        $lenient = Team::from_object( $row );

        $this->assertEquals( Team_Factory::from_object( $row ), $lenient );
        $this->assertEquals( $lenient, Team::from_array( (array) $row ) );
        $this->assertSame( 3, $lenient->id );
        $this->assertSame( 7, $lenient->club_id );
        $this->assertFalse( property_exists( $lenient, 'unknown' ) );
        $this->assertNull( new Team()->id );

        $this->expectException( TypeError::class );
        $this->assertInstanceOf(
            Team::class,
            new Team( $row )
        );
    }

    public function test_inherited_from_object_and_from_array_return_base_team_instance(): void {
        $subclass = new class() extends Team {};
        $row      = (object) [ 'id' => 12, 'title' => 'Subclass Team' ];

        $from_object = $subclass::from_object( $row );
        $this->assertInstanceOf( Team::class, $from_object );
        $this->assertNotInstanceOf( get_class( $subclass ), $from_object );
        $this->assertSame( 12, $from_object->id );

        $from_array = $subclass::from_array( [ 'id' => 12, 'title' => 'Subclass Team' ] );
        $this->assertInstanceOf( Team::class, $from_array );
        $this->assertNotInstanceOf( get_class( $subclass ), $from_array );
        $this->assertSame( 12, $from_array->id );
    }

    #[DataProvider( 'provide_all_team_properties' )]
    public function test_external_mutation_throws_error_for_private_set_properties( string $property, mixed $value ): void {
        $team = new Team();

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property Racketmanager\\\\Domain\\\\Team\\\\Team::\$' . $property . '(?: from scope [^\r\n]+)?$/' );

        $team->{$property} = $value;
    }

    /**
     * Data provider of all 21 properties for asymmetric visibility tests.
     *
     * @return array<string, array{string, mixed}> Property name and sample value.
     */
    public static function provide_all_team_properties(): array {
        return [
            'id'          => [ 'id', 123 ],
            'title'       => [ 'title', 'New Title' ],
            'stadium'     => [ 'stadium', 'New Stadium' ],
            'profile'     => [ 'profile', 'senior' ],
            'club_id'     => [ 'club_id', 45 ],
            'club'        => [ 'club', (object) [ 'id' => 45 ] ],
            'status'      => [ 'status', 'active' ],
            'player'      => [ 'player', 'Player Name' ],
            'player_1'    => [ 'player_1', 'Player 1' ],
            'player_2'    => [ 'player_2', 'Player 2' ],
            'player_1_id' => [ 'player_1_id', 10 ],
            'player_2_id' => [ 'player_2_id', 20 ],
            'type'        => [ 'type', 'league' ],
            'team_type'   => [ 'team_type', 'doubles' ],
            'home'        => [ 'home', 'Home Ground' ],
            'player_ids'  => [ 'player_ids', [ 10, 20 ] ],
            'players'     => [ 'players', [ (object) [ 'id' => 10 ] ] ],
            'team_ref'    => [ 'team_ref', '1_final_1' ],
            'info'        => [ 'info', (object) [ 'notes' => 'info' ] ],
            'standings'   => [ 'standings', [ 'points' => 10 ] ],
            'matches'     => [ 'matches', [ [ 'id' => 1 ] ] ],
        ];
    }

    public function test_default_initialization_state_and_public_reads(): void {
        $team = new Team();

        $this->assertNull( $team->id );
        $this->assertNull( $team->get_id() );
        $this->assertSame( '', $team->title );
        $this->assertSame( '', $team->get_title() );
        $this->assertSame( '', $team->get_name() );
        $this->assertNull( $team->stadium );
        $this->assertNull( $team->get_stadium() );
        $this->assertNull( $team->profile );
        $this->assertNull( $team->get_profile() );
        $this->assertNull( $team->get_profile_enum() );
        $this->assertNull( $team->club_id );
        $this->assertNull( $team->get_club_id() );
        $this->assertNull( $team->club );
        $this->assertNull( $team->get_club() );
        $this->assertNull( $team->status );
        $this->assertNull( $team->get_status() );
        $this->assertNull( $team->player );
        $this->assertNull( $team->get_player() );
        $this->assertNull( $team->player_1 );
        $this->assertNull( $team->get_player_1() );
        $this->assertNull( $team->player_2 );
        $this->assertNull( $team->get_player_2() );
        $this->assertNull( $team->player_1_id );
        $this->assertNull( $team->get_player_1_id() );
        $this->assertNull( $team->player_2_id );
        $this->assertNull( $team->get_player_2_id() );
        $this->assertSame( '', $team->type );
        $this->assertSame( '', $team->get_type() );
        $this->assertNull( $team->team_type );
        $this->assertNull( $team->get_team_type() );
        $this->assertNull( $team->home );
        $this->assertNull( $team->get_home() );
        $this->assertSame( [], $team->player_ids );
        $this->assertSame( [], $team->get_player_ids() );
        $this->assertSame( [], $team->players );
        $this->assertSame( [], $team->get_players() );
        $this->assertNull( $team->team_ref );
        $this->assertNull( $team->get_team_ref() );
        $this->assertNull( $team->info );
        $this->assertNull( $team->get_info() );
        $this->assertSame( [], $team->standings );
        $this->assertSame( [], $team->get_standings() );
        $this->assertSame( [], $team->matches );
        $this->assertSame( [], $team->get_matches() );
    }

    public function test_getters_and_setters_mutate_and_retrieve_properties(): void {
        $team = new Team();

        // id
        $team->set_id( 123 );
        $this->assertSame( 123, $team->id );
        $this->assertSame( 123, $team->get_id() );
        $team->set_id( null );
        $this->assertNull( $team->id );
        $this->assertNull( $team->get_id() );

        // title & name
        $team->set_title( 'Westcliff 1' );
        $this->assertSame( 'Westcliff 1', $team->title );
        $this->assertSame( 'Westcliff 1', $team->get_title() );
        $this->assertSame( 'Westcliff 1', $team->get_name() );
        $team->set_name( 'Westcliff A' );
        $this->assertSame( 'Westcliff A', $team->title );
        $this->assertSame( 'Westcliff A', $team->get_title() );
        $this->assertSame( 'Westcliff A', $team->get_name() );

        // club_id
        $team->set_club_id( 10 );
        $this->assertSame( 10, $team->club_id );
        $this->assertSame( 10, $team->get_club_id() );
        $team->set_club_id( null );
        $this->assertNull( $team->club_id );
        $this->assertNull( $team->get_club_id() );

        // Club object.
        $club_obj = (object) [ 'id' => 10, 'name' => 'Westcliff' ];
        $team->set_club( $club_obj );
        $this->assertSame( $club_obj, $team->club );
        $this->assertSame( $club_obj, $team->get_club() );
        $team->set_club( null );
        $this->assertNull( $team->club );
        $this->assertNull( $team->get_club() );

        // Stadium.
        $team->set_stadium( 'Court 1' );
        $this->assertSame( 'Court 1', $team->stadium );
        $this->assertSame( 'Court 1', $team->get_stadium() );
        $team->set_stadium( null );
        $this->assertNull( $team->stadium );
        $this->assertNull( $team->get_stadium() );

        // Type.
        $team->set_type( 'league' );
        $this->assertSame( 'league', $team->type );
        $this->assertSame( 'league', $team->get_type() );

        // Team type.
        $team->set_team_type( 'M' );
        $this->assertSame( 'M', $team->team_type );
        $this->assertSame( 'M', $team->get_team_type() );
        $team->set_team_type( null );
        $this->assertNull( $team->team_type );
        $this->assertNull( $team->get_team_type() );

        // Status.
        $team->set_status( 'active' );
        $this->assertSame( 'active', $team->status );
        $this->assertSame( 'active', $team->get_status() );
        $team->set_status( null );
        $this->assertNull( $team->status );
        $this->assertNull( $team->get_status() );

        // Profile (int casting and string/enum handling).
        $team->set_profile( 2 );
        $this->assertSame( 2, $team->profile );
        $this->assertSame( '2', $team->get_profile() );
        $this->assertSame( Team_Profile::NEW, $team->get_profile_enum() );

        $team->set_profile( 'senior' );
        $this->assertSame( 'senior', $team->profile );
        $this->assertSame( 'senior', $team->get_profile() );

        $team->set_profile_enum( Team_Profile::ACTIVE );
        $this->assertSame( Team_Profile::ACTIVE->value, $team->profile );
        $this->assertSame( (string) Team_Profile::ACTIVE->value, $team->get_profile() );
        $this->assertSame( Team_Profile::ACTIVE, $team->get_profile_enum() );

        $team->set_profile( null );
        $this->assertNull( $team->profile );
        $this->assertNull( $team->get_profile() );
        $this->assertNull( $team->get_profile_enum() );

        // Player names.
        $team->set_player( 'Player Name' );
        $this->assertSame( 'Player Name', $team->player );
        $this->assertSame( 'Player Name', $team->get_player() );
        $team->set_player( null );
        $this->assertNull( $team->player );
        $this->assertNull( $team->get_player() );

        $team->set_player_1( 'Player One' );
        $this->assertSame( 'Player One', $team->player_1 );
        $this->assertSame( 'Player One', $team->get_player_1() );
        $team->set_player_1( null );
        $this->assertNull( $team->player_1 );
        $this->assertNull( $team->get_player_1() );

        $team->set_player_2( 'Player Two' );
        $this->assertSame( 'Player Two', $team->player_2 );
        $this->assertSame( 'Player Two', $team->get_player_2() );
        $team->set_player_2( null );
        $this->assertNull( $team->player_2 );
        $this->assertNull( $team->get_player_2() );

        // Player IDs.
        $team->set_player_1_id( 101 );
        $this->assertSame( 101, $team->player_1_id );
        $this->assertSame( 101, $team->get_player_1_id() );
        $team->set_player_1_id( null );
        $this->assertNull( $team->player_1_id );
        $this->assertNull( $team->get_player_1_id() );

        $team->set_player_2_id( 102 );
        $this->assertSame( 102, $team->player_2_id );
        $this->assertSame( 102, $team->get_player_2_id() );
        $team->set_player_2_id( null );
        $this->assertNull( $team->player_2_id );
        $this->assertNull( $team->get_player_2_id() );

        // Home (mixed).
        $team->set_home( 'Westcliff LTC' );
        $this->assertSame( 'Westcliff LTC', $team->home );
        $this->assertSame( 'Westcliff LTC', $team->get_home() );
        $home_data = [ 'courts' => 4 ];
        $team->set_home( $home_data );
        $this->assertSame( $home_data, $team->home );
        $this->assertSame( $home_data, $team->get_home() );

        // Player IDs array.
        $team->set_player_ids( [ 1 => 10, 2 => 20 ] );
        $this->assertSame( [ 1 => 10, 2 => 20 ], $team->player_ids );
        $this->assertSame( [ 1 => 10, 2 => 20 ], $team->get_player_ids() );

        // Players array.
        $p1 = Player_Test_Factory::create( id: 10 );
        $p2 = Player_Test_Factory::create( id: 20 );
        $team->set_players( [ $p1, $p2 ] );
        $this->assertSame( [ $p1, $p2 ], $team->players );
        $this->assertSame( [ $p1, $p2 ], $team->get_players() );

        // Team reference.
        $team->set_team_ref( '1_semi-final_1' );
        $this->assertSame( '1_semi-final_1', $team->team_ref );
        $this->assertSame( '1_semi-final_1', $team->get_team_ref() );
        $team->set_team_ref( null );
        $this->assertNull( $team->team_ref );
        $this->assertNull( $team->get_team_ref() );

        // Info (mixed).
        $info_obj = (object) [ 'captain' => 'John', 'phone' => '123' ];
        $team->set_info( $info_obj );
        $this->assertSame( $info_obj, $team->info );
        $this->assertSame( $info_obj, $team->get_info() );

        // Standings (mixed).
        $standings = [ 'played' => 10, 'points' => 25 ];
        $team->set_standings( $standings );
        $this->assertSame( $standings, $team->standings );
        $this->assertSame( $standings, $team->get_standings() );

        // Matches array.
        $matches = [ [ 'id' => 1 ], [ 'id' => 2 ] ];
        $team->set_matches( $matches );
        $this->assertSame( $matches, $team->matches );
        $this->assertSame( $matches, $team->get_matches() );
    }

    public function test_dto_hydration_roundtrip_all_21_properties(): void {
        $club_obj   = (object) [ 'id' => 15, 'name' => 'Westcliff' ];
        $info_obj   = (object) [ 'captain' => 'Captain Jack' ];
        $player_obj = Player_Test_Factory::create( id: 101 );

        $dto = new Team_Hydration_DTO(
            id: 42,
            title: 'Westcliff First Team',
            stadium: 'Court Central',
            profile: 2,
            club_id: 15,
            club: $club_obj,
            status: 'active',
            player: 'Singles Player',
            player_1: 'Doubles Player 1',
            player_2: 'Doubles Player 2',
            player_1_id: 101,
            player_2_id: 102,
            type: 'league',
            team_type: 'doubles',
            home: 'Home Stadium',
            player_ids: [ 101, 102 ],
            players: [ $player_obj ],
            team_ref: 'custom_ref_123',
            info: $info_obj,
            standings: [ 'played' => 10, 'points' => 20 ],
            matches: [ [ 'match_id' => 999 ] ],
        );

        $team = new Team( $dto );

        $this->assertSame( 42, $team->id );
        $this->assertSame( 42, $team->get_id() );
        $this->assertSame( 'Westcliff First Team', $team->title );
        $this->assertSame( 'Westcliff First Team', $team->get_title() );
        $this->assertSame( 'Court Central', $team->stadium );
        $this->assertSame( 'Court Central', $team->get_stadium() );
        $this->assertSame( 2, $team->profile );
        $this->assertSame( '2', $team->get_profile() );
        $this->assertSame( Team_Profile::NEW, $team->get_profile_enum() );
        $this->assertSame( 15, $team->club_id );
        $this->assertSame( 15, $team->get_club_id() );
        $this->assertSame( $club_obj, $team->club );
        $this->assertSame( $club_obj, $team->get_club() );
        $this->assertSame( 'active', $team->status );
        $this->assertSame( 'active', $team->get_status() );
        $this->assertSame( 'Singles Player', $team->player );
        $this->assertSame( 'Singles Player', $team->get_player() );
        $this->assertSame( 'Doubles Player 1', $team->player_1 );
        $this->assertSame( 'Doubles Player 1', $team->get_player_1() );
        $this->assertSame( 'Doubles Player 2', $team->player_2 );
        $this->assertSame( 'Doubles Player 2', $team->get_player_2() );
        $this->assertSame( 101, $team->player_1_id );
        $this->assertSame( 101, $team->get_player_1_id() );
        $this->assertSame( 102, $team->player_2_id );
        $this->assertSame( 102, $team->get_player_2_id() );
        $this->assertSame( 'league', $team->type );
        $this->assertSame( 'league', $team->get_type() );
        $this->assertSame( 'doubles', $team->team_type );
        $this->assertSame( 'doubles', $team->get_team_type() );
        $this->assertSame( 'Home Stadium', $team->home );
        $this->assertSame( 'Home Stadium', $team->get_home() );
        $this->assertSame( [ 101, 102 ], $team->player_ids );
        $this->assertSame( [ 101, 102 ], $team->get_player_ids() );
        $this->assertSame( [ $player_obj ], $team->players );
        $this->assertSame( [ $player_obj ], $team->get_players() );
        $this->assertSame( 'custom_ref_123', $team->team_ref );
        $this->assertSame( 'custom_ref_123', $team->get_team_ref() );
        $this->assertSame( $info_obj, $team->info );
        $this->assertSame( $info_obj, $team->get_info() );
        $this->assertSame( [ 'played' => 10, 'points' => 20 ], $team->standings );
        $this->assertSame( [ 'played' => 10, 'points' => 20 ], $team->get_standings() );
        $this->assertSame( [ [ 'match_id' => 999 ] ], $team->matches );
        $this->assertSame( [ [ 'match_id' => 999 ] ], $team->get_matches() );

        $factory_team = Team_Factory::create_from_dto( $dto );
        $this->assertEquals( $team, $factory_team );
    }

    public function test_is_active_predicate(): void {
        $team = new Team();
        $this->assertFalse( $team->is_active() );

        $team->set_status( 'active' );
        $this->assertTrue( $team->is_active() );

        $team->set_status( 'inactive' );
        $this->assertFalse( $team->is_active() );

        $team->set_status( 'pending' );
        $this->assertFalse( $team->is_active() );

        $team->set_status( null );
        $this->assertFalse( $team->is_active() );
    }

    public function test_is_placeholder_predicate_and_edge_cases(): void {
        $default_team = new Team();
        $this->assertFalse( $default_team->is_placeholder() );

        // With team_ref set
        $ref_team = new Team();
        $ref_team->set_team_ref( '1_semi-final_1' );
        $this->assertTrue( $ref_team->is_placeholder() );

        // Placeholder parsed from title with underscore
        $winner_team = Team_Factory::from_array( [ 'title' => '1_semi-final_1' ] );
        $this->assertTrue( $winner_team->is_placeholder() );
        $this->assertSame( '1_semi-final_1', $winner_team->team_ref );
        $this->assertSame( 'Winner of', $winner_team->title );

        $loser_team = Team_Factory::from_array( [ 'title' => '2_final_1' ] );
        $this->assertTrue( $loser_team->is_placeholder() );
        $this->assertSame( '2_final_1', $loser_team->team_ref );
        $this->assertSame( 'Loser of', $loser_team->title );

        // Placeholder title without a team ref.
        $title_only_winner = new Team();
        $title_only_winner->set_title( '1_semi-final_1' );
        $this->assertTrue( $title_only_winner->is_placeholder() );
        $this->assertNull( $title_only_winner->team_ref );

        $title_only_loser = new Team();
        $title_only_loser->set_title( '2_final_1' );
        $this->assertTrue( $title_only_loser->is_placeholder() );
        $this->assertNull( $title_only_loser->team_ref );

        // Titles that are not valid placeholder references.
        $raw_title_num1 = new Team();
        $raw_title_num1->set_title( '1' );
        $this->assertFalse( $raw_title_num1->is_placeholder() );

        $raw_title_num2 = new Team();
        $raw_title_num2->set_title( '2' );
        $this->assertFalse( $raw_title_num2->is_placeholder() );

        $raw_title_non_ref = new Team();
        $raw_title_non_ref->set_title( 'Westcliff_Men_1' );
        $this->assertFalse( $raw_title_non_ref->is_placeholder() );

        // Edge cases: title '1' and '2' without underscore must NOT be treated as placeholders
        $team_num1 = Team_Factory::from_array( [ 'title' => '1' ] );
        $this->assertFalse( $team_num1->is_placeholder() );
        $this->assertNull( $team_num1->team_ref );
        $this->assertSame( '1', $team_num1->title );

        $team_num2 = Team_Factory::from_array( [ 'title' => '2' ] );
        $this->assertFalse( $team_num2->is_placeholder() );
        $this->assertNull( $team_num2->team_ref );
        $this->assertSame( '2', $team_num2->title );

        // Title with underscore but not matching placeholder ref pattern
        $non_ref_team = Team_Factory::from_array( [ 'title' => 'Westcliff_Men_1' ] );
        $this->assertFalse( $non_ref_team->is_placeholder() );

        // Normal team name
        $normal_team = Team_Factory::from_array( [ 'title' => 'Leigh 1' ] );
        $this->assertFalse( $normal_team->is_placeholder() );
    }

    public function test_is_bye_predicate(): void {
        $default_team = new Team();
        $this->assertFalse( $default_team->is_bye() );

        // ID is -1
        $bye_by_id = Team_Factory::from_array( [ 'id' => -1, 'title' => 'TBD' ] );
        $this->assertTrue( $bye_by_id->is_bye() );

        // Title is 'Bye'
        $bye_by_title = Team_Factory::from_array( [ 'id' => 5, 'title' => 'Bye' ] );
        $this->assertTrue( $bye_by_title->is_bye() );

        // Both ID is -1 and Title is 'Bye'
        $canonical_bye = Team_Factory::from_array( [ 'id' => -1, 'title' => 'Bye' ] );
        $this->assertTrue( $canonical_bye->is_bye() );

        // Normal team
        $normal_team = Team_Factory::from_array( [ 'id' => 10, 'title' => 'Westcliff 1' ] );
        $this->assertFalse( $normal_team->is_bye() );
    }
}
