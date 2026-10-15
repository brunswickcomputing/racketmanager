<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\League;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\League\League_Schedule_Service;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;
use wpdb;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class League_Schedule_Service_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Fixture_Maintenance_Service $fixture_maintenance_service;
    private League_Schedule_Service $service;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->fixture_maintenance_service = $this->createMock( Fixture_Maintenance_Service::class );
        $this->service                     = new League_Schedule_Service( $this->fixture_maintenance_service );
        $GLOBALS['wp_stubs_events']        = array();
        $GLOBALS['wp_stubs_leagues']       = array();
        $GLOBALS['wp_stubs_teams']         = array();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    /**
     * Create a mocked wpdb instance that realistically formats prepared statements
     */
    private function create_wpdb_mock(): wpdb {
        $wpdb_mock = $this->createMock( wpdb::class );
        $wpdb_mock->racketmanager              = 'wp_racketmanager';
        $wpdb_mock->racketmanager_teams        = 'wp_racketmanager_teams';
        $wpdb_mock->racketmanager_league_teams = 'wp_racketmanager_league_teams';
        $wpdb_mock->racketmanager_seasons      = 'wp_racketmanager_seasons';
        $wpdb_mock->method( 'prepare' )
            ->willReturnCallback( function ( string $query, ...$args ) {
                if ( empty( $args ) ) {
                    return $query;
                }
                $formatted = $query;
                foreach ( $args as $arg ) {
                    $formatted = preg_replace( '/%[sd]/', (string) $arg, $formatted, 1 );
                }
                return $formatted;
            } );
        return $wpdb_mock;
    }

    public function test_delete_event_matches_success_deletes_each_fixture(): void {
        $event     = $this->createMock( Event::class );
        $event->id = 10;
        $event->method( 'get_season' )->willReturn( '2026' );

        $league     = $this->createMock( League::class );
        $match1     = new stdClass();
        $match1->id = 101;
        $match2     = new stdClass();
        $match2->id = 102;
        $league->expects( self::once() )->method( 'get_matches' )->with( array( 'season' => '2026' ) )->willReturn( array( $match1, $match2 ) );

        $event->method( 'get_leagues' )->willReturn( array( $league ) );

        $GLOBALS['wp_stubs_events'][10] = $event;

        $racketmanager = $this->createMock( RacketManager::class );
        $racketmanager->expects( self::once() )
            ->method( 'get_matches' )
            ->with( array( 'count' => true, 'event_id' => 10, 'season' => '2026', 'time' => 'latest' ) )
            ->willReturn( 0 );
        $GLOBALS['racketmanager'] = $racketmanager;

        $this->fixture_maintenance_service->expects( self::exactly( 2 ) )
            ->method( 'delete_fixture' )
            ->willReturnCallback( function ( int $fixture_id ) {
                self::assertContains( $fixture_id, array( 101, 102 ) );
                return true;
            } );

        $result = $this->service->delete_event_matches( array( 10 ) );

        self::assertSame( 'Matches deleted', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_delete_event_matches_returns_error_when_event_has_completed_matches(): void {
        $event     = $this->createMock( Event::class );
        $event->id = 10;
        $event->method( 'get_season' )->willReturn( '2026' );
        $GLOBALS['wp_stubs_events'][10] = $event;

        $racketmanager = $this->createMock( RacketManager::class );
        $racketmanager->method( 'get_matches' )->willReturn( 3 );
        $GLOBALS['racketmanager'] = $racketmanager;

        $this->fixture_maintenance_service->expects( self::never() )->method( 'delete_fixture' );

        $result = $this->service->delete_event_matches( array( 10 ) );

        self::assertSame( 'Event has completed matches', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_delete_event_matches_returns_warning_when_some_events_have_completed_matches(): void {
        $event1     = $this->createMock( Event::class );
        $event1->id = 10;
        $event1->method( 'get_season' )->willReturn( '2026' );

        $event2     = $this->createMock( Event::class );
        $event2->id = 20;
        $event2->method( 'get_season' )->willReturn( '2026' );

        $league    = $this->createMock( League::class );
        $match     = new stdClass();
        $match->id = 201;
        $league->method( 'get_matches' )->willReturn( array( $match ) );
        $event2->method( 'get_leagues' )->willReturn( array( $league ) );

        $GLOBALS['wp_stubs_events'][10] = $event1;
        $GLOBALS['wp_stubs_events'][20] = $event2;

        $racketmanager = $this->createMock( RacketManager::class );
        $racketmanager->method( 'get_matches' )
            ->willReturnCallback( function ( array $args ) {
                return ( 10 === $args['event_id'] ) ? 2 : 0;
            } );
        $GLOBALS['racketmanager'] = $racketmanager;

        $this->fixture_maintenance_service->expects( self::once() )
            ->method( 'delete_fixture' )
            ->with( 201 );

        $result = $this->service->delete_event_matches( array( 10, 20 ) );

        self::assertSame( 'Some matches deleted, but some events have completed matches', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }

    public function test_schedule_league_matches_validation_fails_with_existing_matches(): void {
        $event       = $this->createMock( Event::class );
        $event->id   = 10;
        $event->name = 'Summer League';
        $event->method( 'get_season' )->willReturn( '2026' );
        $GLOBALS['wp_stubs_events'][10] = $event;

        $racketmanager = $this->createMock( RacketManager::class );
        $racketmanager->method( 'get_matches' )->willReturn( 5 );
        $GLOBALS['racketmanager'] = $racketmanager;

        $result = $this->service->schedule_league_matches( array( 10 ) );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertStringContainsString( 'Summer League already has matches scheduled for 2026', $result->message );
    }

    public function test_schedule_league_matches_validation_fails_with_differing_match_days(): void {
        $event1                 = $this->createMock( Event::class );
        $event1->id             = 10;
        $event1->name           = 'Event 1';
        $event1->current_season = array(
            'num_match_days' => 5,
            'match_dates'    => array( '2026-05-01', '2026-05-08' ),
            'home_away'      => true,
        );
        $event1->method( 'get_season' )->willReturn( '2026' );

        $event2                 = $this->createMock( Event::class );
        $event2->id             = 20;
        $event2->name           = 'Event 2';
        $event2->current_season = array(
            'num_match_days' => 6,
            'match_dates'    => array( '2026-05-01', '2026-05-08' ),
            'home_away'      => true,
        );
        $event2->method( 'get_season' )->willReturn( '2026' );

        $GLOBALS['wp_stubs_events'][10] = $event1;
        $GLOBALS['wp_stubs_events'][20] = $event2;

        $racketmanager = $this->createMock( RacketManager::class );
        $racketmanager->method( 'get_matches' )->willReturn( 0 );
        $GLOBALS['racketmanager'] = $racketmanager;

        $result = $this->service->schedule_league_matches( array( 10, 20 ) );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertStringContainsString( 'Events have different number of match days', $result->message );
    }

    public function test_schedule_league_matches_validation_fails_with_differing_match_dates(): void {
        $event1                 = $this->createMock( Event::class );
        $event1->id             = 10;
        $event1->name           = 'Event 1';
        $event1->current_season = array(
            'num_match_days' => 6,
            'match_dates'    => array( '2026-05-01', '2026-05-08' ),
            'home_away'      => true,
        );
        $event1->method( 'get_season' )->willReturn( '2026' );

        $event2                 = $this->createMock( Event::class );
        $event2->id             = 20;
        $event2->name           = 'Event 2';
        $event2->current_season = array(
            'num_match_days' => 6,
            'match_dates'    => array( '2026-05-02', '2026-05-09' ),
            'home_away'      => true,
        );
        $event2->method( 'get_season' )->willReturn( '2026' );

        $GLOBALS['wp_stubs_events'][10] = $event1;
        $GLOBALS['wp_stubs_events'][20] = $event2;

        $racketmanager = $this->createMock( RacketManager::class );
        $racketmanager->method( 'get_matches' )->willReturn( 0 );
        $GLOBALS['racketmanager'] = $racketmanager;

        $result = $this->service->schedule_league_matches( array( 10, 20 ) );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertStringContainsString( 'Events have different match dates', $result->message );
    }

    public function test_schedule_league_matches_validation_fails_with_differing_home_away_setting(): void {
        $event1                 = $this->createMock( Event::class );
        $event1->id             = 10;
        $event1->name           = 'Event 1';
        $event1->current_season = array(
            'num_match_days' => 6,
            'match_dates'    => array( '2026-05-01', '2026-05-08' ),
            'home_away'      => true,
        );
        $event1->method( 'get_season' )->willReturn( '2026' );

        $event2                 = $this->createMock( Event::class );
        $event2->id             = 20;
        $event2->name           = 'Event 2';
        $event2->current_season = array(
            'num_match_days' => 6,
            'match_dates'    => array( '2026-05-01', '2026-05-08' ),
            'home_away'      => false,
        );
        $event2->method( 'get_season' )->willReturn( '2026' );

        $GLOBALS['wp_stubs_events'][10] = $event1;
        $GLOBALS['wp_stubs_events'][20] = $event2;

        $racketmanager = $this->createMock( RacketManager::class );
        $racketmanager->method( 'get_matches' )->willReturn( 0 );
        $GLOBALS['racketmanager'] = $racketmanager;

        $result = $this->service->schedule_league_matches( array( 10, 20 ) );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertStringContainsString( 'Events have different home / away setting', $result->message );
    }

    public function test_schedule_league_matches_validation_fails_with_missing_match_dates_on_first_event(): void {
        $event                 = $this->createMock( Event::class );
        $event->id             = 10;
        $event->name           = 'Premier Event';
        $event->current_season = array(
            'num_match_days' => 6,
        );
        $event->method( 'get_season' )->willReturn( '2026' );
        $GLOBALS['wp_stubs_events'][10] = $event;

        $racketmanager = $this->createMock( RacketManager::class );
        $racketmanager->method( 'get_matches' )->willReturn( 0 );
        $GLOBALS['racketmanager'] = $racketmanager;

        $result = $this->service->schedule_league_matches( array( 10 ) );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertStringContainsString( 'Events match dates not set for Premier Event', $result->message );
    }

    public function test_validate_events_fails_when_teams_have_missing_match_days(): void {
        $event                 = $this->createMock( Event::class );
        $event->id             = 10;
        $event->name           = 'Event 1';
        $event->current_season = array(
            'num_match_days' => 6,
            'match_dates'    => array( '2026-05-01', '2026-05-08' ),
            'home_away'      => true,
        );
        $event->method( 'get_season' )->willReturn( '2026' );
        $GLOBALS['wp_stubs_events'][10] = $event;

        $racketmanager = $this->createMock( RacketManager::class );
        $racketmanager->method( 'get_matches' )->willReturn( 0 );
        $GLOBALS['racketmanager'] = $racketmanager;

        $wpdb_mock = $this->create_wpdb_mock();
        $wpdb_mock->method( 'get_var' )->willReturn( '2026' );

        $team1 = new stdClass();
        $team1->title = 'Club A 1st';
        $team2 = new stdClass();
        $team2->title = 'Club B 2nd';
        $wpdb_mock->method( 'get_results' )->willReturn( array( $team1, $team2 ) );

        $GLOBALS['wpdb'] = $wpdb_mock;

        $validation = $this->service->validate_events( array( 10 ) );

        self::assertFalse( $validation->success );
        self::assertStringContainsString( 'Missing match days for Club A 1st and Club B 2nd', $validation->message );
    }

    public function test_validate_events_success_with_single_event(): void {
        $event                 = $this->createMock( Event::class );
        $event->id             = 10;
        $event->name           = 'Event 1';
        $event->current_season = array(
            'num_match_days' => 10,
            'match_dates'    => array( '2026-05-01', '2026-05-08' ),
            'home_away'      => true,
        );
        $event->method( 'get_season' )->willReturn( '2026' );
        $GLOBALS['wp_stubs_events'][10] = $event;

        $racketmanager = $this->createMock( RacketManager::class );
        $racketmanager->method( 'get_matches' )->willReturn( 0 );
        $GLOBALS['racketmanager'] = $racketmanager;

        $wpdb_mock = $this->create_wpdb_mock();
        $wpdb_mock->method( 'get_var' )->willReturn( '2026' );
        $wpdb_mock->method( 'get_results' )->willReturn( array() );

        $GLOBALS['wpdb'] = $wpdb_mock;

        $validation = $this->service->validate_events( array( 10 ) );

        self::assertTrue( $validation->success );
        self::assertSame( 5, $validation->num_rounds );
        self::assertTrue( $validation->home_away );
    }

    public function test_handle_teams_in_same_division_assigns_first_round_slots(): void {
        $wpdb_mock = $this->create_wpdb_mock();

        $club_league = (object) array( 'club_id' => 1, 'league_id' => 100 );
        $team_a = (object) array( 'id' => 501, 'team_id' => 11, 'league_id' => 100 );
        $team_b = (object) array( 'id' => 502, 'team_id' => 12, 'league_id' => 100 );

        $wpdb_mock->method( 'get_results' )
            ->willReturnCallback( function ( string $query ) use ( $club_league, $team_a, $team_b ) {
                if ( str_contains( $query, 'GROUP BY t.`club_id`, tbl.`league_id`' ) ) {
                    return array( $club_league );
                }
                if ( str_contains( $query, 'ORDER BY tbl.`team_id`' ) ) {
                    return array( $team_a, $team_b );
                }
                return array();
            } );

        $updated_groups = array();
        $wpdb_mock->method( 'query' )
            ->willReturnCallback( function ( string $query ) use ( &$updated_groups ) {
                $updated_groups[] = $query;
                return 1;
            } );

        $GLOBALS['wpdb'] = $wpdb_mock;

        $validation = new stdClass();
        $validation->success  = true;
        $validation->messages = array();

        $result = $this->service->handle_teams_in_same_division( array( 10 ), '2026', $validation, array( 1, 2, 3, 4, 5, 6 ) );

        self::assertTrue( $result->success );
        self::assertSame(
            array(
                'UPDATE wp_racketmanager_league_teams SET `group` = 2 WHERE `id` = 501',
                'UPDATE wp_racketmanager_league_teams SET `group` = 5 WHERE `id` = 502',
            ),
            $updated_groups
        );
    }

    public function test_handle_teams_in_different_divisions_with_same_match_day_assigns_alternating_slots(): void {
        $wpdb_mock = $this->create_wpdb_mock();

        $event_team = (object) array( 'club_id' => 1, 'match_day' => 'Monday', 'match_time' => '19:00' );
        $team_div1  = (object) array( 'id' => 501, 'team_id' => 11, 'league_id' => 100, 'group' => '' );
        $team_div2  = (object) array( 'id' => 502, 'team_id' => 12, 'league_id' => 200, 'group' => '' );

        $wpdb_mock->method( 'get_results' )
            ->willReturnCallback( function ( string $query ) use ( $event_team, $team_div1, $team_div2 ) {
                if ( str_contains( $query, 'GROUP BY t.`club_id`, tbl.`match_day`' ) ) {
                    return array( $event_team );
                }
                if ( str_contains( $query, 'tbl.`match_time` =' ) ) {
                    return array( $team_div1, $team_div2 );
                }
                return array();
            } );

        $updated_groups = array();
        $wpdb_mock->method( 'query' )
            ->willReturnCallback( function ( string $query ) use ( &$updated_groups ) {
                $updated_groups[] = $query;
                return 1;
            } );

        $GLOBALS['wpdb'] = $wpdb_mock;

        $validation = new stdClass();
        $validation->success  = true;
        $validation->messages = array();

        $result = $this->service->handle_teams_in_different_divisions_with_same_match_day( array( 10, 20 ), '2026', 6, $validation, array( 1, 2, 3, 4, 5, 6 ) );

        self::assertTrue( $result->success );
        self::assertSame(
            array(
                'UPDATE wp_racketmanager_league_teams SET `group` = 1 WHERE `id` = 501',
                'UPDATE wp_racketmanager_league_teams SET `group` = 4 WHERE `id` = 502',
            ),
            $updated_groups
        );
    }

    public function test_handle_teams_in_different_divisions_with_same_match_day_resolves_alt_ref_when_team1_has_group(): void {
        $wpdb_mock = $this->create_wpdb_mock();

        $event_team = (object) array( 'club_id' => 1, 'match_day' => 'Monday', 'match_time' => '19:00' );
        // Team 1 in div 1 already has group '2' assigned
        $team_div1  = (object) array( 'id' => 501, 'team_id' => 11, 'league_id' => 100, 'group' => '2' );
        $team_div2  = (object) array( 'id' => 502, 'team_id' => 12, 'league_id' => 200, 'group' => '' );

        $wpdb_mock->method( 'get_results' )
            ->willReturnCallback( function ( string $query ) use ( $event_team, $team_div1, $team_div2 ) {
                if ( str_contains( $query, 'GROUP BY t.`club_id`, tbl.`match_day`' ) ) {
                    return array( $event_team );
                }
                if ( str_contains( $query, 'tbl.`match_time` =' ) ) {
                    return array( $team_div1, $team_div2 );
                }
                if ( str_contains( $query, '`league_id` = 100' ) ) {
                    // Realistic database state: league 100 already has group 2 taken by team 1
                    return array( (object) array( 'value' => '2' ) );
                }
                return array();
            } );

        $updated_groups = array();
        $wpdb_mock->method( 'query' )
            ->willReturnCallback( function ( string $query ) use ( &$updated_groups ) {
                $updated_groups[] = $query;
                return 1;
            } );

        $GLOBALS['wpdb'] = $wpdb_mock;

        $validation = new stdClass();
        $validation->success  = true;
        $validation->messages = array();

        $result = $this->service->handle_teams_in_different_divisions_with_same_match_day( array( 10, 20 ), '2026', 6, $validation, array( 1, 2, 3, 4, 5, 6 ) );

        self::assertTrue( $result->success );
        self::assertSame(
            array(
                'UPDATE wp_racketmanager_league_teams SET `group` = 2 WHERE `id` = 501',
                'UPDATE wp_racketmanager_league_teams SET `group` = 5 WHERE `id` = 502',
            ),
            $updated_groups
        );
    }

    public function test_handle_teams_in_different_divisions_with_same_match_day_resolves_ref_when_team2_has_group(): void {
        $wpdb_mock = $this->create_wpdb_mock();

        $event_team = (object) array( 'club_id' => 1, 'match_day' => 'Monday', 'match_time' => '19:00' );
        $team_div1  = (object) array( 'id' => 501, 'team_id' => 11, 'league_id' => 100, 'group' => '' );
        // Team 2 in div 2 already has group '5'
        $team_div2  = (object) array( 'id' => 502, 'team_id' => 12, 'league_id' => 200, 'group' => '5' );

        $wpdb_mock->method( 'get_results' )
            ->willReturnCallback( function ( string $query ) use ( $event_team, $team_div1, $team_div2 ) {
                if ( str_contains( $query, 'GROUP BY t.`club_id`, tbl.`match_day`' ) ) {
                    return array( $event_team );
                }
                if ( str_contains( $query, 'tbl.`match_time` =' ) ) {
                    return array( $team_div1, $team_div2 );
                }
                if ( str_contains( $query, '`league_id` = 200' ) ) {
                    return array( (object) array( 'value' => '5' ) );
                }
                return array();
            } );

        $updated_groups = array();
        $wpdb_mock->method( 'query' )
            ->willReturnCallback( function ( string $query ) use ( &$updated_groups ) {
                $updated_groups[] = $query;
                return 1;
            } );

        $GLOBALS['wpdb'] = $wpdb_mock;

        $validation = new stdClass();
        $validation->success  = true;
        $validation->messages = array();

        $result = $this->service->handle_teams_in_different_divisions_with_same_match_day( array( 10, 20 ), '2026', 6, $validation, array( 1, 2, 3, 4, 5, 6 ) );

        self::assertTrue( $result->success );
        self::assertSame(
            array(
                'UPDATE wp_racketmanager_league_teams SET `group` = 2 WHERE `id` = 501',
                'UPDATE wp_racketmanager_league_teams SET `group` = 5 WHERE `id` = 502',
            ),
            $updated_groups
        );
    }

    public function test_handle_teams_in_different_divisions_with_same_match_day_reports_error_when_alt_ref_clashes(): void {
        $wpdb_mock = $this->create_wpdb_mock();

        $event_team = (object) array( 'club_id' => 1, 'match_day' => 'Monday', 'match_time' => '19:00' );
        // Team 1 has group 2
        $team_div1  = (object) array( 'id' => 501, 'team_id' => 11, 'league_id' => 100, 'group' => '2' );
        $team_div2  = (object) array( 'id' => 502, 'team_id' => 12, 'league_id' => 200, 'group' => '' );

        // In division 2 (league 200), group 5 is already taken by another team!
        $group_taken = (object) array( 'value' => '5' );

        $wpdb_mock->method( 'get_results' )
            ->willReturnCallback( function ( string $query ) use ( $event_team, $team_div1, $team_div2, $group_taken ) {
                if ( str_contains( $query, 'GROUP BY t.`club_id`, tbl.`match_day`' ) ) {
                    return array( $event_team );
                }
                if ( str_contains( $query, 'tbl.`match_time` =' ) ) {
                    return array( $team_div1, $team_div2 );
                }
                if ( str_contains( $query, '`league_id` = 100' ) ) {
                    return array( (object) array( 'value' => '2' ) );
                }
                if ( str_contains( $query, '`league_id` = 200' ) ) {
                    return array( $group_taken );
                }
                return array();
            } );

        $GLOBALS['wpdb'] = $wpdb_mock;

        $team_obj        = new stdClass();
        $team_obj->title = 'Club A 1st';
        $GLOBALS['wp_stubs_teams'][11] = $team_obj;

        $league_obj        = new stdClass();
        $league_obj->title = 'Division 1';
        $GLOBALS['wp_stubs_leagues'][100] = $league_obj;

        $validation = new stdClass();
        $validation->success  = true;
        $validation->messages = array();

        $result = $this->service->handle_teams_in_different_divisions_with_same_match_day( array( 10, 20 ), '2026', 6, $validation, array( 1, 2, 3, 4, 5, 6 ) );

        self::assertFalse( $result->success );
        self::assertNotEmpty( $result->messages );
        self::assertStringContainsString( '1 - Error in scheduling Club A 1st in Division 1', $result->messages[0] );
    }

    public function test_handle_teams_in_different_divisions_with_same_match_day_handles_wrap_around_for_alt_ref(): void {
        $wpdb_mock = $this->create_wpdb_mock();

        $event_team = (object) array( 'club_id' => 1, 'match_day' => 'Monday', 'match_time' => '19:00' );
        // Team 1 has group 5. With 6 max teams, 5 + 3 = 8 > 6 => alt_ref = 2.
        $team_div1  = (object) array( 'id' => 501, 'team_id' => 11, 'league_id' => 100, 'group' => '5' );
        $team_div2  = (object) array( 'id' => 502, 'team_id' => 12, 'league_id' => 200, 'group' => '' );

        $wpdb_mock->method( 'get_results' )
            ->willReturnCallback( function ( string $query ) use ( $event_team, $team_div1, $team_div2 ) {
                if ( str_contains( $query, 'GROUP BY t.`club_id`, tbl.`match_day`' ) ) {
                    return array( $event_team );
                }
                if ( str_contains( $query, 'tbl.`match_time` =' ) ) {
                    return array( $team_div1, $team_div2 );
                }
                if ( str_contains( $query, '`league_id` = 100' ) ) {
                    return array( (object) array( 'value' => '5' ) );
                }
                return array();
            } );

        $updated_groups = array();
        $wpdb_mock->method( 'query' )
            ->willReturnCallback( function ( string $query ) use ( &$updated_groups ) {
                $updated_groups[] = $query;
                return 1;
            } );

        $GLOBALS['wpdb'] = $wpdb_mock;

        $validation = new stdClass();
        $validation->success  = true;
        $validation->messages = array();

        $result = $this->service->handle_teams_in_different_divisions_with_same_match_day( array( 10, 20 ), '2026', 6, $validation, array( 1, 2, 3, 4, 5, 6 ) );

        self::assertTrue( $result->success );
        self::assertSame(
            array(
                'UPDATE wp_racketmanager_league_teams SET `group` = 5 WHERE `id` = 501',
                'UPDATE wp_racketmanager_league_teams SET `group` = 2 WHERE `id` = 502',
            ),
            $updated_groups
        );
    }

    public function test_handle_teams_in_different_divisions_with_same_match_day_handles_wrap_around_for_ref(): void {
        $wpdb_mock = $this->create_wpdb_mock();

        $event_team = (object) array( 'club_id' => 1, 'match_day' => 'Monday', 'match_time' => '19:00' );
        $team_div1  = (object) array( 'id' => 501, 'team_id' => 11, 'league_id' => 100, 'group' => '' );
        // Team 2 has group 2. With 6 max teams, 2 - 3 = -1 < 1 => ref = 5.
        $team_div2  = (object) array( 'id' => 502, 'team_id' => 12, 'league_id' => 200, 'group' => '2' );

        $wpdb_mock->method( 'get_results' )
            ->willReturnCallback( function ( string $query ) use ( $event_team, $team_div1, $team_div2 ) {
                if ( str_contains( $query, 'GROUP BY t.`club_id`, tbl.`match_day`' ) ) {
                    return array( $event_team );
                }
                if ( str_contains( $query, 'tbl.`match_time` =' ) ) {
                    return array( $team_div1, $team_div2 );
                }
                if ( str_contains( $query, '`league_id` = 200' ) ) {
                    return array( (object) array( 'value' => '2' ) );
                }
                return array();
            } );

        $updated_groups = array();
        $wpdb_mock->method( 'query' )
            ->willReturnCallback( function ( string $query ) use ( &$updated_groups ) {
                $updated_groups[] = $query;
                return 1;
            } );

        $GLOBALS['wpdb'] = $wpdb_mock;

        $validation = new stdClass();
        $validation->success  = true;
        $validation->messages = array();

        $result = $this->service->handle_teams_in_different_divisions_with_same_match_day( array( 10, 20 ), '2026', 6, $validation, array( 1, 2, 3, 4, 5, 6 ) );

        self::assertTrue( $result->success );
        self::assertSame(
            array(
                'UPDATE wp_racketmanager_league_teams SET `group` = 5 WHERE `id` = 501',
                'UPDATE wp_racketmanager_league_teams SET `group` = 2 WHERE `id` = 502',
            ),
            $updated_groups
        );
    }

    public function test_handle_teams_in_different_divisions_with_same_match_day_fails_when_both_groups_assigned_do_not_alternate(): void {
        $wpdb_mock = $this->create_wpdb_mock();

        $event_team = (object) array( 'club_id' => 1, 'match_day' => 'Monday', 'match_time' => '19:00' );
        // Both teams have pre-assigned group '2' (non-alternating clash: team 2 should have '5' for 6 max teams)
        $team_div1  = (object) array( 'id' => 501, 'team_id' => 11, 'league_id' => 100, 'group' => '2' );
        $team_div2  = (object) array( 'id' => 502, 'team_id' => 12, 'league_id' => 200, 'group' => '2' );

        $wpdb_mock->method( 'get_results' )
            ->willReturnCallback( function ( string $query ) use ( $event_team, $team_div1, $team_div2 ) {
                if ( str_contains( $query, 'GROUP BY t.`club_id`, tbl.`match_day`' ) ) {
                    return array( $event_team );
                }
                if ( str_contains( $query, 'tbl.`match_time` =' ) ) {
                    return array( $team_div1, $team_div2 );
                }
                if ( str_contains( $query, '`league_id` = 100' ) ) {
                    return array( (object) array( 'value' => '2' ) );
                }
                if ( str_contains( $query, '`league_id` = 200' ) ) {
                    return array( (object) array( 'value' => '2' ) );
                }
                return array();
            } );

        $GLOBALS['wpdb'] = $wpdb_mock;

        $team_obj        = new stdClass();
        $team_obj->title = 'Club A 1st';
        $GLOBALS['wp_stubs_teams'][11] = $team_obj;

        $league_obj        = new stdClass();
        $league_obj->title = 'Division 1';
        $GLOBALS['wp_stubs_leagues'][100] = $league_obj;

        $validation = new stdClass();
        $validation->success  = true;
        $validation->messages = array();

        $result = $this->service->handle_teams_in_different_divisions_with_same_match_day( array( 10, 20 ), '2026', 6, $validation, array( 1, 2, 3, 4, 5, 6 ) );

        self::assertFalse( $result->success );
        self::assertNotEmpty( $result->messages );
        self::assertStringContainsString( '1 - Error in scheduling Club A 1st in Division 1', $result->messages[0] );
    }

    public function test_same_division_round_one_pair_with_groups_1_and_6_is_accepted_for_six_teams(): void {
        $this->assert_same_division_pair_groups_are_accepted( 1, 6, 6 );
    }

    public function test_same_division_round_one_pair_with_groups_3_and_4_is_accepted_for_six_teams(): void {
        $this->assert_same_division_pair_groups_are_accepted( 3, 4, 6 );
    }

    public function test_same_division_round_one_pair_with_groups_2_and_5_is_accepted_for_eight_teams(): void {
        $this->assert_same_division_pair_groups_are_accepted( 2, 5, 8 );
    }

    private function assert_same_division_pair_groups_are_accepted( int $group1, int $group2, int $max_teams ): void {
        $wpdb_mock = $this->create_wpdb_mock();

        $event_team = (object) array( 'club_id' => 1, 'match_day' => 'Monday', 'match_time' => '19:00' );
        $team_div1  = (object) array( 'id' => 501, 'team_id' => 11, 'league_id' => 100, 'group' => (string) $group1 );
        $team_div2  = (object) array( 'id' => 502, 'team_id' => 12, 'league_id' => 100, 'group' => (string) $group2 );
        $taken      = array(
            (object) array( 'value' => (string) $group1 ),
            (object) array( 'value' => (string) $group2 ),
        );

        $wpdb_mock->method( 'get_results' )
            ->willReturnCallback( function ( string $query ) use ( $event_team, $team_div1, $team_div2, $taken ) {
                if ( str_contains( $query, 'GROUP BY t.`club_id`, tbl.`match_day`' ) ) {
                    return array( $event_team );
                }
                if ( str_contains( $query, 'tbl.`match_time` =' ) ) {
                    return array( $team_div1, $team_div2 );
                }
                if ( str_contains( $query, '`group` as `value`' ) ) {
                    return $taken;
                }
                return array();
            } );

        $updated_groups = array();
        $wpdb_mock->method( 'query' )
            ->willReturnCallback( function ( string $query ) use ( &$updated_groups ) {
                $updated_groups[] = $query;
                return 1;
            } );

        $GLOBALS['wpdb'] = $wpdb_mock;

        $validation = new stdClass();
        $validation->success  = true;
        $validation->messages = array();

        $result = $this->service->handle_teams_in_different_divisions_with_same_match_day( array( 10 ), '2026', $max_teams, $validation, range( 1, $max_teams ) );

        self::assertTrue( $result->success );
        self::assertSame(
            array(
                'UPDATE wp_racketmanager_league_teams SET `group` = ' . $group1 . ' WHERE `id` = 501',
                'UPDATE wp_racketmanager_league_teams SET `group` = ' . $group2 . ' WHERE `id` = 502',
            ),
            $updated_groups
        );
    }

    public function test_handle_teams_in_different_divisions_with_same_match_day_succeeds_when_both_groups_assigned_alternate(): void {
        $wpdb_mock = $this->create_wpdb_mock();

        $event_team = (object) array( 'club_id' => 1, 'match_day' => 'Monday', 'match_time' => '19:00' );
        // Both teams have pre-assigned alternating groups: '2' and '5'
        $team_div1  = (object) array( 'id' => 501, 'team_id' => 11, 'league_id' => 100, 'group' => '2' );
        $team_div2  = (object) array( 'id' => 502, 'team_id' => 12, 'league_id' => 200, 'group' => '5' );

        $wpdb_mock->method( 'get_results' )
            ->willReturnCallback( function ( string $query ) use ( $event_team, $team_div1, $team_div2 ) {
                if ( str_contains( $query, 'GROUP BY t.`club_id`, tbl.`match_day`' ) ) {
                    return array( $event_team );
                }
                if ( str_contains( $query, 'tbl.`match_time` =' ) ) {
                    return array( $team_div1, $team_div2 );
                }
                if ( str_contains( $query, '`league_id` = 100' ) ) {
                    return array( (object) array( 'value' => '2' ) );
                }
                if ( str_contains( $query, '`league_id` = 200' ) ) {
                    return array( (object) array( 'value' => '5' ) );
                }
                return array();
            } );

        $updated_groups = array();
        $wpdb_mock->method( 'query' )
            ->willReturnCallback( function ( string $query ) use ( &$updated_groups ) {
                $updated_groups[] = $query;
                return 1;
            } );

        $GLOBALS['wpdb'] = $wpdb_mock;

        $validation = new stdClass();
        $validation->success  = true;
        $validation->messages = array();

        $result = $this->service->handle_teams_in_different_divisions_with_same_match_day( array( 10, 20 ), '2026', 6, $validation, array( 1, 2, 3, 4, 5, 6 ) );

        self::assertTrue( $result->success );
        self::assertSame(
            array(
                'UPDATE wp_racketmanager_league_teams SET `group` = 2 WHERE `id` = 501',
                'UPDATE wp_racketmanager_league_teams SET `group` = 5 WHERE `id` = 502',
            ),
            $updated_groups
        );
    }

    public function test_handle_teams_in_different_divisions_with_same_match_day_fails_when_no_refs(): void {
        $wpdb_mock = $this->create_wpdb_mock();

        $event_team = (object) array( 'club_id' => 1, 'match_day' => 'Monday', 'match_time' => '19:00' );
        $team_div1  = (object) array( 'id' => 501, 'team_id' => 11, 'league_id' => 100, 'group' => '' );
        $team_div2  = (object) array( 'id' => 502, 'team_id' => 12, 'league_id' => 200, 'group' => '' );

        // Table groups already take all refs
        $taken = array(
            (object) array( 'value' => '1' ),
            (object) array( 'value' => '2' ),
            (object) array( 'value' => '3' ),
            (object) array( 'value' => '4' ),
            (object) array( 'value' => '5' ),
            (object) array( 'value' => '6' ),
        );

        $wpdb_mock->method( 'get_results' )
            ->willReturnCallback( function ( string $query ) use ( $event_team, $team_div1, $team_div2, $taken ) {
                if ( str_contains( $query, 'GROUP BY t.`club_id`, tbl.`match_day`' ) ) {
                    return array( $event_team );
                }
                if ( str_contains( $query, 'tbl.`match_time` =' ) ) {
                    return array( $team_div1, $team_div2 );
                }
                return $taken;
            } );

        $GLOBALS['wpdb'] = $wpdb_mock;

        $team_obj        = new stdClass();
        $team_obj->title = 'Club A 1st';
        $GLOBALS['wp_stubs_teams'][11] = $team_obj;

        $league_obj        = new stdClass();
        $league_obj->title = 'Division 1';
        $GLOBALS['wp_stubs_leagues'][100] = $league_obj;

        $validation = new stdClass();
        $validation->success  = true;
        $validation->messages = array();

        $result = $this->service->handle_teams_in_different_divisions_with_same_match_day( array( 10, 20 ), '2026', 6, $validation, array( 1, 2, 3, 4, 5, 6 ) );

        self::assertFalse( $result->success );
        self::assertNotEmpty( $result->messages );
        self::assertStringContainsString( '3 - Error in scheduling Club A 1st in Division 1', $result->messages[0] );
    }

    public function test_schedule_league_matches_orchestrates_full_flow(): void {
        $event                 = $this->createMock( Event::class );
        $event->id             = 10;
        $event->name           = 'Premier Event';
        $event->current_season = array(
            'num_match_days' => 6,
            'match_dates'    => array( '2026-05-01', '2026-05-08', '2026-05-15', '2026-05-22', '2026-05-29', '2026-06-05' ),
            'home_away'      => true,
        );
        $event->method( 'get_season' )->willReturn( '2026' );

        $league = $this->createMock( League::class );
        $league->method( 'get_matches' )->willReturn( array() );
        $event->method( 'get_leagues' )->willReturn( array( $league ) );

        $GLOBALS['wp_stubs_events'][10] = $event;

        $racketmanager = $this->createMock( RacketManager::class );
        $racketmanager->method( 'get_matches' )->willReturn( 0 );
        $GLOBALS['racketmanager'] = $racketmanager;

        $wpdb_mock = $this->create_wpdb_mock();
        $wpdb_mock->method( 'get_var' )->willReturn( '2026' );
        $wpdb_mock->method( 'get_results' )->willReturn( array() );
        $wpdb_mock->method( 'query' )->willReturn( 1 );

        $GLOBALS['wpdb'] = $wpdb_mock;

        $result = $this->service->schedule_league_matches( array( 10 ) );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( 'Matches scheduled', $result->message );
    }

    public function test_schedule_league_matches_fails_when_setup_cannot_resolve_clashes(): void {
        $event                 = $this->createMock( Event::class );
        $event->id             = 10;
        $event->name           = 'Premier Event';
        $event->current_season = array(
            'num_match_days' => 6,
            'match_dates'    => array( '2026-05-01', '2026-05-08', '2026-05-15', '2026-05-22', '2026-05-29', '2026-06-05' ),
            'home_away'      => true,
        );
        $event->method( 'get_season' )->willReturn( '2026' );

        $league = $this->createMock( League::class );
        $league->method( 'get_matches' )->willReturn( array() );
        $event->method( 'get_leagues' )->willReturn( array( $league ) );

        $GLOBALS['wp_stubs_events'][10] = $event;

        $racketmanager = $this->createMock( RacketManager::class );
        $racketmanager->method( 'get_matches' )->willReturn( 0 );
        $GLOBALS['racketmanager'] = $racketmanager;

        $wpdb_mock = $this->create_wpdb_mock();
        $wpdb_mock->method( 'get_var' )->willReturn( '2026' );

        $event_team = (object) array( 'club_id' => 1, 'match_day' => 'Monday', 'match_time' => '19:00' );
        $team_div1  = (object) array( 'id' => 501, 'team_id' => 11, 'league_id' => 100, 'group' => '' );
        $team_div2  = (object) array( 'id' => 502, 'team_id' => 12, 'league_id' => 200, 'group' => '' );

        // Table groups already take all refs
        $taken = array(
            (object) array( 'value' => '1' ),
            (object) array( 'value' => '2' ),
            (object) array( 'value' => '3' ),
            (object) array( 'value' => '4' ),
        );

        $wpdb_mock->method( 'get_results' )
            ->willReturnCallback( function ( string $query ) use ( $event_team, $team_div1, $team_div2, $taken ) {
                if ( str_contains( $query, 'GROUP BY t.`club_id`, tbl.`match_day`' ) ) {
                    return array( $event_team );
                }
                if ( str_contains( $query, 'tbl.`match_time` =' ) ) {
                    return array( $team_div1, $team_div2 );
                }
                if ( str_contains( $query, '`group` as `value`' ) ) {
                    return $taken;
                }
                return array();
            } );

        $GLOBALS['wpdb'] = $wpdb_mock;

        $league_obj        = new stdClass();
        $league_obj->title = 'Division 1';
        $GLOBALS['wp_stubs_leagues'][100] = $league_obj;

        $team_obj        = new stdClass();
        $team_obj->title = 'Club A 1st';
        $GLOBALS['wp_stubs_teams'][11] = $team_obj;

        $result = $this->service->schedule_league_matches( array( 10 ) );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'Unable to generate schedule for selected events', $result->message );
    }
}
