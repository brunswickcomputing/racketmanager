<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Team;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\DTO\Team\Team_Hydration_DTO;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Team\Team;
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
        $this->assertSame( 'custom_roster_data', $dto->roster );

        $array_dto = Team_Hydration_DTO::from_array( [
            'id'    => '99',
            'title' => 'Array Team',
        ] );
        $this->assertSame( 99, $array_dto->id );
        $this->assertSame( 'Array Team', $array_dto->title );
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
        $p1 = new Player();
        $p1->id = 10;
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

    public function test_team_repository_find_by_id_and_cache_invalidation(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache_deleted'] = [];
        $GLOBALS['wp_stubs_cache'] = [];

        $wpdb = $this->createMock( wpdb::class );
        $wpdb->prefix = 'wp_';
        $player_repo = $this->createMock( Player_Repository_Interface::class );

        $p_player = new Player();
        $p_player->id = 77;
        $player_repo->expects( $this->once() )->method( 'find' )->with( 77 )->willReturn( $p_player );

        $wpdb->method( 'prepare' )->willReturnCallback( fn( ...$args ) => 'PREPARED_QUERY' );
        $wpdb->expects( $this->once() )->method( 'get_row' )->willReturn( (object) [
            'id'        => 50,
            'title'     => 'Player Team',
            'team_type' => 'P',
            'roster'    => null,
            'stadium'   => null,
            'home'      => null,
            'profile'   => null,
            'status'    => null,
            'club_id'   => null,
            'type'      => null,
        ] );
        $wpdb->expects( $this->once() )->method( 'get_results' )->willReturn( [
            (object) [ 'player_id' => 77 ],
        ] );

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
            ->willReturn( [
                (object) [ 'id' => 101, 'home_team' => '50', 'away_team' => '60' ],
                (object) [ 'id' => 102, 'home_team' => '70', 'away_team' => '50' ],
            ] );

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

        $service = new Team_Service( $team_repo, $club_repo, $event_repo, $player_serv, $fixture_repo );
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

        $service = new Team_Service( $team_repo, $club_repo, $event_repo, $player_serv, $fixture_repo );
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

        $service = new Team_Service( $team_repo, $club_repo, $event_repo, $player_serv, $fixture_repo );
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

        $event = $this->createMock( Event::class );
        $event->competition = (object) [ 'is_team_entry' => true ];
        $event->method( 'get_season' )->willReturn( '2026' );
        $query = $this->createMock( Event_Query_Service::class );
        $query->expects( $this->once() )->method( 'get_team_info' )->with( $event, 10 )->willReturn( (object) [
            'captain_id'   => 1,
            'match_day'    => 'Monday',
            'match_time'   => '18:00',
            'contactno'    => '111',
            'contactemail' => 'old@example.com',
        ] );

        $event_repo->expects( $this->once() )->method( 'find_by_id' )->with( 5 )->willReturn( $event );

        $team_repo->expects( $this->once() )
            ->method( 'update_event_settings' )
            ->with( 10, 5, '2026', '2', 'Wednesday', '19:30' )
            ->willReturn( true );

        $p2 = new Player();
        $p2->id = 2;
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

        $event = $this->createMock( Event::class );
        $event->competition = (object) [ 'is_team_entry' => true ];
        $event->method( 'get_season' )->willReturn( '2026' );
        $query = $this->createMock( Event_Query_Service::class );
        $query->expects( $this->once() )->method( 'get_team_info' )->with( $event, 10 )->willReturn( (object) [
            'captain_id'   => 2,
            'match_day'    => 'Wednesday',
            'match_time'   => '19:30',
            'contactno'    => '222',
            'contactemail' => 'new@example.com',
        ] );

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

        $event = $this->createMock( Event::class );
        $event->competition = (object) [ 'is_team_entry' => true ];
        $event->method( 'get_season' )->willReturn( '2026' );
        $query = $this->createMock( Event_Query_Service::class );
        $query->expects( $this->once() )->method( 'get_team_info' )->with( $event, 10 )->willReturn( (object) [
            'captain_id'   => 1,
            'match_day'    => 'Monday',
            'match_time'   => '18:00',
            'contactno'    => '111',
            'contactemail' => 'old@example.com',
        ] );

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

        $event = $this->createMock( Event::class );
        $event->competition = (object) [ 'is_team_entry' => true ];
        $event->method( 'get_season' )->willReturn( '2026' );
        $query = $this->createMock( Event_Query_Service::class );
        $query->expects( $this->once() )->method( 'get_team_info' )->with( $event, 10 )->willReturn( (object) [
            'captain_id'   => 1,
            'match_day'    => 'Monday',
            'match_time'   => '18:00',
            'contactno'    => '111',
            'contactemail' => 'old@example.com',
        ] );

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

        $event = $this->createMock( Event::class );
        $event->competition = (object) [ 'is_team_entry' => true ];
        $event->method( 'get_season' )->willReturn( '2026' );
        $query = $this->createMock( Event_Query_Service::class );
        $query->expects( $this->once() )->method( 'get_team_info' )->with( $event, 10 )->willReturn( (object) [
            'captain_id'   => 2,
            'match_day'    => 'Tuesday',
            'match_time'   => '19:30',
            'contactno'    => '111',
            'contactemail' => 'old@example.com',
        ] );

        $event_repo->expects( $this->once() )->method( 'find_by_id' )->with( 5 )->willReturn( $event );
        $p2 = new Player();
        $p2->id = 2;
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

        $event = $this->createMock( Event::class );
        $event->competition = (object) [ 'is_team_entry' => true ];
        $event->method( 'get_season' )->willReturn( '2026' );
        $query = $this->createMock( Event_Query_Service::class );
        $query->expects( $this->once() )->method( 'get_team_info' )->with( $event, 10 )->willReturn( (object) [
            'captain_id'   => 2,
            'match_day'    => 'Tuesday',
            'match_time'   => '19:30',
            'contactno'    => '111',
            'contactemail' => 'old@example.com',
        ] );

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
        $subclass = new class extends Team {};
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
}
