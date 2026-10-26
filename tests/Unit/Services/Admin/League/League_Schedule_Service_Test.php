<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\League;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\Scheduling\Schedule_Result_DTO;
use Racketmanager\Domain\Competition\Scheduling\Scheduled_Match_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Schedule_Validation_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Services\Admin\League\League_Schedule_Service;
use Racketmanager\Services\Competition\Competition_Schedule_Service;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use ReflectionClass;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class League_Schedule_Service_Test extends TestCase {

    private Fixture_Maintenance_Service&MockObject $fixture_maintenance_service;
    private Competition_Schedule_Service&MockObject $competition_schedule_service;
    private Event_Repository_Interface&MockObject $event_repository;
    private League_Repository_Interface&MockObject $league_repository;
    private League_Team_Repository_Interface&MockObject $league_team_repository;
    private Event_Query_Service&MockObject $event_query_service;
    private League_Query_Service&MockObject $league_query_service;
    private League_Schedule_Service $service;
    /** @var array<int, Event> */
    private array $events = array();

    protected function setUp(): void {
        parent::setUp();
        $this->fixture_maintenance_service  = $this->createMock( Fixture_Maintenance_Service::class );
        $this->competition_schedule_service = $this->createMock( Competition_Schedule_Service::class );
        $this->event_repository             = $this->createMock( Event_Repository_Interface::class );
        $this->league_repository            = $this->createMock( League_Repository_Interface::class );
        $this->league_team_repository       = $this->createMock( League_Team_Repository_Interface::class );
        $this->event_query_service          = $this->createMock( Event_Query_Service::class );
        $this->league_query_service         = $this->createMock( League_Query_Service::class );
        $this->events                       = array();
        $this->event_repository->method( 'find_by_id' )->willReturnCallback( fn( $id ) => $this->events[ (int) $id ] ?? null );
        $this->league_team_repository->method( 'find_teams_missing_match_day' )->willReturn( array() );

        $this->service = new League_Schedule_Service(
            $this->fixture_maintenance_service,
            $this->competition_schedule_service,
            $this->event_repository,
            $this->league_repository,
            $this->league_team_repository,
            $this->event_query_service,
            $this->league_query_service
        );
    }

    /**
     * @param array<string, array<string, mixed>> $extra_seasons additional seasons keyed by name.
     */
    private function create_event(
        int $id = 10,
        string $name = 'Event 1',
        int $num_match_days = 6,
        ?array $match_dates = array( '2026-05-01', '2026-05-08' ),
        bool $home_away = true,
        string $season = '2026',
        array $extra_seasons = array()
    ): Event {
        $event       = ( new ReflectionClass( Event::class ) )->newInstanceWithoutConstructor();
        $event->id   = $id;
        $event->name = $name;
        $season_data = array(
            'name'           => $season,
            'num_match_days' => $num_match_days,
            'home_away'      => $home_away,
        );
        if ( null !== $match_dates ) {
            $season_data['match_dates'] = $match_dates;
        }
        $event->current_season = $season_data;
        $event->seasons        = array( $season => $season_data ) + $extra_seasons;
        $this->events[ $id ]   = $event;
        return $event;
    }

    private function create_league( int $id, string $title ): League {
        $league        = ( new ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
        $league->id    = $id;
        $league->title = $title;
        return $league;
    }

    private function valid_result( int $retry = 3, array $alerts = array(), array $pairings = array() ): Schedule_Result_DTO {
        $match = new Scheduled_Match_DTO( is_bye: false, date: '2026-05-01', home_id: 1, away_id: 2, home: 'A', away: 'B', time: '19:00', location: 'LWC', resource: 'r' );
        $bye   = new Scheduled_Match_DTO( is_bye: true, date: '2026-05-01', bye_team_id: 3, bye_team: 'C' );
        return new Schedule_Result_DTO(
            retry: $retry,
            is_valid: true,
            total_weeks: 2,
            schedule: array(
                100 => array(
                    'league_id'   => 100,
                    'league_name' => 'Division 1',
                    'weeks'       => array(
                        1 => array( $match, $bye ),
                        2 => array( $match ),
                    ),
                ),
            ),
            alerts: $alerts,
            resource_pairings: $pairings,
        );
    }

    /* ---------------------------------------------------------------- delete */

    public function test_delete_event_matches_success_deletes_each_fixture(): void {
        $event          = $this->create_event( 10 );
        $league         = $this->create_league( 100, 'Division 1' );
        $event->leagues = array( $league );

        $this->event_repository->expects( self::once() )
            ->method( 'count_by_event_season' )
            ->with( 10, '2026', true )
            ->willReturn( 0 );
        $this->league_query_service->expects( self::once() )
            ->method( 'get_matches' )
            ->with( $league, array( 'season' => '2026' ) )
            ->willReturn( array( (object) array( 'id' => 101 ), (object) array( 'id' => 102 ) ) );

        $deleted = array();
        $this->fixture_maintenance_service->expects( self::exactly( 2 ) )
            ->method( 'delete_fixture' )
            ->willReturnCallback( static function ( int $fixture_id ) use ( &$deleted ): bool {
                $deleted[] = $fixture_id;
                return true;
            } );

        $result = $this->service->delete_event_matches( array( 10 ) );

        self::assertSame( array( 101, 102 ), $deleted );
        self::assertSame( 'Matches deleted', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_delete_event_matches_loads_leagues_via_query_service_and_resolves_ids(): void {
        $this->create_event( 10 );
        $league = $this->create_league( 100, 'Division 1' );

        $this->event_repository->method( 'count_by_event_season' )->willReturn( 0 );
        $this->event_query_service->expects( self::once() )->method( 'get_leagues' )->willReturn( array( 100 ) );
        $this->league_repository->expects( self::once() )->method( 'find_by_id' )->with( 100 )->willReturn( $league );
        $this->league_query_service->method( 'get_matches' )->willReturn( array( (object) array( 'id' => 7 ) ) );
        $this->fixture_maintenance_service->expects( self::once() )->method( 'delete_fixture' )->with( 7 );

        $result = $this->service->delete_event_matches( array( 10 ) );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_delete_event_matches_returns_error_when_event_has_completed_matches(): void {
        $this->create_event( 10 );
        $this->event_repository->method( 'count_by_event_season' )->willReturn( 3 );
        $this->fixture_maintenance_service->expects( self::never() )->method( 'delete_fixture' );

        $result = $this->service->delete_event_matches( array( 10 ) );

        self::assertSame( 'Event has completed matches', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_delete_event_matches_returns_warning_when_some_events_have_completed_matches(): void {
        $this->create_event( 10 );
        $event2          = $this->create_event( 20, 'Event 2' );
        $event2->leagues = array( $this->create_league( 100, 'Division 1' ) );

        $this->event_repository->method( 'count_by_event_season' )
            ->willReturnCallback( static fn( int $event_id ): int => 10 === $event_id ? 2 : 0 );
        $this->league_query_service->method( 'get_matches' )->willReturn( array( (object) array( 'id' => 201 ) ) );
        $this->fixture_maintenance_service->expects( self::once() )->method( 'delete_fixture' )->with( 201 );

        $result = $this->service->delete_event_matches( array( 10, 20 ) );

        self::assertSame( 'Some matches deleted, but some events have completed matches', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }

    public function test_delete_event_matches_uses_posted_non_current_season(): void {
        $event          = $this->create_event( 10, 'Event 1', 6, array( '2026-05-01' ), true, '2026', array( '2025' => array( 'name' => '2025', 'num_match_days' => 6 ) ) );
        $league         = $this->create_league( 100, 'Division 1' );
        $event->leagues = array( $league );

        $this->event_repository->expects( self::once() )
            ->method( 'count_by_event_season' )
            ->with( 10, '2025', true )
            ->willReturn( 0 );
        $this->league_query_service->expects( self::once() )
            ->method( 'get_matches' )
            ->with( $league, array( 'season' => '2025' ) )
            ->willReturn( array( (object) array( 'id' => 301 ) ) );
        $this->fixture_maintenance_service->expects( self::once() )->method( 'delete_fixture' )->with( 301 );

        $result = $this->service->delete_event_matches( array( 10 ), ' 2025 ' );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_delete_event_matches_preflights_all_events_before_deleting_anything(): void {
        $event1          = $this->create_event( 10, 'Event 1', 6, array( '2026-05-01', '2026-05-08' ), true, '2026', array( '2025' => array( 'name' => '2025', 'num_match_days' => 6, 'home_away' => true, 'match_dates' => array( '2025-05-01', '2025-05-08' ) ) ) );
        $event1->leagues = array( $this->create_league( 100, 'Division 1' ) );
        $event2          = $this->create_event( 20, 'Event 2' );
        $event2->leagues = array( $this->create_league( 200, 'Division 2' ) );

        $this->event_repository->expects( self::never() )->method( 'count_by_event_season' );
        $this->league_query_service->expects( self::never() )->method( 'get_matches' );
        $this->fixture_maintenance_service->expects( self::never() )->method( 'delete_fixture' );

        $result = $this->service->delete_event_matches( array( 10, 20 ), '2025' );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'Season 2025 is not available for Event 2', $result->message );
    }

    public function test_delete_event_matches_rejects_posted_season_the_event_does_not_have(): void {
        $event          = $this->create_event( 10, 'Event 1' );
        $event->leagues = array( $this->create_league( 100, 'Division 1' ) );

        $this->event_repository->expects( self::never() )->method( 'count_by_event_season' );
        $this->league_query_service->expects( self::never() )->method( 'get_matches' );
        $this->fixture_maintenance_service->expects( self::never() )->method( 'delete_fixture' );

        $result = $this->service->delete_event_matches( array( 10 ), '1999' );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'Season 1999 is not available for Event 1', $result->message );
    }

    /* ------------------------------------------------------------ validation */

    public function test_validate_events_fails_with_existing_matches(): void {
        $this->create_event( 10, 'Summer League' );
        $this->event_repository->expects( self::once() )->method( 'count_by_event_season' )->with( 10, '2026' )->willReturn( 5 );
        $this->competition_schedule_service->expects( self::never() )->method( 'schedule_events' );

        $result = $this->service->schedule_league_matches( array( 10 ), '2026' );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertStringContainsString( 'Summer League already has matches scheduled for 2026', $result->message );
    }

    public function test_validate_events_fails_with_differing_match_days(): void {
        $this->create_event( 10, 'Event 1', 5 );
        $this->create_event( 20, 'Event 2', 6 );
        $this->event_repository->method( 'count_by_event_season' )->willReturn( 0 );

        $validation = $this->service->validate_events( array( 10, 20 ), '2026' );

        self::assertFalse( $validation->success );
        self::assertStringContainsString( 'Events have different number of match days', $validation->message );
    }

    public function test_validate_events_fails_with_differing_match_dates(): void {
        $this->create_event( 10, 'Event 1', 6, array( '2026-05-01', '2026-05-08' ) );
        $this->create_event( 20, 'Event 2', 6, array( '2026-05-02', '2026-05-09' ) );
        $this->event_repository->method( 'count_by_event_season' )->willReturn( 0 );

        $validation = $this->service->validate_events( array( 10, 20 ), '2026' );

        self::assertFalse( $validation->success );
        self::assertStringContainsString( 'Events have different match dates', $validation->message );
    }

    public function test_validate_events_fails_with_differing_home_away_setting(): void {
        $this->create_event( 10, 'Event 1', 6, array( '2026-05-01', '2026-05-08' ), true );
        $this->create_event( 20, 'Event 2', 6, array( '2026-05-01', '2026-05-08' ), false );
        $this->event_repository->method( 'count_by_event_season' )->willReturn( 0 );

        $validation = $this->service->validate_events( array( 10, 20 ), '2026' );

        self::assertFalse( $validation->success );
        self::assertStringContainsString( 'Events have different home / away setting', $validation->message );
    }

    public function test_validate_events_fails_with_missing_match_dates_on_first_event(): void {
        $this->create_event( 10, 'Premier Event', 6, null );
        $this->event_repository->method( 'count_by_event_season' )->willReturn( 0 );

        $validation = $this->service->validate_events( array( 10 ), '2026' );

        self::assertFalse( $validation->success );
        self::assertStringContainsString( 'Events match dates not set for Premier Event', $validation->message );
    }

    public function test_validate_events_fails_when_teams_have_missing_match_days(): void {
        $this->create_event( 10 );
        $this->event_repository->method( 'count_by_event_season' )->willReturn( 0 );

        $league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
        $league_team_repository->expects( self::once() )
            ->method( 'find_teams_missing_match_day' )
            ->with( array( 10 ), '2026' )
            ->willReturn( array( 'Club A 1st', 'Club B 2nd' ) );
        $service = new League_Schedule_Service(
            $this->fixture_maintenance_service,
            $this->competition_schedule_service,
            $this->event_repository,
            $this->league_repository,
            $league_team_repository,
            $this->event_query_service,
            $this->league_query_service
        );

        $validation = $service->validate_events( array( 10 ), '2026' );

        self::assertFalse( $validation->success );
        self::assertStringContainsString( 'Missing match days for Club A 1st and Club B 2nd', $validation->message );
    }

    public function test_validate_events_success_with_single_event(): void {
        $this->create_event( 10, 'Event 1', 10 );
        $this->event_repository->method( 'count_by_event_season' )->willReturn( 0 );

        $validation = $this->service->validate_events( array( 10 ), '2026' );

        self::assertTrue( $validation->success );
        self::assertSame( '2026', $validation->season );
        self::assertSame( 5, $validation->num_rounds );
        self::assertTrue( $validation->home_away );
        self::assertSame( array( '2026-05-01', '2026-05-08' ), $validation->match_dates );
    }

    public function test_validate_events_falls_back_to_current_season_when_none_posted(): void {
        $this->create_event( 10, 'Event 1', 4, array( '2026-05-01' ), false, '2026' );
        $this->event_repository->expects( self::once() )
            ->method( 'count_by_event_season' )
            ->with( 10, '2026' )
            ->willReturn( 0 );

        $validation = $this->service->validate_events( array( 10 ), null );

        self::assertTrue( $validation->success );
        self::assertSame( '2026', $validation->season );
        self::assertFalse( $validation->home_away );
        self::assertSame( 4, $validation->num_rounds );
    }

    public function test_validate_events_uses_settings_of_requested_non_current_season(): void {
        $this->create_event(
            10,
            'Event 1',
            6,
            array( '2026-05-01' ),
            true,
            '2026',
            array(
                '2025' => array(
                    'name'           => '2025',
                    'num_match_days' => 8,
                    'match_dates'    => array( '2025-05-01' ),
                    'home_away'      => false,
                ),
            )
        );
        $this->event_repository->expects( self::once() )
            ->method( 'count_by_event_season' )
            ->with( 10, '2025' )
            ->willReturn( 0 );

        $validation = $this->service->validate_events( array( 10 ), '2025' );

        self::assertTrue( $validation->success );
        self::assertSame( '2025', $validation->season );
        self::assertSame( 8, $validation->num_rounds );
        self::assertFalse( $validation->home_away );
        self::assertSame( array( '2025-05-01' ), $validation->match_dates );
    }

    public function test_validate_events_rejects_season_not_configured_for_event(): void {
        $this->create_event( 10, 'Event 1' );
        $this->event_repository->expects( self::never() )->method( 'count_by_event_season' );
        $this->competition_schedule_service->expects( self::never() )->method( 'schedule_events' );

        $result = $this->service->schedule_league_matches( array( 10 ), '1999' );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'Season 1999 is not available for Event 1', $result->message );
    }

    /* --------------------------------------------------------- orchestration */

    public function test_schedule_league_matches_success_reports_attempts_and_flash_report(): void {
        $this->create_event( 10, 'Premier Event', 6, array( '2026-05-01', '2026-05-08', '2026-05-15', '2026-05-22', '2026-05-29', '2026-06-05' ) );
        $this->event_repository->method( 'count_by_event_season' )->willReturn( 0 );

        $alerts   = array( array( 'type' => 'equity_compromise', 'team_name' => 'A', 'division' => 'Division 1', 'count' => 4, 'ideal' => 3 ) );
        $pairings = array( '1_Monday 19:00_C0' => array( array( 'id' => 1, 'name' => 'A', 'division' => 'Division 1' ) ) );

        $this->competition_schedule_service->expects( self::once() )
            ->method( 'schedule_events' )
            ->with( array( 10 ), '2026', 4, true, array( '2026-05-01', '2026-05-08', '2026-05-15', '2026-05-22', '2026-05-29', '2026-06-05' ) )
            ->willReturn( $this->valid_result( 7, $alerts, $pairings ) );

        $result = $this->service->schedule_league_matches( array( 10 ), '2026' );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( 'Matches scheduled after 7 attempts', $result->message );
        self::assertSame(
            array(
                'retry'             => 7,
                'conflicts'         => array(),
                'alerts'            => $alerts,
                'resource_pairings' => $pairings,
                'scheduled'         => 2,
            ),
            $result->flash_data
        );
    }

    public function test_schedule_league_matches_uses_num_rounds_plus_one_without_home_away(): void {
        $this->create_event( 10, 'Event 1', 5, array( '2026-05-01' ), false );
        $this->event_repository->method( 'count_by_event_season' )->willReturn( 0 );

        $this->competition_schedule_service->expects( self::once() )
            ->method( 'schedule_events' )
            ->with( array( 10 ), '2026', 6, false, array( '2026-05-01' ) )
            ->willReturn( new Schedule_Result_DTO( retry: 1, is_valid: true, total_weeks: 5 ) );

        $result = $this->service->schedule_league_matches( array( 10 ), '2026' );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( 0, $result->flash_data['scheduled'] );
    }

    public function test_schedule_league_matches_returns_error_with_conflicts_when_schedule_invalid(): void {
        $this->create_event( 10 );
        $this->event_repository->method( 'count_by_event_season' )->willReturn( 0 );

        $conflicts = array( array( 'week' => 2, 'division' => 'Division 1', 'court' => '1_Monday 19:00_C0', 'home' => 'A', 'with' => 'B' ) );
        $this->competition_schedule_service->expects( self::once() )
            ->method( 'schedule_events' )
            ->willReturn( new Schedule_Result_DTO( retry: 375550, is_valid: false, total_weeks: 10, conflicts: $conflicts ) );

        $result = $this->service->schedule_league_matches( array( 10 ), '2026' );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'Scheduling failed after 375550 attempts', $result->message );
        self::assertSame( 375550, $result->flash_data['retry'] );
        self::assertSame( $conflicts, $result->flash_data['conflicts'] );
        self::assertSame( 0, $result->flash_data['scheduled'] );
    }

    public function test_schedule_league_matches_maps_schedule_validation_exception_to_error(): void {
        $this->create_event( 10 );
        $this->event_repository->method( 'count_by_event_season' )->willReturn( 0 );

        $this->competition_schedule_service->method( 'schedule_events' )
            ->willThrowException( new Schedule_Validation_Exception( array( 'Division 1 has 7 teams but the schedule only allows 6', 'League 5 not found' ) ) );

        $result = $this->service->schedule_league_matches( array( 10 ), '2026' );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'Division 1 has 7 teams but the schedule only allows 6<br>League 5 not found', $result->message );
        self::assertSame( array(), $result->flash_data );
    }

    public function test_schedule_league_matches_validation_failure_does_not_call_schedule_service(): void {
        $this->create_event( 10, 'Event 1', 5 );
        $this->create_event( 20, 'Event 2', 6 );
        $this->event_repository->method( 'count_by_event_season' )->willReturn( 0 );
        $this->competition_schedule_service->expects( self::never() )->method( 'schedule_events' );

        $result = $this->service->schedule_league_matches( array( 10, 20 ), '2026' );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertStringContainsString( 'Events have different number of match days', $result->message );
    }
}
