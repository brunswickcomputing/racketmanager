<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Ajax;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Exceptions\Clubs_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Ajax\Admin_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Admin_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Ajax\Ajax_Registry;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Notification\Notification_Service;

class Admin_Ajax_Adapter_Test extends TestCase {
    private Stub|Security_Service_Interface $security_service;
    private RacketManager|Stub $racketmanager;
    private Competition_Service|MockObject|Stub $competition_service;
    private Competition_Entry_Service|MockObject|Stub $competition_entry_service;
    private Notification_Service|MockObject|Stub $notification_service;
    private Fixture_Repository_Interface|MockObject|Stub $fixture_repository;
    private Admin_Ajax_Adapter $adapter;

    protected function setUp(): void {
        parent::setUp();
        $this->security_service          = $this->createStub( Security_Service_Interface::class );
        $this->racketmanager             = $this->createStub( RacketManager::class );
        $this->competition_service       = $this->createStub( Competition_Service::class );
        $this->competition_entry_service = $this->createStub( Competition_Entry_Service::class );
        $this->notification_service      = $this->createStub( Notification_Service::class );
        $this->fixture_repository        = $this->createStub( Fixture_Repository_Interface::class );
        $this->adapter                   = $this->make_adapter();
        $_POST                           = [];
        $_GET                            = [];
        $_REQUEST                        = [];
    }

    protected function tearDown(): void {
        parent::tearDown();
        unset(
            $GLOBALS['wp_stubs_events'],
            $GLOBALS['wp_stubs_leagues'],
            $GLOBALS['wp_stubs_teams'],
            $GLOBALS['wp_stubs_clubs'],
            $GLOBALS['wp_stubs_league_teams'],
            $GLOBALS['wp_mail_calls']
        );
    }

    private function make_adapter(): Admin_Ajax_Adapter {
        $response_factory = $this->createStub( Json_Response_Factory_Interface::class );
        $response_factory->method( 'create_success_response' )->willReturnCallback(
            static fn( $data = null, $status = null ) => new Response( $data, $status ?? 200 )
        );
        $response_factory->method( 'create_error_response' )->willReturnCallback(
            static fn( $data = null, $status = null ) => new Response( $data, $status ?? 400 )
        );
        $response_factory->method( 'create_raw_response' )->willReturnCallback(
            static fn( $content, $status = null ) => new Response( $content, $status ?? 200 )
        );

        return new Admin_Ajax_Adapter(
            $this->security_service,
            $response_factory,
            $this->racketmanager,
            $this->competition_service,
            $this->competition_entry_service,
            $this->notification_service,
            $this->fixture_repository
        );
    }

    private function allow_security( bool $verify_nonce = true, bool $user_can = true ): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( $verify_nonce );
        $this->security_service->method( 'current_user_can' )->willReturn( $user_can );
    }

    private function create_competition_mock( string $entry_type = 'team', string $season_name = '2026' ): Competition {
        $competition           = $this->createMock( Competition::class );
        $competition->settings = new \Racketmanager\Domain\Competition\Competition_Settings( [ 'entry_type' => $entry_type ] );
        $competition->expects( $this->once() )
            ->method( 'get_season_by_name' )
            ->with( $season_name )
            ->willReturn( [ 'name' => $season_name ] );
        return $competition;
    }

    private function setup_tournament_entry_service_notification( int $tournament_id, int|\Throwable $result ): void {
        $entry_service = $this->createMock( Competition_Entry_Service::class );
        $expectation   = $entry_service->expects( $this->once() )
            ->method( 'notify_tournament_entry_open' )
            ->with( $tournament_id );

        if ( $result instanceof \Throwable ) {
            $expectation->willThrowException( $result );
        } else {
            $expectation->willReturn( $result );
        }

        $this->competition_entry_service = $entry_service;
        $this->adapter                   = $this->make_adapter();
    }

    private function setup_fixture_notification( int $match_id, bool $notification_result ): void {
        $match = $this->createStub( Fixture::class );
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( $match_id )
            ->willReturn( $match );

        $notif_service = $this->createMock( Notification_Service::class );
        $notif_service->expects( $this->once() )
            ->method( 'send_next_fixture_notification' )
            ->with( $match )
            ->willReturn( $notification_result );

        $this->fixture_repository   = $fixture_repo;
        $this->notification_service = $notif_service;
        $this->adapter              = $this->make_adapter();
    }

    public function test_register_hooks_routes_all_admin_actions(): void {
        $controller = new Admin_Ajax_Controller( $this->createStub( RacketManager::class ) );
        $registry   = new Ajax_Registry();
        $GLOBALS['wp_action_hooks'] = [];
        $controller->register_hooks( $registry );

        $this->assertSame( [ $controller, 'save_add_points' ], $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_save_add_points'][0] );
        $this->assertSame( [ $controller, 'insert_home_stadium' ], $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_insert_home_stadium'][0] );
        $this->assertSame( [ $controller, 'get_event_dropdown' ], $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_get_event_dropdown'][0] );
        $this->assertSame( [ $controller, 'get_league_dropdown' ], $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_get_league_dropdown'][0] );
        $this->assertSame( [ $controller, 'set_season_dropdown' ], $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_get_season_dropdown'][0] );
        $this->assertSame( [ $controller, 'set_match_dropdown' ], $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_get_match_dropdown'][0] );
        $this->assertSame( [ $controller, 'check_team_exists' ], $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_check_team_exists'][0] );
        $this->assertSame( [ $controller, 'email_constitution' ], $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_email_constitution'][0] );
        $this->assertSame( [ $controller, 'notify_competition_entries_open' ], $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_notify_competition_entries_open'][0] );
        $this->assertSame( [ $controller, 'notify_tournament_entries_open' ], $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_notify_tournament_entries_open'][0] );
        $this->assertSame( [ $controller, 'notify_teams_fixture' ], $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_notify_teams'][0] );
        $this->assertSame( [ $controller, 'set_tournament_dates' ], $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_set_tournament_dates'][0] );
        $this->assertSame( [ $controller, 'send_fixtures' ], $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_send_fixtures'][0] );
    }

    public function test_save_add_points_success(): void {
        $this->allow_security();

        $league_team = new class {
            public int $points = 0;
            public function amend_points( int $pts ): void {
                $this->points = $pts;
            }
        };
        $GLOBALS['wp_stubs_league_teams'] = [ 5 => $league_team ];

        $response = $this->adapter->save_add_points( [
            'security' => 'valid',
            'table_id' => 5,
            'points'   => 3,
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 3, $league_team->points );
        unset( $GLOBALS['wp_stubs_league_teams'] );
    }

    public function test_save_add_points_empty_table_id_is_noop_success(): void {
        $this->allow_security();

        $response = $this->adapter->save_add_points( [
            'security' => 'valid',
            'table_id' => 0,
            'points'   => 5,
        ] );
        $this->assertSame( 200, $response->get_status_code() );
    }

    public function test_save_add_points_unauthorized_returns_403(): void {
        $this->allow_security( true, false );

        $response = $this->adapter->save_add_points( [
            'security' => 'valid',
            'table_id' => 5,
        ] );
        $this->assertSame( 403, $response->get_status_code() );
    }

    public function test_save_add_points_nonce_failure_returns_403(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( false );

        $response = $this->adapter->save_add_points( [
            'security' => 'invalid',
            'table_id' => 5,
        ] );
        $this->assertSame( 403, $response->get_status_code() );
    }

    public function test_save_add_points_entry_not_found_returns_500(): void {
        $this->allow_security();
        $GLOBALS['wp_stubs_league_teams'] = [];

        $response = $this->adapter->save_add_points( [
            'security' => 'valid',
            'table_id' => 999,
        ] );
        $this->assertSame( 500, $response->get_status_code() );
        unset( $GLOBALS['wp_stubs_league_teams'] );
    }

    public function test_get_event_dropdown_success(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );

        $response = $this->adapter->get_event_dropdown( [
            'security'       => 'valid',
            'competition_id' => 1,
        ] );
        $this->assertSame( 200, $response->get_status_code() );
    }

    public function test_get_event_dropdown_nonce_failure_returns_403(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( false );

        $response = $this->adapter->get_event_dropdown( [
            'security' => 'invalid',
        ] );
        $this->assertSame( 403, $response->get_status_code() );
    }

    public function test_get_league_dropdown_success(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );

        $response = $this->adapter->get_league_dropdown( [
            'security' => 'valid',
            'event_id' => 2,
        ] );
        $this->assertSame( 200, $response->get_status_code() );
    }

    public function test_set_season_dropdown_success(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );

        $response = $this->adapter->set_season_dropdown( [
            'security'  => 'valid',
            'league_id' => 3,
        ] );
        $this->assertSame( 200, $response->get_status_code() );
    }

    public function test_set_match_dropdown_success(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );

        $response = $this->adapter->set_match_dropdown( [
            'security'  => 'valid',
            'league_id' => 3,
            'season'    => '2026',
        ] );
        $this->assertSame( 200, $response->get_status_code() );
    }

    public function test_save_add_points_league_entry_not_found_returns_500(): void {
        $this->allow_security();
        $GLOBALS['wp_stubs_league_teams'] = [];

        $response = $this->adapter->save_add_points( [
            'security' => 'valid',
            'table_id' => 999,
            'points'   => 3,
        ] );
        $this->assertSame( 500, $response->get_status_code() );
        $this->assertSame( 'League entry not found', $response->get_content() );
    }

    public function test_insert_home_stadium_success(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );

        $team = new class {
            public string $stadium = 'Court 1';
        };
        $GLOBALS['wp_stubs_teams'] = [ 12 => $team ];

        $response = $this->adapter->insert_home_stadium( [
            'security' => 'valid',
            'team_id'  => 12,
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'Court 1', $response->get_content() );
        unset( $GLOBALS['wp_stubs_teams'] );
    }

    public function test_insert_home_stadium_team_not_found_returns_null(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $GLOBALS['wp_stubs_teams'] = [];

        $response = $this->adapter->insert_home_stadium( [
            'security' => 'valid',
            'team_id'  => 999,
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertNull( $response->get_content() );
    }

    public function test_email_constitution_unauthorized_returns_403(): void {
        $this->allow_security( true, false );

        $response = $this->adapter->email_constitution( [
            'security' => 'valid',
            'eventId'  => 1,
        ] );
        $this->assertSame( 403, $response->get_status_code() );
    }

    public function test_email_constitution_missing_event_id_returns_500(): void {
        $this->allow_security();

        $response = $this->adapter->email_constitution( [
            'security' => 'valid',
        ] );
        $this->assertSame( 500, $response->get_status_code() );
        $this->assertSame( 'Event not specified', $response->get_content() );
    }

    public function test_email_constitution_event_not_found_returns_500(): void {
        $this->allow_security();
        $GLOBALS['wp_stubs_events'] = [ 999 => null ];

        $response = $this->adapter->email_constitution( [
            'security' => 'valid',
            'eventId'  => 999,
        ] );
        $this->assertSame( 500, $response->get_status_code() );
        $this->assertSame( 'Event not found', $response->get_content() );
    }

    public function test_email_constitution_success(): void {
        $this->allow_security();

        $constitution_sent_season = null;
        $event = $this->createStub( Event::class );
        $event->current_season = [ 'name' => '2026' ];
        $event->method( 'send_constitution' )->willReturnCallback(
            function ( array $season ) use ( &$constitution_sent_season ) {
                $constitution_sent_season = $season['name'] ?? null;
            }
        );
        $GLOBALS['wp_stubs_events'] = [ 101 => $event ];

        $response = $this->adapter->email_constitution( [
            'security' => 'valid',
            'eventId'  => 101,
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'Constitution emailed', $response->get_content() );
        $this->assertSame( '2026', $constitution_sent_season );
    }

    public function test_notify_competition_entries_open_unauthorized_returns_403(): void {
        $this->allow_security( true, false );

        $response = $this->adapter->notify_competition_entries_open( [
            'security'      => 'valid',
            'competitionId' => 1,
        ] );
        $this->assertSame( 403, $response->get_status_code() );
    }

    public function test_notify_competition_entries_open_competition_not_found_returns_404(): void {
        $this->allow_security();

        $comp_service = $this->createMock( Competition_Service::class );
        $comp_service->expects( $this->once() )
            ->method( 'get_by_id' )
            ->with( 404 )
            ->willThrowException( new Competition_Not_Found_Exception( 'Competition not found' ) );

        $this->competition_service = $comp_service;
        $this->adapter             = $this->make_adapter();

        $response = $this->adapter->notify_competition_entries_open( [
            'security'      => 'valid',
            'competitionId' => 404,
            'season'        => 2026,
        ] );
        $this->assertSame( 404, $response->get_status_code() );
        $this->assertSame( 'Competition not found', $response->get_content() );
    }

    public function test_notify_competition_entries_open_season_not_found_returns_500(): void {
        $this->allow_security();

        $competition = $this->createMock( Competition::class );
        $competition->expects( $this->once() )
            ->method( 'get_season_by_name' )
            ->with( '2026' )
            ->willReturn( [] );

        $comp_service = $this->createMock( Competition_Service::class );
        $comp_service->expects( $this->once() )
            ->method( 'get_by_id' )
            ->with( 10 )
            ->willReturn( $competition );

        $this->competition_service = $comp_service;
        $this->adapter             = $this->make_adapter();

        $response = $this->adapter->notify_competition_entries_open( [
            'security'      => 'valid',
            'competitionId' => 10,
            'season'        => 2026,
        ] );
        $this->assertSame( 500, $response->get_status_code() );
        $this->assertSame( 'Season not found for competition', $response->get_content() );
    }

    public function test_notify_competition_entries_open_invalid_entry_type_returns_500(): void {
        $this->allow_security();

        $competition  = $this->create_competition_mock( 'individual' );
        $comp_service = $this->createMock( Competition_Service::class );
        $comp_service->expects( $this->once() )
            ->method( 'get_by_id' )
            ->with( 10 )
            ->willReturn( $competition );

        $this->competition_service = $comp_service;
        $this->adapter             = $this->make_adapter();

        $response = $this->adapter->notify_competition_entries_open( [
            'security'      => 'valid',
            'competitionId' => 10,
            'season'        => 2026,
        ] );
        $this->assertSame( 500, $response->get_status_code() );
        $this->assertSame( 'Invalid competition entry type', $response->get_content() );
    }

    public function test_notify_competition_entries_open_with_existing_entries_triggers_reminder(): void {
        $this->allow_security();

        $competition  = $this->create_competition_mock( 'team' );
        $comp_service = $this->createMock( Competition_Service::class );
        $comp_service->expects( $this->once() )
            ->method( 'get_by_id' )
            ->with( 10 )
            ->willReturn( $competition );
        $comp_service->expects( $this->once() )
            ->method( 'get_clubs_for_competition' )
            ->with( 10, 2026 )
            ->willReturn( [ 1, 2 ] );

        $entry_service = $this->createMock( Competition_Entry_Service::class );
        $entry_service->expects( $this->once() )
            ->method( 'notify_team_entry_reminder' )
            ->with( 10, 2026 )
            ->willReturn( (object) [ 'msg' => 'Reminder sent to clubs' ] );

        $this->competition_service       = $comp_service;
        $this->competition_entry_service = $entry_service;
        $this->adapter                   = $this->make_adapter();

        $response = $this->adapter->notify_competition_entries_open( [
            'security'      => 'valid',
            'competitionId' => 10,
            'season'        => 2026,
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'Reminder sent to clubs', $response->get_content() );
    }

    public function test_notify_competition_entries_open_without_existing_entries_triggers_open(): void {
        $this->allow_security();

        $competition  = $this->create_competition_mock( 'team' );
        $comp_service = $this->createMock( Competition_Service::class );
        $comp_service->expects( $this->once() )
            ->method( 'get_by_id' )
            ->with( 10 )
            ->willReturn( $competition );
        $comp_service->expects( $this->once() )
            ->method( 'get_clubs_for_competition' )
            ->with( 10, 2026 )
            ->willReturn( [] );

        $entry_service = $this->createMock( Competition_Entry_Service::class );
        $entry_service->expects( $this->once() )
            ->method( 'notify_team_entry_open' )
            ->with( 10, 2026 )
            ->willReturn( (object) [ 'msg' => 'Entries opened to clubs' ] );

        $this->competition_service       = $comp_service;
        $this->competition_entry_service = $entry_service;
        $this->adapter                   = $this->make_adapter();

        $response = $this->adapter->notify_competition_entries_open( [
            'security'      => 'valid',
            'competitionId' => 10,
            'season'        => 2026,
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'Entries opened to clubs', $response->get_content() );
    }

    public function test_notify_competition_entries_open_reminder_exception_returns_500(): void {
        $this->allow_security();

        $competition  = $this->create_competition_mock( 'team' );
        $comp_service = $this->createMock( Competition_Service::class );
        $comp_service->expects( $this->once() )
            ->method( 'get_by_id' )
            ->with( 10 )
            ->willReturn( $competition );
        $comp_service->expects( $this->once() )
            ->method( 'get_clubs_for_competition' )
            ->with( 10, 2026 )
            ->willReturn( [ 1 ] );

        $entry_service = $this->createMock( Competition_Entry_Service::class );
        $entry_service->expects( $this->once() )
            ->method( 'notify_team_entry_reminder' )
            ->with( 10, 2026 )
            ->willThrowException( new Clubs_Not_Found_Exception( 'No clubs found for notification' ) );

        $this->competition_service       = $comp_service;
        $this->competition_entry_service = $entry_service;
        $this->adapter                   = $this->make_adapter();

        $response = $this->adapter->notify_competition_entries_open( [
            'security'      => 'valid',
            'competitionId' => 10,
            'season'        => 2026,
        ] );
        $this->assertSame( 500, $response->get_status_code() );
        $this->assertSame( 'No clubs found for notification', $response->get_content() );
    }

    public function test_notify_tournament_entries_open_unauthorized_returns_403(): void {
        $this->allow_security( true, false );

        $response = $this->adapter->notify_tournament_entries_open( [
            'security'     => 'valid',
            'tournamentId' => 1,
        ] );
        $this->assertSame( 403, $response->get_status_code() );
    }

    public function test_notify_tournament_entries_open_success(): void {
        $this->allow_security();
        $this->setup_tournament_entry_service_notification( 55, 12 );

        $response = $this->adapter->notify_tournament_entries_open( [
            'security'     => 'valid',
            'tournamentId' => 55,
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( '12 notifications sent', $response->get_content() );
    }

    public function test_notify_tournament_entries_open_failure_zero_sent_returns_500(): void {
        $this->allow_security();
        $this->setup_tournament_entry_service_notification( 55, 0 );

        $response = $this->adapter->notify_tournament_entries_open( [
            'security'     => 'valid',
            'tournamentId' => 55,
        ] );
        $this->assertSame( 500, $response->get_status_code() );
        $this->assertSame( 'Notifications not sent', $response->get_content() );
    }

    public function test_notify_tournament_entries_open_exception_returns_500(): void {
        $this->allow_security();
        $this->setup_tournament_entry_service_notification( 55, new Tournament_Not_Found_Exception( 'Tournament not found' ) );

        $response = $this->adapter->notify_tournament_entries_open( [
            'security'     => 'valid',
            'tournamentId' => 55,
        ] );
        $this->assertSame( 500, $response->get_status_code() );
        $this->assertSame( 'Tournament not found', $response->get_content() );
    }

    public function test_notify_teams_fixture_unauthorized_returns_403(): void {
        $this->allow_security( true, false );

        $response = $this->adapter->notify_teams_fixture( [
            'security' => 'valid',
            'matchId'  => '100',
        ] );
        $this->assertSame( 403, $response->get_status_code() );
    }

    public function test_notify_teams_fixture_success(): void {
        $this->allow_security();
        $this->setup_fixture_notification( 777, true );

        $response = $this->adapter->notify_teams_fixture( [
            'security' => 'valid',
            'matchId'  => '777',
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( [ 'message' => 'Teams notified' ], $response->get_content() );
    }

    public function test_notify_teams_fixture_not_sent(): void {
        $this->allow_security();
        $this->setup_fixture_notification( 777, false );

        $response = $this->adapter->notify_teams_fixture( [
            'security' => 'valid',
            'matchId'  => '777',
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( [ 'message' => 'No notification' ], $response->get_content() );
    }

    public function test_send_fixtures_unauthorized_returns_403(): void {
        $this->allow_security( true, false );

        $response = $this->adapter->send_fixtures( [
            'security' => 'valid',
            'eventId'  => 1,
        ] );
        $this->assertSame( 403, $response->get_status_code() );
    }

    public function test_send_fixtures_event_not_found_returns_404(): void {
        $this->allow_security();
        $GLOBALS['wp_stubs_events'] = [ 404 => null ];

        $response = $this->adapter->send_fixtures( [
            'security' => 'valid',
            'eventId'  => 404,
        ] );
        $this->assertSame( 404, $response->get_status_code() );
        $this->assertSame( 'Event not found', $response->get_content() );
    }

    public function test_send_fixtures_no_contact_emails_returns_500(): void {
        $this->allow_security();

        $event = $this->createStub( Event::class );
        $event->competition = (object) [ 'type' => 'league' ];
        $event->method( 'get_leagues' )->willReturn( [ (object) [ 'id' => 10 ] ] );
        $GLOBALS['wp_stubs_events'] = [ 100 => $event ];

        $league = new class {
            public int $id = 10;
            public string $title = 'Division 1';
            public function get_league_teams( array $args = [] ): array {
                // Team without contactemail
                return [ (object) [ 'id' => 1, 'season' => '2026', 'title' => 'Team 1' ] ];
            }
            public function get_matches( array $args = [] ): array {
                return [];
            }
        };
        $GLOBALS['wp_stubs_leagues'] = [ 10 => $league ];

        $response = $this->adapter->send_fixtures( [
            'security' => 'valid',
            'eventId'  => 100,
        ] );
        $this->assertSame( 500, $response->get_status_code() );
        $this->assertSame( 'No notification', $response->get_content() );
    }

    public function test_send_fixtures_success_sends_email_and_returns_200(): void {
        $this->allow_security();

        $event              = $this->createStub( Event::class );
        $event->name        = 'Men Doubles Event';
        $event->competition = (object) [ 'type' => 'league' ];
        $event->method( 'get_leagues' )->willReturn( [ (object) [ 'id' => 20 ] ] );
        $GLOBALS['wp_stubs_events'] = [ 200 => $event ];

        $league = new class {
            public int $id = 20;
            public string $title = 'Premier Division';
            public function get_league_teams( array $args = [] ): array {
                return [
                    (object) [
                        'id'           => 101,
                        'title'        => 'Leigh Firsts',
                        'season'       => '2026',
                        'captain'      => 'Alice Captain',
                        'contactemail' => 'alice@example.com',
                        'club_id'      => 301,
                    ],
                ];
            }
            public function get_matches( array $args = [] ): array {
                return [ (object) [ 'id' => 1 ] ];
            }
        };
        $GLOBALS['wp_stubs_leagues'] = [ 20 => $league ];

        $club = (object) [
            'match_secretary' => (object) [
                'display_name' => 'Bob Secretary',
                'email'        => 'bob@club.com',
            ],
        ];
        $GLOBALS['wp_stubs_clubs'] = [ 301 => $club ];

        $this->racketmanager->site_name = 'Leigh Tennis Club';
        $this->racketmanager->site_url  = 'http://example.com';
        $this->racketmanager->method( 'get_confirmation_email' )->willReturn( 'admin@example.com' );
        $this->racketmanager->shortcodes = new class {
            public function load_template( string $slug, array $args = [], string $sub_directory = '' ): string {
                return 'Mock email body for ' . ( $args['competition'] ?? '' );
            }
        };

        $GLOBALS['wp_mail_calls'] = [];

        $response = $this->adapter->send_fixtures( [
            'security' => 'valid',
            'eventId'  => 200,
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertSame( 'Captains emailed', $content['msg'] );

        $this->assertCount( 1, $GLOBALS['wp_mail_calls'] );
        $mail = $GLOBALS['wp_mail_calls'][0];
        $this->assertSame( 'Alice Captain <alice@example.com>', $mail['to'] );
        $this->assertStringContainsString( 'Leigh Tennis Club - Premier Division - Season 2026 - Fixtures - Leigh Firsts', $mail['subject'] );
        $this->assertStringContainsString( 'Mock email body for Men Doubles Event', $mail['message'] );
        $this->assertContains( 'From: League Secretary <admin@example.com>', $mail['headers'] );
        $this->assertContains( 'cc: Bob Secretary <bob@club.com>', $mail['headers'] );
    }

    public function test_set_tournament_dates_unauthorized_returns_403(): void {
        $this->allow_security( true, false );

        $response = $this->adapter->set_tournament_dates( [
            'security'  => 'valid',
            'dateStart' => '2026-06-01',
        ] );
        $this->assertSame( 403, $response->get_status_code() );
    }

    public function test_set_tournament_dates_missing_start_date_returns_500(): void {
        $this->allow_security();

        $response = $this->adapter->set_tournament_dates( [
            'security' => 'valid',
        ] );
        $this->assertSame( 500, $response->get_status_code() );
        $this->assertSame( 'No start date specified', $response->get_content() );
    }

    public function test_set_tournament_dates_no_lead_time_parameters_returns_500(): void {
        $this->allow_security();
        $this->racketmanager->method( 'get_options' )->willReturn( [] );

        $response = $this->adapter->set_tournament_dates( [
            'security'  => 'valid',
            'dateStart' => '2026-06-01',
        ] );
        $this->assertSame( 500, $response->get_status_code() );
        $this->assertSame( 'No lead time parameters set', $response->get_content() );
    }

    public function test_set_tournament_dates_success(): void {
        $this->allow_security();

        $this->racketmanager->method( 'get_options' )->willReturn( [
            'open_lead_time'  => '30',
            'date_closing'    => [ 'A' => '7' ],
            'date_withdrawal' => [ 'A' => '5' ],
        ] );

        $response = $this->adapter->set_tournament_dates( [
            'security'  => 'valid',
            'grade'     => 'A',
            'dateStart' => '2026-06-01',
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $content = $response->get_content();
        $this->assertSame( 'Dates set', $content->msg );
    }

    public function test_check_team_exists_returns_boolean(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( true );
        $this->racketmanager->method( 'get_team_id' )->willReturn( 42 );

        $response = $this->adapter->check_team_exists( [
            'security' => 'valid',
            'name'     => 'Leigh 1',
        ] );
        $this->assertSame( 200, $response->get_status_code() );
        $this->assertTrue( $response->get_content() );
    }

    public function test_nonce_failure_missing_nonce_returns_403_with_generic_problem_message(): void {
        $response = $this->adapter->save_add_points( [] );
        $this->assertSame( 403, $response->get_status_code() );
        $this->assertStringContainsString( 'There was a problem with your request', (string) $response->get_content() );
    }

    public function test_nonce_failure_invalid_nonce_returns_403_with_expired_invalid_message(): void {
        $this->security_service->method( 'verify_nonce' )->willReturn( false );
        $response = $this->adapter->save_add_points( [ 'security' => 'invalid_token' ] );
        $this->assertSame( 403, $response->get_status_code() );
        $this->assertStringContainsString( 'expired or is invalid', (string) $response->get_content() );
    }
}
