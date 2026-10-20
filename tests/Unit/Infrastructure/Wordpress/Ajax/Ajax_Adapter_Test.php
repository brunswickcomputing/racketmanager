<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Ajax;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Event\Queries\Get_Team_Order_Players_Handler;
use Racketmanager\Application\Event\Queries\Get_Team_Order_Players_Query;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Ajax\Frontend_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Tournament_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\Public\Shortcodes;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;
use WP_Error;

class Ajax_Adapter_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['test_current_user_id'] = 3;
    }

    protected function tearDown(): void {
        unset(
            $GLOBALS['test_competition_obj'],
            $GLOBALS['test_tournament_obj'],
            $GLOBALS['test_event_obj'],
            $GLOBALS['test_league_obj'],
            $GLOBALS['test_current_user_id'],
            $GLOBALS['racketmanager_competition_clubs_callback'],
            $GLOBALS['racketmanager_tournament_overview_callback'],
            $GLOBALS['racketmanager_event_draw_callback'],
            $GLOBALS['racketmanager_league_overview_callback']
        );
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_tab_data_rejects_invalid_link_id_and_does_not_execute_callback(): void {
        $adapter = $this->create_frontend_adapter();

        $comp = $this->createStub( Competition::class );
        $comp->id = 10;
        $comp->method( 'get_id' )->willReturn( 10 );
        $comp->method( 'get_seasons' )->willReturn( [ '2026' => [ 'name' => '2026' ] ] );
        $GLOBALS['test_competition_obj'] = $comp;

        $called = false;
        $GLOBALS['racketmanager_competition_clubs_callback'] = function () use ( &$called ) {
            $called = true;
            return 'should not be called';
        };

        $response = $adapter->tab_data( [
            'security' => 'valid_nonce',
            'target'   => 'competition',
            'targetId' => 10,
            'tab'      => 'clubs',
            'link_id'  => '<script>alert(1)</script>',
        ] );

        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'Invalid link ID', $response->get_content() );
        $this->assertFalse( $called );
    }

    public function test_tab_data_accepts_valid_link_id_and_valid_season(): void {
        $adapter = $this->create_frontend_adapter();

        $comp = $this->createStub( Competition::class );
        $comp->id = 10;
        $comp->method( 'get_id' )->willReturn( 10 );
        $comp->method( 'get_seasons' )->willReturn( [ '2026' => [ 'name' => '2026' ] ] );
        $GLOBALS['test_competition_obj'] = $comp;

        $passed_id   = null;
        $passed_args = null;
        $GLOBALS['racketmanager_competition_clubs_callback'] = function ( $id, $args ) use ( &$passed_id, &$passed_args ) {
            $passed_id   = $id;
            $passed_args = $args;
            return 'clubs content';
        };

        $response = $adapter->tab_data( [
            'security' => 'valid_nonce',
            'target'   => 'competition',
            'targetId' => 10,
            'tab'      => 'clubs',
            'link_id'  => 'valid-club_123',
            'season'   => '2026',
        ] );

        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'clubs content', $response->get_content() );
        $this->assertSame( 10, $passed_id );
        $this->assertSame( '2026', $passed_args['season'] ?? null );
        $this->assertSame( 'valid-club_123', $passed_args['clubs'] ?? null );
    }

    public function test_tab_data_omits_manipulated_season_from_callback_args(): void {
        $adapter = $this->create_frontend_adapter();

        $comp = $this->createStub( Competition::class );
        $comp->id = 10;
        $comp->method( 'get_id' )->willReturn( 10 );
        $comp->method( 'get_seasons' )->willReturn( [ '2026' => [ 'name' => '2026' ] ] );
        $GLOBALS['test_competition_obj'] = $comp;

        $passed_args = null;
        $GLOBALS['racketmanager_competition_clubs_callback'] = function ( $id, $args ) use ( &$passed_args ) {
            $passed_args = $args;
            return 'clubs content';
        };

        $response = $adapter->tab_data( [
            'security' => 'valid_nonce',
            'target'   => 'competition',
            'targetId' => 10,
            'tab'      => 'clubs',
            'season'   => '1999', // Not in allowed seasons
        ] );

        $this->assertSame( 200, $response->get_status_code() );
        $this->assertSame( 'clubs content', $response->get_content() );
        $this->assertArrayNotHasKey( 'season', $passed_args );
    }

    public function test_tab_data_validates_event_target_with_json_seasons(): void {
        $adapter = $this->create_frontend_adapter();

        // Real event instance / stub where seasons property is stored as a JSON string
        $event = $this->createStub( Event::class );
        $event->id = 20;
        $event->seasons = json_encode( [ '2026' => [ 'name' => '2026' ], '2025' => [ 'name' => '2025' ] ] );
        $event->method( 'get_seasons' )->willReturn( [ '2026' => [ 'name' => '2026' ], '2025' => [ 'name' => '2025' ] ] );
        $GLOBALS['test_event_obj'] = $event;

        $passed_args = null;
        $GLOBALS['racketmanager_event_draw_callback'] = function ( $id, $args ) use ( &$passed_args ) {
            $passed_args = $args;
            return 'event draw content';
        };

        // Valid season matches
        $adapter->tab_data( [
            'security' => 'valid_nonce',
            'target'   => 'event',
            'targetId' => 20,
            'tab'      => 'draw',
            'link_id'  => 'draw_link',
            'season'   => '2026',
        ] );
        $this->assertSame( '2026', $passed_args['season'] ?? null );

        // Forged season fails closed and is omitted
        $passed_args = null;
        $adapter->tab_data( [
            'security' => 'valid_nonce',
            'target'   => 'event',
            'targetId' => 20,
            'tab'      => 'draw',
            'link_id'  => 'draw_link',
            'season'   => 'forged_season',
        ] );
        $this->assertArrayNotHasKey( 'season', $passed_args );
    }

    public function test_tab_data_validates_league_target_with_event_json_seasons(): void {
        $adapter = $this->create_frontend_adapter();

        $event = $this->createStub( Event::class );
        $event->id = 30;
        $event->seasons = json_encode( [ '2026' => [ 'name' => '2026' ] ] );
        $event->method( 'get_seasons' )->willReturn( [ '2026' => [ 'name' => '2026' ] ] );

        $league = new stdClass();
        $league->id = 50;
        $league->event = $event;
        $GLOBALS['wp_stubs_leagues'][50] = $league;

        $passed_args = null;
        $GLOBALS['racketmanager_league_overview_callback'] = function ( $id, $args ) use ( &$passed_args ) {
            $passed_args = $args;
            return 'league overview content';
        };

        // Valid season
        $adapter->tab_data( [
            'security' => 'valid_nonce',
            'target'   => 'league',
            'targetId' => 50,
            'tab'      => 'overview',
            'link_id'  => 'league_link',
            'season'   => '2026',
        ] );
        $this->assertSame( '2026', $passed_args['season'] ?? null );

        // Forged season
        $passed_args = null;
        $adapter->tab_data( [
            'security' => 'valid_nonce',
            'target'   => 'league',
            'targetId' => 50,
            'tab'      => 'overview',
            'link_id'  => 'league_link',
            'season'   => 'forged',
        ] );
        $this->assertArrayNotHasKey( 'season', $passed_args );
    }

    public function test_tab_data_validates_tournament_target_season(): void {
        $adapter = $this->create_frontend_adapter();

        $tournament = $this->createStub( Tournament::class );
        $tournament->id = 5;
        $tournament->season = '2026';
        $tournament->method( 'get_id' )->willReturn( 5 );
        $tournament->method( 'get_season' )->willReturn( 2026 );
        $GLOBALS['test_tournament_obj'] = $tournament;

        $passed_args = null;
        $GLOBALS['racketmanager_tournament_overview_callback'] = function ( $id, $args ) use ( &$passed_args ) {
            $passed_args = $args;
            return 'tournament overview';
        };

        // Matching season passes
        $response = $adapter->tab_data( [
            'security' => 'valid_nonce',
            'target'   => 'tournament',
            'targetId' => 5,
            'tab'      => 'overview',
            'season'   => '2026',
        ] );
        $this->assertSame( 'tournament overview', $response->get_content() );
        $this->assertSame( '2026', $passed_args['season'] ?? null );

        // Mismatched season is filtered out
        $passed_args = null;
        $response = $adapter->tab_data( [
            'security' => 'valid_nonce',
            'target'   => 'tournament',
            'targetId' => 5,
            'tab'      => 'overview',
            'season'   => '2024',
        ] );
        $this->assertSame( 'tournament overview', $response->get_content() );
        $this->assertArrayNotHasKey( 'season', $passed_args );
    }

    public function test_tab_data_handles_unknown_target_and_invalid_tab(): void {
        $adapter = $this->create_frontend_adapter();

        $res1 = $adapter->tab_data( [
            'security' => 'valid_nonce',
            'target'   => 'unknown_target',
        ] );
        $this->assertSame( 'Invalid target', $res1->get_content() );

        $res_no_target = $adapter->tab_data( [
            'security' => 'valid_nonce',
        ] );
        $this->assertSame( 'Target ref not found', $res_no_target->get_content() );

        $comp = $this->createStub( Competition::class );
        $comp->id = 10;
        $comp->method( 'get_id' )->willReturn( 10 );
        $GLOBALS['test_competition_obj'] = $comp;

        $res2 = $adapter->tab_data( [
            'security' => 'valid_nonce',
            'target'   => 'competition',
            'targetId' => 10,
            'tab'      => 'invalid_tab_name',
        ] );
        $this->assertSame( 'Tab not valid', $res2->get_content() );
    }

    public function test_tournament_ajax_adapter_returns_400_with_season_error(): void {
        $sec_service  = $this->createStub( Security_Service_Interface::class );
        $sec_service->method( 'verify_nonce' )->willReturn( true );

        $resp_factory = $this->createStub( Json_Response_Factory_Interface::class );
        $resp_factory->method( 'create_error_response' )->willReturnCallback(
            static function ( $content, $status = null ) {
                return new Response( $content, $status ?? 400 );
            }
        );

        $entry_service = $this->createStub( Competition_Entry_Service::class );
        $wp_error      = new WP_Error();
        $wp_error->add( 'season', 'Season does not match this tournament' );
        $entry_service->method( 'request_tournament_entry' )->willReturn( $wp_error );
        $tournament = $this->createStub( Tournament::class );
        $tournament_service = $this->createStub( Tournament_Service::class );
        $tournament_service->method( 'get_tournament' )->willReturn( $tournament );

        $adapter = new Tournament_Ajax_Adapter(
            $sec_service,
            $resp_factory,
            $entry_service,
            $this->createStub( Finance_Service::class ),
            $tournament_service
        );

        $response = $adapter->tournament_entry_request( [
            'racketmanager_nonce' => 'valid_nonce',
            'tournament_id'       => 8,
            'playerId'            => 3,
            'season'              => '2025',
        ] );

        $this->assertSame( 400, $response->get_status_code() );
        $details = $response->get_content();
        $this->assertIsObject( $details );
        $this->assertTrue( $details->error );
        $this->assertContains( 'season', $details->err_flds );
    }

    public function test_team_order_players_gender_inclusive_for_senior_and_open_events(): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $club_service = $this->createStub( Club_Service::class );
        $reg_service  = $this->createMock( Registration_Service::class );
        $settings_service = $this->createStub( Settings_Service::class );
        $settings_service->method( 'get_category' )->willReturn( [ 'ageLimitCheck' => true ] );

        $comp = $this->createStub( Competition::class );
        $comp->id = 10;
        $comp->method( 'is_event_allowed' )->willReturn( true );

        // 1. Test Senior ('S') event type
        $event_s = $this->createStub( Event::class );
        $event_s->id = 77;
        $event_s->type = 'SD'; // Senior Open Doubles
        $event_s->age_limit = '45';
        $event_s->age_offset = '5';
        $event_s->competition_id = 10;
        $event_s->competition = $comp;
        $event_s->current_season = [ 'name' => '2026' ];
        $event_s->method( 'get_teams' )->willReturn( [] );

        $club = $this->createStub( Club::class );
        $club->id = 100;
        $club->method( 'get_id' )->willReturn( 100 );
        $club_service->method( 'get_club' )->willReturn( $club );
        $club_service->method( 'can_user_update_as_captain' )->willReturn( true );

        $comp_service->method( 'get_event_by_id' )->willReturnCallback( function ( $id ) use ( $event_s ) {
            return $event_s;
        } );

        // Expect gender = null, min_age = 45 for 'S' event
        $reg_service->expects( $this->once() )
            ->method( 'get_registered_players_list' )
            ->with( 'active', null, 100, null, true, null, 45 )
            ->willReturn( [ (object) [ 'id' => 1, 'name' => 'Senior Player' ] ] );

        $handler = new Get_Team_Order_Players_Handler( $comp_service, $club_service, $reg_service, $settings_service );
        $vm = $handler->handle( new Get_Team_Order_Players_Query( event_id: 77, club_id: 100 ) );

        $this->assertFalse( $vm->is_error() );
        $this->assertArrayHasKey( 'x', $vm->vars['club_players'] );
        $this->assertCount( 1, $vm->vars['club_players']['x'] );
    }

    public function test_team_order_players_gender_inclusive_for_open_events(): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $club_service = $this->createStub( Club_Service::class );
        $reg_service  = $this->createMock( Registration_Service::class );
        $settings_service = $this->createStub( Settings_Service::class );
        $settings_service->method( 'get_category' )->willReturn( [ 'ageLimitCheck' => false ] );

        $comp = $this->createStub( Competition::class );
        $comp->id = 10;
        $comp->method( 'is_event_allowed' )->willReturn( true );

        // 2. Test Open ('O') event type
        $event_o = $this->createStub( Event::class );
        $event_o->id = 88;
        $event_o->type = 'OD'; // Open Doubles
        $event_o->age_limit = null;
        $event_o->competition_id = 10;
        $event_o->competition = $comp;
        $event_o->current_season = [ 'name' => '2026' ];
        $event_o->method( 'get_teams' )->willReturn( [] );

        $club = $this->createStub( Club::class );
        $club->id = 100;
        $club->method( 'get_id' )->willReturn( 100 );
        $club_service->method( 'get_club' )->willReturn( $club );
        $club_service->method( 'can_user_update_as_captain' )->willReturn( true );

        $comp_service->method( 'get_event_by_id' )->willReturn( $event_o );

        // Expect gender = null for 'O' event
        $reg_service->expects( $this->once() )
            ->method( 'get_registered_players_list' )
            ->with( 'active', null, 100, null, true, null, null )
            ->willReturn( [ (object) [ 'id' => 2, 'name' => 'Open Player' ] ] );

        $handler = new Get_Team_Order_Players_Handler( $comp_service, $club_service, $reg_service, $settings_service );
        $vm = $handler->handle( new Get_Team_Order_Players_Query( event_id: 88, club_id: 100 ) );

        $this->assertFalse( $vm->is_error() );
        $this->assertArrayHasKey( 'x', $vm->vars['club_players'] );
        $this->assertCount( 1, $vm->vars['club_players']['x'] );
    }

    public function test_shortcodes_get_club_players_gender_inclusive_for_s_and_o_events(): void {
        $rm          = $this->createStub( RacketManager::class );
        $rm->method( 'get_options' )->willReturn( [ 'ageLimitCheck' => false ] );
        $reg_service = $this->createMock( Registration_Service::class );

        $container = new Simple_Container();
        $container->set( 'registration_service', fn() => $reg_service );
        $rm->container = $container;

        $club = $this->createStub( Club::class );
        $club->method( 'get_id' )->willReturn( 100 );

        $shortcodes = new class( $rm ) extends Shortcodes {
            public function call_get_club_players( object $event, object $club ): array {
                return $this->get_club_players( $event, $club );
            }
        };

        $event_s = (object) [ 'type' => 'SD', 'age_limit' => null ];
        $reg_service->expects( $this->once() )
            ->method( 'get_registered_players_list' )
            ->with( 'active', null, 100, null, true, null, null )
            ->willReturn( [ (object) [ 'id' => 5 ] ] );

        $res = $shortcodes->call_get_club_players( $event_s, $club );
        $this->assertArrayHasKey( 'x', $res );
        $this->assertCount( 1, $res['x'] );
    }

    private function create_frontend_adapter(): Frontend_Ajax_Adapter {
        $sec_service  = $this->createStub( Security_Service_Interface::class );
        $sec_service->method( 'verify_nonce' )->willReturn( true );

        $resp_factory = $this->createStub( Json_Response_Factory_Interface::class );
        $resp_factory->method( 'create_raw_response' )->willReturnCallback(
            static function ( $content, $status = null ) {
                return new Response( $content, $status ?? 200 );
            }
        );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturnCallback( function ( $id ) {
            if ( isset( $GLOBALS['test_competition_obj'] ) ) {
                return $GLOBALS['test_competition_obj'];
            }
            throw new Competition_Not_Found_Exception( 'Competition not found' );
        } );
        $comp_service->method( 'get_event_by_id' )->willReturnCallback( function ( $id ) {
            if ( isset( $GLOBALS['test_event_obj'] ) ) {
                return $GLOBALS['test_event_obj'];
            }
            throw new Event_Not_Found_Exception( 'Event not found' );
        } );

        $entry_service = $this->createStub( Competition_Entry_Service::class );
        $team_service  = $this->createStub( Team_Service::class );
        $player_service= $this->createStub( Player_Service::class );
        $reg_service   = $this->createStub( Registration_Service::class );
        $club_service  = $this->createStub( Club_Service::class );
        $tourn_service = $this->createStub( Tournament_Service::class );
        $tourn_service->method( 'get_tournament' )->willReturnCallback( function ( $id ) {
            if ( isset( $GLOBALS['test_tournament_obj'] ) ) {
                return $GLOBALS['test_tournament_obj'];
            }
            throw new Tournament_Not_Found_Exception( 'Tournament not found' );
        } );

        $fixture_repo = $this->createStub( Fixture_Repository_Interface::class );

        return new Frontend_Ajax_Adapter(
            $sec_service,
            $resp_factory,
            $comp_service,
            $entry_service,
            $team_service,
            $player_service,
            $reg_service,
            $club_service,
            $tourn_service,
            $fixture_repo,
            $this->createStub( \Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface::class )
        );
    }
}

// Global functions mock for Racketmanager tab handlers in tests
namespace Racketmanager;

if ( ! function_exists( 'Racketmanager\competition_clubs' ) ) {
    function competition_clubs( $id, $args = [] ) {
        if ( isset( $GLOBALS['racketmanager_competition_clubs_callback'] ) ) {
            return ( $GLOBALS['racketmanager_competition_clubs_callback'] )( $id, $args );
        }
        return '';
    }
}

if ( ! function_exists( 'Racketmanager\tournament_overview' ) ) {
    function tournament_overview( $id, $args = [] ) {
        if ( isset( $GLOBALS['racketmanager_tournament_overview_callback'] ) ) {
            return ( $GLOBALS['racketmanager_tournament_overview_callback'] )( $id, $args );
        }
        return '';
    }
}

if ( ! function_exists( 'Racketmanager\event_draw' ) ) {
    function event_draw( $id, $args = [] ) {
        if ( isset( $GLOBALS['racketmanager_event_draw_callback'] ) ) {
            return ( $GLOBALS['racketmanager_event_draw_callback'] )( $id, $args );
        }
        return '';
    }
}

if ( ! function_exists( 'Racketmanager\league_overview' ) ) {
    function league_overview( $id, $args = [] ) {
        if ( isset( $GLOBALS['racketmanager_league_overview_callback'] ) ) {
            return ( $GLOBALS['racketmanager_league_overview_callback'] )( $id, $args );
        }
        return '';
    }
}
