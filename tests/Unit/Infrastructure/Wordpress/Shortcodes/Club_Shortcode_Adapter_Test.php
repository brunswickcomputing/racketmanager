<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Club\Queries\Get_Club_Competitions_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Invoices_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Players_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Role_Modal_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Roles_Handler;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Club\Club_Player;
use Racketmanager\Domain\Club\Club_Role;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Factory;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Club\Club_Details_DTO;
use Racketmanager\Domain\DTO\Club\Club_Player_DTO;
use Racketmanager\Domain\DTO\Competition\Competition_Hydration_DTO;
use Racketmanager\Domain\Finance\Invoice;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Domain\Tournament\Currency;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Exceptions\Role_Assignment_Not_Found_Exception;
use Racketmanager\Infrastructure\Gateways\Option\Wp_Option_Gateway;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Club_Shortcode_Adapter;
use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\Presenters\Club_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Invoice_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\Presenters\Team_Details_Presenter;
use Racketmanager\Presentation\View_Models\Club_Competition_View_Model;
use Racketmanager\Presentation\View_Models\Club_Event_View_Model;
use Racketmanager\Presentation\View_Models\Club_Roster_Player_View_Model;
use Racketmanager\Presentation\View_Models\Event_Header_View_Model;
use Racketmanager\Presentation\View_Models\Player_View_Model;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Creates_Event_Query_Service;
use Racketmanager\Tests\Helpers\Creates_Test_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class Club_Shortcode_Adapter_Test extends TestCase {
    use Creates_Event_Query_Service;
    use Creates_Test_Container;
    use Manages_Wordpress_Globals;

    /** @var array<int, array{path: string, vars: array}> Templates rendered through the capturing renderer. */
    private array $rendered = [];

    /** @var array<int, Event[]> Events returned by Competition_Service::get_events_for_competition(), keyed by competition id. */
    private array $competition_events = [];

    /** @var array<int, array> Teams per event id. */
    private array $event_teams = [];

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        global $wp, $racketmanager;
        $wp                    = (object) [ 'query_vars' => [] ];
        $racketmanager         = $this->createStub( RacketManager::class );
        $GLOBALS['query_vars'] = [ 'club_name' => 'leigh-ltc' ];
        $this->rendered        = [];
        unset( $GLOBALS['wp_stubs_is_user_logged_in'], $GLOBALS['wp_stubs_current_user_can'] );
        $GLOBALS['wp_stubs_teams'] = [];
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    // ---------------------------------------------------------------------
    // [club] / [clubs]
    // ---------------------------------------------------------------------

    public function test_show_club_renders_club_details_via_handler_and_presenter(): void {
        $club = $this->club();

        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_club_by_shortcode' )->willReturn( $club );
        $club_service->method( 'get_club_details' )->willReturn( new Club_Details_DTO( $club ) );

        $settings = $this->createStub( Settings_Service::class );
        $settings->method( 'get_category' )->willReturn( [ 'googleMapsKey' => 'MAP_KEY_123' ] );

        $adapter = $this->adapter( [ 'club_service' => $club_service, 'settings' => $settings ] );

        global $wp;
        $wp->query_vars['club_name'] = 'leigh-ltc';

        $output = $adapter->show_club( [] );

        $this->assertStringContainsString( 'Leigh Tennis Club', $output );
        $this->assertStringContainsString( '123 Tennis Lane', $output );
    }

    public function test_show_club_returns_danger_alert_when_club_not_found(): void {
        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_club_by_shortcode' )->willThrowException( new Club_Not_Found_Exception( 'Club not found' ) );

        $output = $this->adapter( [ 'club_service' => $club_service ] )->show_club( [ 'club_name' => 'Unknown' ] );

        $this->assertStringContainsString( 'alert--danger', $output );
        $this->assertStringContainsString( 'Club not found', $output );
    }

    public function test_show_clubs_renders_via_club_service(): void {
        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_clubs_with_details' )->willReturn( [ $this->club() ] );

        $output = $this->adapter( [ 'club_service' => $club_service ] )->show_clubs( [] );

        $this->assertStringContainsString( 'Leigh Tennis Club', $output );
    }

    public function test_show_clubs_asks_for_current_clubs_and_honours_template_attribute(): void {
        $club_service = $this->createMock( Club_Service::class );
        $club_service->expects( $this->once() )->method( 'get_clubs_with_details' )->with( [ 'type' => 'current' ] )->willReturn( [] );

        $this->adapter( [ 'club_service' => $club_service, 'renderer' => $this->capturing_renderer() ] )->show_clubs( [ 'template' => 'compact' ] );

        $this->assertSame( 'clubs-compact', $this->rendered[0]['path'] );
        $this->assertFalse( $this->rendered[0]['vars']['standalone'] );
    }

    // ---------------------------------------------------------------------
    // [club-players]
    // ---------------------------------------------------------------------

    public function test_show_club_players_lists_active_registered_players(): void {
        $club_service = $this->club_service_for( $this->club() );
        $club_service->method( 'can_user_update_players' )->willReturn( true );
        $registration = $this->createMock( Registration_Service::class );
        $registration->expects( $this->once() )->method( 'get_registered_players_list' )->with( 'active', null, 10 )->willReturn(
            [
                $this->club_player_dto( 1, 42, 'Roger Federer', 'M', '12345678', [ 'S' => '9.4', 'D' => '10.1' ] ),
                $this->club_player_dto( 2, 43, 'Martina Hingis', 'F', '87654321', [ 'S' => '8.0', 'D' => '7.5' ] ),
            ]
        );

        $output = $this->adapter(
            [
                'club_service' => $club_service,
                'registration' => $registration,
                'renderer'     => $this->capturing_renderer(),
                'display'      => [ 'wtn' => true ],
            ]
        )->show_club_players( [] );

        $this->assertSame( 'rendered:club/players', $output );
        $vars = $this->rendered[0]['vars'];
        $this->assertCount( 2, $vars['players'] );
        $this->assertContainsOnlyInstancesOf( Club_Roster_Player_View_Model::class, $vars['players'] );
        $this->assertSame( 'Roger Federer', $vars['players'][0]->display_name );
        $this->assertSame( '/clubs/leigh-ltc/players/roger-federer/', $vars['players'][0]->player_url );
        $this->assertSame( '[S:9.4] - [D:10.1]', $vars['players'][0]->rating_display );
        $this->assertSame( 'WTN', $vars['rating_header'] );
        $this->assertNull( $vars['player'] );
        $this->assertTrue( $vars['user_can_update'] );
    }

    public function test_show_club_players_renders_roster_template_with_wtn_from_real_dtos(): void {
        $club_service = $this->club_service_for( $this->club() );
        $registration = $this->createStub( Registration_Service::class );
        $registration->method( 'get_registered_players_list' )->willReturn(
            [
                $this->club_player_dto( 1, 42, 'Roger Federer', 'M', '12345678', [ 'S' => '9.4', 'D' => '10.1' ] ),
                $this->club_player_dto( 2, 43, 'Martina Hingis', 'F', '87654321', [ 'S' => '8.0', 'D' => '7.5' ] ),
            ]
        );

        $output = $this->capture_without_warnings(
            fn() => $this->adapter( [ 'club_service' => $club_service, 'registration' => $registration, 'display' => [ 'wtn' => true ] ] )->show_club_players( [] ),
            $errors
        );

        $this->assertSame( [], $errors, 'Rendering club/players produced PHP warnings: ' . implode( '; ', $errors ) );
        $this->assertStringContainsString( 'Leigh Tennis Club', $output );
        $this->assertStringContainsString( '>WTN</th>', $output );
        $this->assertStringContainsString( 'href="/clubs/leigh-ltc/players/roger-federer/">Roger Federer</a>', $output );
        $this->assertStringContainsString( 'href="/clubs/leigh-ltc/players/martina-hingis/">Martina Hingis</a>', $output );
        $this->assertStringContainsString( '12345678', $output );
        $this->assertStringContainsString( '[S:9.4] - [D:10.1]', $output );
        $this->assertStringContainsString( '[S:8.0] - [D:7.5]', $output );
        $this->assertStringContainsString( 'id="club_player-1"', $output );
    }

    public function test_show_club_players_rating_points_mode_has_no_rating_source_and_shows_placeholders(): void {
        $club_service = $this->club_service_for( $this->club() );
        $registration = $this->createStub( Registration_Service::class );
        $registration->method( 'get_registered_players_list' )->willReturn(
            [ $this->club_player_dto( 1, 42, 'Roger Federer', 'M', '12345678', [ 'S' => '9.4', 'D' => '10.1' ] ) ]
        );

        $output = $this->capture_without_warnings(
            fn() => $this->adapter( [ 'club_service' => $club_service, 'registration' => $registration, 'display' => [] ] )->show_club_players( [] ),
            $errors
        );

        $this->assertSame( [], $errors, 'Rendering club/players produced PHP warnings: ' . implode( '; ', $errors ) );
        $this->assertStringContainsString( '>Rating Points</th>', $output );
        $this->assertStringContainsString( '[S:-] - [D:-]', $output );
        $this->assertStringNotContainsString( '9.4', $output );
    }

    public function test_show_club_players_single_player_from_query_var(): void {
        $GLOBALS['query_vars']['player_id'] = 'jane-doe';
        $club_service                       = $this->club_service_for( $this->club() );
        $player                             = $this->createStub( Player::class );
        $player->id                         = 7;
        $players                            = $this->createMock( Player_Service::class );
        $players->expects( $this->once() )->method( 'get_player_by_name' )->with( 'jane doe' )->willReturn( $player );
        $registration = $this->createMock( Registration_Service::class );
        $registration->expects( $this->once() )->method( 'get_player_for_club' )->with( 10, 7 )->willReturn( $this->club_player_dto( 10, 7, 'Jane Doe', 'F', '11112222' ) );
        $registration->expects( $this->never() )->method( 'get_registered_players_list' );

        $this->adapter(
            [ 'club_service' => $club_service, 'players' => $players, 'registration' => $registration, 'renderer' => $this->capturing_renderer() ]
        )->show_club_players( [ 'template' => 'single' ] );

        $this->assertSame( 'club/players-single', $this->rendered[0]['path'] );
        $this->assertInstanceOf( Player_View_Model::class, $this->rendered[0]['vars']['player_vm'] );
    }

    public function test_show_club_players_single_player_unregistered_returns_not_found(): void {
        $GLOBALS['query_vars']['player_id'] = 'jane-doe';
        $club_service                       = $this->club_service_for( $this->club() );
        $player                             = $this->createStub( Player::class );
        $player->id                         = 7;
        $players                            = $this->createMock( Player_Service::class );
        $players->expects( $this->once() )->method( 'get_player_by_name' )->with( 'jane doe' )->willReturn( $player );
        $registration = $this->createMock( Registration_Service::class );
        $registration->expects( $this->once() )->method( 'get_player_for_club' )->with( 10, 7 )->willReturn( null );
        $registration->expects( $this->never() )->method( 'get_registered_players_list' );

        $output = $this->adapter(
            [ 'club_service' => $club_service, 'players' => $players, 'registration' => $registration, 'renderer' => $this->capturing_renderer() ]
        )->show_club_players( [ 'template' => 'single' ] );

        $this->assertSame( 'rendered:alert', $output );
        $this->assertSame( 'Player not found', $this->rendered[0]['vars']['msg'] );
    }

    public function test_show_club_players_single_player_uses_wordpress_referrer_when_available(): void {
        $GLOBALS['query_vars']['player_id']  = 'jane-doe';
        $GLOBALS['wp_stubs_wp_get_referer'] = 'https://example.test/previous-page/';

        $this->adapter(
            [
                'club_service' => $this->club_service_for( $this->club() ),
                'players'      => $this->player_service_returning( 7 ),
                'registration' => $this->registration_returning_player( 'Jane Doe' ),
                'renderer'     => $this->capturing_renderer(),
            ]
        )->show_club_players( [] );

        $this->assertSame( 'https://example.test/previous-page/', $this->rendered[0]['vars']['page_referrer'] );
        $this->assertSame( '/clubs/leigh-ltc/players/', $this->rendered[0]['vars']['club_players_url'] );
    }

    public function test_show_club_players_single_player_falls_back_to_club_players_url_without_referrer(): void {
        $GLOBALS['query_vars']['player_id'] = 'jane-doe';
        unset( $GLOBALS['wp_stubs_wp_get_referer'] );

        $this->adapter(
            [
                'club_service' => $this->club_service_for( $this->club() ),
                'players'      => $this->player_service_returning( 7 ),
                'registration' => $this->registration_returning_player( 'Jane Doe' ),
                'renderer'     => $this->capturing_renderer(),
            ]
        )->show_club_players( [] );

        $this->assertSame( '/clubs/leigh-ltc/players/', $this->rendered[0]['vars']['page_referrer'] );
    }

    public function test_show_club_players_renders_single_player_template_with_return_link(): void {
        $GLOBALS['query_vars']['player_id']  = 'jane-doe';
        $GLOBALS['wp_stubs_wp_get_referer'] = 'https://example.test/previous-page/';

        $output = $this->capture_without_warnings(
            fn() => $this->adapter(
                [
                    'club_service' => $this->club_service_for( $this->club() ),
                    'players'      => $this->player_service_returning( 7 ),
                    'registration' => $this->registration_returning_player( 'Jane Doe' ),
                ]
            )->show_club_players( [] ),
            $errors
        );

        $this->assertSame( [], $errors, 'Rendering club/player produced PHP warnings: ' . implode( '; ', $errors ) );
        $this->assertStringContainsString( 'Jane Doe', $output );
        $this->assertStringContainsString( 'href="https://example.test/previous-page/"', $output );

        unset( $GLOBALS['wp_stubs_wp_get_referer'] );
        $output = $this->capture_without_warnings(
            fn() => $this->adapter(
                [
                    'club_service' => $this->club_service_for( $this->club() ),
                    'players'      => $this->player_service_returning( 7 ),
                    'registration' => $this->registration_returning_player( 'Jane Doe' ),
                ]
            )->show_club_players( [] ),
            $errors
        );

        $this->assertSame( [], $errors, 'Rendering club/player produced PHP warnings: ' . implode( '; ', $errors ) );
        $this->assertStringContainsString( 'href="/clubs/leigh-ltc/players/"', $output );
    }

    public function test_show_club_players_unknown_player_renders_alert(): void {
        $GLOBALS['query_vars']['player_id'] = 'nobody';
        $players                            = $this->createStub( Player_Service::class );
        $players->method( 'get_player_by_name' )->willReturn( null );

        $output = $this->adapter( [ 'club_service' => $this->club_service_for( $this->club() ), 'players' => $players ] )->show_club_players( [] );

        $this->assertStringContainsString( 'alert--danger', $output );
        $this->assertStringContainsString( 'Player not found', $output );
    }

    public function test_show_club_players_unknown_club_renders_alert(): void {
        $output = $this->adapter( [ 'club_service' => $this->club_service_unknown() ] )->show_club_players( [] );

        $this->assertStringContainsString( 'alert--danger', $output );
        $this->assertStringContainsString( 'No such club', $output );
    }

    // ---------------------------------------------------------------------
    // [club-roles] / [club-role-modal]
    // ---------------------------------------------------------------------

    public function test_show_club_roles_renders_roles_for_club(): void {
        $club_service = $this->club_service_for( $this->club() );
        $club_service->method( 'get_roles_for_club' )->willReturn( [ 'role-a' ] );

        $output = $this->adapter( [ 'club_service' => $club_service, 'renderer' => $this->capturing_renderer() ] )->show_club_roles( [] );

        $this->assertSame( 'rendered:club/roles', $output );
        $this->assertSame( [ 'role-a' ], $this->rendered[0]['vars']['roles'] );
        $this->assertSame( 10, $this->rendered[0]['vars']['club']->id );
    }

    public function test_show_club_roles_unknown_club_renders_alert(): void {
        $output = $this->adapter( [ 'club_service' => $this->club_service_unknown() ] )->show_club_roles( [] );

        $this->assertStringContainsString( 'No such club', $output );
    }

    public function test_show_club_role_modal_passes_role_modal_and_lookup(): void {
        $club_service = $this->createMock( Club_Service::class );
        $role         = Club_Role::from_object( (object) [
            'id'      => 5,
            'role_id' => 1,
            'club_id' => 10,
            'user'    => (object) [ 'id' => 1, 'display_name' => 'John Doe' ],
            'role'    => (object) [ 'id' => 1, 'name' => 'Secretary', 'desc' => 'Club Secretary' ],
        ] );
        $club_service->expects( $this->once() )->method( 'get_role' )->with( 5 )->willReturn( $role );

        $this->adapter( [ 'club_service' => $club_service, 'renderer' => $this->capturing_renderer() ] )
            ->show_club_role_modal( [ 'id' => '5', 'modal' => 'edit' ] );

        $this->assertSame( 'club/club-role-modal', $this->rendered[0]['path'] );
        $this->assertSame( $role, $this->rendered[0]['vars']['club_role'] );
        $this->assertSame( 'edit', $this->rendered[0]['vars']['modal'] );
        $this->assertArrayHasKey( 'club_roles', $this->rendered[0]['vars'] );
    }

    public function test_show_club_role_modal_error_renders_modal_alert(): void {
        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_role' )->willThrowException( new Role_Assignment_Not_Found_Exception( 'Role missing' ) );

        $this->adapter( [ 'club_service' => $club_service, 'renderer' => $this->capturing_renderer() ] )->show_club_role_modal( [ 'id' => 5 ] );

        $this->assertSame( 'alert-modal', $this->rendered[0]['path'] );
        $this->assertSame( 'Role missing', $this->rendered[0]['vars']['msg'] );
    }

    // ---------------------------------------------------------------------
    // [club-competitions]
    // ---------------------------------------------------------------------

    public function test_show_club_competitions_keeps_only_competitions_with_club_teams(): void {
        $club_service = $this->club_service_for( $this->club() );
        $club_service->method( 'can_user_update' )->willReturn( true );

        $event_with_teams = $this->event_with_teams( 101, "Men's Division 1", [ 'id' => 5, 'title' => 'Leigh LTC 1' ] );
        $event_without    = $this->event_with_teams( 102, 'Ladies Division 1' );
        $with_teams       = $this->competition_with_events( 'Summer League', [ $event_with_teams, $event_without ] );
        $without_teams    = $this->competition_with_events( 'Winter League', [ $event_without ] );

        $competition_service = $this->competition_service_by_type( [ 'league' => [ $with_teams, $without_teams ] ] );

        $this->adapter( [ 'club_service' => $club_service, 'competition_service' => $competition_service, 'renderer' => $this->capturing_renderer() ] )
            ->show_club_competitions( [] );

        $vars = $this->rendered[0]['vars'];
        $this->assertSame( 'club/competitions', $this->rendered[0]['path'] );
        $this->assertTrue( $vars['user_can_update'] );
        $this->assertContainsOnlyInstancesOf( Club_Competition_View_Model::class, $vars['competitions'] );
        $this->assertCount( 1, $vars['competitions'] );
        $competition = $vars['competitions'][0];
        $this->assertSame( 'Summer League', $competition->name );
        $this->assertSame( '/summer-league/2026/club/leigh-ltc/', $competition->url );
        $this->assertCount( 1, $competition->events );
        $this->assertInstanceOf( Club_Event_View_Model::class, $competition->events[0] );
        $this->assertSame( "Men's Division 1", $competition->events[0]->name );
        $this->assertSame( '/clubs/leigh-ltc/event/mens-division-1/', $competition->events[0]->url );
        $this->assertCount( 1, $competition->events[0]->team_rows );
        $this->assertSame( 'Leigh LTC 1', $competition->events[0]->team_rows[0]->title );
    }

    public function test_show_club_competitions_does_not_write_presentation_state_onto_domain_objects(): void {
        $club             = $this->club();
        $event_with_teams = $this->event_with_teams( 101, "Men's Division 1", [ 'id' => 5, 'title' => 'Leigh LTC 1' ] );
        $competition      = $this->competition_with_events( 'Summer League', [ $event_with_teams ] );
        $competition_service = $this->competition_service_by_type( [ 'league' => [ $competition ] ] );

        $this->adapter(
            [ 'club_service' => $this->club_service_for( $club ), 'competition_service' => $competition_service, 'renderer' => $this->capturing_renderer() ]
        )->show_club_competitions( [] );

        $this->assertSame( [], $club->competitions, 'The club entity must not be mutated by the query' );
        $this->assertFalse( property_exists( $competition, 'club_url' ) );
        $this->assertFalse( property_exists( $event_with_teams, 'club_url' ) );
    }

    public function test_show_club_competitions_renders_template_with_links_from_handler(): void {
        $club_service = $this->club_service_for( $this->club() );
        $event        = $this->event_with_teams( 101, "Men's Division 1", [ 'id' => 5, 'title' => 'Leigh LTC 1' ] );
        $competition  = $this->competition_with_events( 'Summer League', [ $event ] );
        $competition_service = $this->competition_service_by_type( [ 'league' => [ $competition ] ] );

        $output = $this->capture_without_warnings(
            fn() => $this->adapter( [ 'club_service' => $club_service, 'competition_service' => $competition_service ] )->show_club_competitions( [] ),
            $errors
        );

        $this->assertSame( [], $errors, 'Rendering club/competitions produced PHP warnings: ' . implode( '; ', $errors ) );
        $this->assertStringContainsString( 'Summer League', $output );
        $this->assertStringContainsString( 'href="/summer-league/2026/club/leigh-ltc/"', $output );
        $this->assertMatchesRegularExpression( '/Men(\'|&#0?39;)s Division 1/', $output );
        $this->assertStringContainsString( 'href="/clubs/leigh-ltc/event/mens-division-1/"', $output );
        $this->assertStringContainsString( 'Leigh LTC 1', $output );
        $this->assertStringNotContainsString( 'Club has no teams in this event', $output );
    }

    public function test_show_club_competitions_event_without_current_season_has_no_link(): void {
        $event       = $this->event_with_teams( 101, 'Plate', [ 'id' => 5, 'title' => 'Leigh LTC 1' ], false );
        $competition = $this->competition_with_events( 'Summer Cup', [ $event ] );
        $competition_service = $this->competition_service_by_type( [ 'cup' => [ $competition ] ] );

        $output = $this->capture_without_warnings(
            fn() => $this->adapter(
                [ 'club_service' => $this->club_service_for( $this->club() ), 'competition_service' => $competition_service ]
            )->show_club_competitions( [] ),
            $errors
        );

        $this->assertSame( [], $errors, 'Rendering club/competitions produced PHP warnings: ' . implode( '; ', $errors ) );
        $this->assertStringContainsString( 'Plate', $output );
        $this->assertStringNotContainsString( '/event/plate/', $output );
    }

    public function test_show_club_competitions_named_competition_from_query_var(): void {
        $GLOBALS['query_vars']['competition_name'] = 'summer-cup';
        $event_with_teams = $this->event_with_teams( 101, 'Mixed Cup', [ 'id' => 5, 'title' => 'Leigh LTC 1' ] );
        $event_without    = $this->event_with_teams( 102, 'Ladies Cup' );
        $competition      = $this->competition_with_events( 'Summer Cup', [ $event_with_teams, $event_without ] );

        $repository = $this->createMock( Competition_Repository_Interface::class );
        $repository->expects( $this->once() )->method( 'find_by_name' )->with( 'summer cup' )->willReturn( $competition );

        $this->adapter(
            [ 'club_service' => $this->club_service_for( $this->club() ), 'competition_repository' => $repository, 'renderer' => $this->capturing_renderer() ]
        )->show_club_competitions( [] );

        $competitions = $this->rendered[0]['vars']['competitions'];
        $this->assertCount( 1, $competitions );
        $this->assertSame( 'Summer Cup', $competitions[0]->name );
        $this->assertCount( 1, $competitions[0]->events );
        $this->assertSame( 'Mixed Cup', $competitions[0]->events[0]->name );
        $this->assertSame( '/clubs/leigh-ltc/event/mixed-cup/', $competitions[0]->events[0]->url );
    }

    public function test_show_club_competitions_named_competition_without_teams_renders_empty_competitions(): void {
        $GLOBALS['query_vars']['competition_name'] = 'summer-cup';
        $competition = $this->competition_with_events( 'Summer Cup', [ $this->event_with_teams( 102, 'Ladies Cup' ) ] );

        $repository = $this->createMock( Competition_Repository_Interface::class );
        $repository->expects( $this->once() )->method( 'find_by_name' )->with( 'summer cup' )->willReturn( $competition );

        $this->adapter(
            [ 'club_service' => $this->club_service_for( $this->club() ), 'competition_repository' => $repository, 'renderer' => $this->capturing_renderer() ]
        )->show_club_competitions( [] );

        $this->assertSame( [], $this->rendered[0]['vars']['competitions'] );
    }

    public function test_show_club_competitions_unknown_competition_renders_alert(): void {
        $GLOBALS['query_vars']['competition_name'] = 'nothing';
        $repository                                = $this->createStub( Competition_Repository_Interface::class );
        $repository->method( 'find_by_name' )->willReturn( null );

        $output = $this->adapter(
            [ 'club_service' => $this->club_service_for( $this->club() ), 'competition_repository' => $repository ]
        )->show_club_competitions( [] );

        $this->assertStringContainsString( 'Competition not found', $output );
    }

    // ---------------------------------------------------------------------
    // [club-invoices]
    // ---------------------------------------------------------------------

    public function test_show_club_invoices_lists_invoices_for_club(): void {
        $finance = $this->createMock( Finance_Service::class );
        $finance->expects( $this->once() )->method( 'get_invoices_by_criteria' )->with( [ 'billable' => 10 ] )->willReturn( [ 'inv-1' ] );

        $this->adapter(
            [ 'club_service' => $this->club_service_for( $this->club() ), 'finance' => $finance, 'renderer' => $this->capturing_renderer() ]
        )->show_club_invoices( [] );

        $this->assertSame( 'club/invoices', $this->rendered[0]['path'] );
        $this->assertInstanceOf( \Racketmanager\Presentation\View_Models\Club_Invoices_List_View_Model::class, $this->rendered[0]['vars']['invoices'] );
        $this->assertCount( 1, $this->rendered[0]['vars']['invoices'] );
        $this->assertNull( $this->rendered[0]['vars']['invoice'] );
        $this->assertFalse( $this->rendered[0]['vars']['user_can_manage']->club );
    }

    public function test_show_club_invoices_single_invoice_for_this_club_includes_details(): void {
        $GLOBALS['query_vars']['invoice'] = '5';
        $finance                          = $this->createMock( Finance_Service::class );
        $finance->expects( $this->once() )->method( 'get_invoice' )->with( 5 )->willReturn( $this->invoice( 5, 10 ) );

        $this->adapter(
            [ 'club_service' => $this->club_service_for( $this->club() ), 'finance' => $finance, 'renderer' => $this->capturing_renderer() ]
        )->show_club_invoices( [ 'template' => 'pdf' ] );

        $this->assertSame( 'club/invoice-pdf', $this->rendered[0]['path'] );
        $this->assertSame( 'invoice:5', $this->rendered[0]['vars']['invoice']->details );
    }

    public function test_show_club_invoices_rejects_invoice_of_another_club(): void {
        $GLOBALS['query_vars']['invoice'] = '5';
        $finance                          = $this->createStub( Finance_Service::class );
        $finance->method( 'get_invoice' )->willReturn( $this->invoice( 5, 99 ) );

        $output = $this->adapter( [ 'club_service' => $this->club_service_for( $this->club() ), 'finance' => $finance ] )->show_club_invoices( [] );

        $this->assertStringContainsString( 'Invoice not for this club', $output );
    }

    public function test_show_club_invoices_unknown_invoice_renders_alert(): void {
        $GLOBALS['query_vars']['invoice'] = '404';
        $finance                          = $this->createStub( Finance_Service::class );
        $finance->method( 'get_invoice' )->willThrowException( new Invoice_Not_Found_Exception( 'Invoice 404 missing' ) );

        $output = $this->adapter( [ 'club_service' => $this->club_service_for( $this->club() ), 'finance' => $finance ] )->show_club_invoices( [] );

        $this->assertStringContainsString( 'Invoice 404 missing', $output );
    }

    public function test_show_club_invoices_management_permission_for_admin_secretary_and_anonymous(): void {
        // Anonymous.
        $GLOBALS['wp_stubs_is_user_logged_in'] = false;
        $this->adapter( [ 'club_service' => $this->club_service_for( $this->club() ), 'renderer' => $this->capturing_renderer() ] )->show_club_invoices( [] );
        $this->assertFalse( $this->rendered[0]['vars']['user_can_manage']->club );

        // Administrator.
        $this->rendered                         = [];
        $GLOBALS['wp_stubs_is_user_logged_in']  = true;
        $GLOBALS['wp_stubs_current_user_can']   = true;
        $this->adapter( [ 'club_service' => $this->club_service_for( $this->club() ), 'renderer' => $this->capturing_renderer() ] )->show_club_invoices( [] );
        $this->assertTrue( $this->rendered[0]['vars']['user_can_manage']->club );

        // Match secretary of this club.
        $this->rendered                         = [];
        $GLOBALS['wp_stubs_current_user_can']   = false;
        $GLOBALS['wp_stubs_get_current_user_id'] = 77;
        $club_service                           = $this->club_service_for( $this->club() );
        $club_service->method( 'is_user_match_secretary' )->willReturnCallback( static fn( int $user, int $club ) => 77 === $user && 10 === $club );
        $this->adapter( [ 'club_service' => $club_service, 'renderer' => $this->capturing_renderer() ] )->show_club_invoices( [] );
        $this->assertTrue( $this->rendered[0]['vars']['user_can_manage']->club );

        // Logged in, not a secretary.
        $this->rendered                         = [];
        $GLOBALS['wp_stubs_get_current_user_id'] = 78;
        $this->adapter( [ 'club_service' => $club_service, 'renderer' => $this->capturing_renderer() ] )->show_club_invoices( [] );
        $this->assertFalse( $this->rendered[0]['vars']['user_can_manage']->club );
    }

    // ---------------------------------------------------------------------
    // [club-event] / [club-team] / [team-edit]
    // ---------------------------------------------------------------------

    public function test_show_club_event_error_paths(): void {
        $adapter = $this->adapter( [ 'club_service' => $this->club_service_for( $this->club() ) ] );
        $this->assertStringContainsString( 'Event id not supplied', $adapter->show_club_event( [] ) );

        $unknown = $this->adapter( [ 'club_service' => $this->club_service_unknown() ] );
        $this->assertStringContainsString( 'No such club', $unknown->show_club_event( [] ) );
    }

    public function test_show_club_team_error_paths(): void {
        $adapter = $this->adapter( [ 'club_service' => $this->club_service_for( $this->club() ) ] );
        $this->assertStringContainsString( 'Team not supplied', $adapter->show_club_team( [] ) );

        $GLOBALS['query_vars']['team'] = 'first-team';
        $this->assertStringContainsString( 'Team not found', $adapter->show_club_team( [] ) );

        $GLOBALS['wp_stubs_teams']['first team'] = Team::from_object( (object) [ 'id' => 3, 'title' => 'First Team' ] );
        $this->assertStringContainsString( 'Event id not supplied', $adapter->show_club_team( [] ) );

        $unknown = $this->adapter( [ 'club_service' => $this->club_service_unknown() ] );
        $this->assertStringContainsString( 'No such club', $unknown->show_club_team( [] ) );
    }

    public function test_show_team_edit_modal_validates_attributes_with_modal_alerts(): void {
        $adapter = $this->adapter( [ 'renderer' => $this->capturing_renderer() ] );

        $adapter->show_team_edit_modal( [] );
        $adapter->show_team_edit_modal( [ 'id' => '5' ] );
        $adapter->show_team_edit_modal( [ 'id' => '5', 'event_id' => '3' ] );

        $this->assertSame( [ 'alert-modal', 'alert-modal', 'alert-modal' ], array_column( $this->rendered, 'path' ) );
        $this->assertSame( 'Team id not supplied', $this->rendered[0]['vars']['msg'] );
        $this->assertSame( 'Event id not supplied', $this->rendered[1]['vars']['msg'] );
        $this->assertSame( 'Team not found', $this->rendered[2]['vars']['msg'] );
        $this->assertSame( 'danger', $this->rendered[2]['vars']['class'] );
    }

    private function create_test_competition( int $id = 10, array $allowed_entry_events = [ 101, 102 ] ): Competition {
        return Competition_Factory::create_from_dto(
            new Competition_Hydration_DTO(
                id: $id,
                name: 'Test Competition ' . $id,
                type: Competition_Type::LEAGUE,
                age_group: 'Adults',
                seasons: [
                    '2026' => [
                        'name'         => '2026',
                        'entry_events' => $allowed_entry_events,
                    ],
                ],
                settings: []
            )
        );
    }

    private function create_test_event(
        int $id,
        string $name,
        int $competition_id = 10,
        ?Competition $competition = null,
        string $season = '2026'
    ): Event {
        $event                 = new class extends Event {
            public ?League $primary_league_obj = null;
            public function __construct() {
                // no-op
            }
            public function get_seasons(): array {
                return json_decode( $this->seasons, true ) ?? [];
            }
        };
        $event->id             = $id;
        $event->name           = $name;
        $event->competition_id = $competition_id;
        $event->seasons        = json_encode( [ $season => [ 'name' => $season ] ] );
        $event->current_season = [ 'name' => $season ];
        if ( $competition ) {
            $event->competition = $competition;
        }

        return $event;
    }

    public function test_show_club_event_enforces_allowlist(): void {
        $competition   = $this->create_test_competition( 10, [ 101, 102 ] );
        $allowed_event = $this->create_test_event( 101, 'Mens Doubles', 10, $competition );
        $blocked_event = $this->create_test_event( 103, 'Ladies Doubles', 10, $competition );

        $GLOBALS['wp_stubs_events']['mens doubles']   = $allowed_event;
        $GLOBALS['wp_stubs_events']['ladies doubles'] = $blocked_event;

        $adapter = $this->adapter( [
            'club_service' => $this->club_service_for( $this->club() ),
            'renderer'     => $this->capturing_renderer(),
        ] );

        // 1. Blocked event
        $GLOBALS['query_vars']['season'] = '2026';
        $GLOBALS['query_vars']['event']  = 'ladies-doubles';
        $adapter->show_club_event( [] );
        $this->assertSame( 'alert', $this->rendered[0]['path'] );
        $this->assertSame( 'Event not found for competition', $this->rendered[0]['vars']['msg'] );

        // 2. Allowed event
        $this->rendered = [];
        $GLOBALS['query_vars']['event'] = 'mens-doubles';
        $adapter->show_club_event( [] );
        $this->assertSame( 'club/event', $this->rendered[0]['path'] );
        $this->assertSame( 101, $this->rendered[0]['vars']['event']->id );
        $event_header = $this->rendered[0]['vars']['event_header'];
        $this->assertInstanceOf( Event_Header_View_Model::class, $event_header );
        $this->assertSame( 2, $event_header->header_level );
        $this->assertSame( '/clubs/leigh-ltc/event/mens-doubles/', $event_header->pagename );
        $this->assertSame( 'Mens Doubles', $event_header->name );
        $this->assertSame( '/test-competition-10/2026/', $event_header->competition_link );

        $event_header_html = ( new Shortcode_Template_Renderer(
            new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' )
        ) )->render( 'event-header', [ 'event_header' => $event_header ], 'includes' );
        $this->assertStringContainsString( '<h2', $event_header_html );
        $this->assertStringContainsString( 'id="pagename" value="/clubs/leigh-ltc/event/mens-doubles/"', $event_header_html );
        $this->assertStringContainsString( 'href="/test-competition-10/2026/"', $event_header_html );

        // 3. Unconfigured season on competition
        $this->rendered                  = [];
        $GLOBALS['query_vars']['season'] = '2020';
        $GLOBALS['query_vars']['event']  = 'mens-doubles';
        $adapter->show_club_event( [] );
        $this->assertSame( 'alert', $this->rendered[0]['path'] );
        $this->assertSame( 'Event not found for competition', $this->rendered[0]['vars']['msg'] );
    }

    public function test_show_club_event_passes_primary_league_for_championship_event(): void {
        $competition       = $this->create_test_competition( 10, [ 101 ] );
        $competition->type = Competition_Type::CUP;

        $championship = $this->createStub( Championship::class );
        $championship->method( 'get_finals' )->willReturn( [
            'final' => [ 'round' => 1, 'name' => 'Final' ],
            'semi'  => [ 'round' => 2, 'name' => 'Semi Final' ],
        ] );
        $championship->method( 'num_rounds' )->willReturn( 2 );

        $primary_league               = $this->createStub( League::class );
        $primary_league->id           = 10;
        $primary_league->championship = $championship;

        $event                      = $this->create_test_event( 101, 'Cup Event', 10, $competition );
        $event->is_championship     = true;
        $event->primary_league_obj  = $primary_league;

        $GLOBALS['wp_stubs_events']['cup event'] = $event;
        $GLOBALS['query_vars']['season']         = '2026';
        $GLOBALS['query_vars']['event']          = 'cup-event';

        $adapter = $this->adapter( [
            'club_service' => $this->club_service_for( $this->club() ),
            'renderer'     => $this->capturing_renderer(),
        ] );

        $adapter->show_club_event( [] );

        $this->assertSame( 'club/event', $this->rendered[0]['path'] );
        $this->assertSame( $primary_league, $this->rendered[0]['vars']['primary_league'] );
    }

    public function test_show_club_event_renders_championship_rounds_with_real_renderer(): void {
        $competition       = $this->create_test_competition( 10, [ 101 ] );
        $competition->type = Competition_Type::CUP;

        $championship = $this->createStub( Championship::class );
        $championship->method( 'get_finals' )->willReturn( [
            'final' => [ 'round' => 1, 'name' => 'Final' ],
            'semi'  => [ 'round' => 2, 'name' => 'Semi Final' ],
        ] );
        $championship->method( 'num_rounds' )->willReturn( 2 );

        $primary_league               = $this->createStub( League::class );
        $primary_league->id           = 10;
        $primary_league->championship = $championship;

        $event                      = $this->create_test_event( 101, 'Cup Event', 10, $competition );
        $event->is_championship     = true;
        $event->primary_league_obj  = $primary_league;

        $GLOBALS['wp_stubs_events']['cup event'] = $event;
        $GLOBALS['query_vars']['season']         = '2026';
        $GLOBALS['query_vars']['event']          = 'cup-event';

        $adapter = $this->adapter( [
            'club_service' => $this->club_service_for( $this->club() ),
        ] );

        $html = $adapter->show_club_event( [] );

        $this->assertStringContainsString( 'Player Matches', $html );
        $this->assertStringContainsString( 'Final', $html );
        $this->assertStringContainsString( 'Semi Final', $html );
    }

    public function test_show_club_team_enforces_allowlist(): void {
        $competition   = $this->create_test_competition( 10, [ 101, 102 ] );
        $allowed_event = $this->create_test_event( 101, 'Mens Doubles', 10, $competition );
        $blocked_event = $this->create_test_event( 103, 'Ladies Doubles', 10, $competition );

        $GLOBALS['wp_stubs_events']['mens doubles']   = $allowed_event;
        $GLOBALS['wp_stubs_events']['ladies doubles'] = $blocked_event;
        $GLOBALS['wp_stubs_teams']['first team']      = Team::from_object( (object) [ 'id' => 3, 'title' => 'First Team' ] );

        $adapter = $this->adapter( [
            'club_service' => $this->club_service_for( $this->club() ),
            'renderer'     => $this->capturing_renderer(),
        ] );

        // 1. Blocked event
        $GLOBALS['query_vars']['team']  = 'first-team';
        $GLOBALS['query_vars']['event'] = 'ladies-doubles';
        $adapter->show_club_team( [] );
        $this->assertSame( 'alert', $this->rendered[0]['path'] );
        $this->assertSame( 'Event not found for competition', $this->rendered[0]['vars']['msg'] );

        // 2. Allowed event
        $this->rendered = [];
        $GLOBALS['query_vars']['event'] = 'mens-doubles';
        $adapter->show_club_team( [] );
        $this->assertSame( 'club/team', $this->rendered[0]['path'] );
        $this->assertSame( 101, $this->rendered[0]['vars']['event']->id );
    }

    public function test_show_club_team_and_event_resolve_query_var_season_and_reject_unknown_season(): void {
        $competition = $this->create_test_competition( 10, [ 101 ] );
        $competition->set_seasons( [
            '2025' => [ 'name' => '2025', 'entry_events' => [ 101 ] ],
            '2026' => [ 'name' => '2026', 'entry_events' => [ 101 ] ],
        ] );
        $event = $this->create_test_event( 101, 'Mens Doubles', 10, $competition, '2026' );
        $event->seasons = json_encode( [
            '2025' => [ 'name' => '2025' ],
            '2026' => [ 'name' => '2026' ],
        ] );

        $GLOBALS['wp_stubs_events']['mens doubles'] = $event;
        $GLOBALS['wp_stubs_teams']['first team']    = Team::from_object( (object) [ 'id' => 3, 'title' => 'First Team' ] );

        $adapter = $this->adapter( [
            'club_service' => $this->club_service_for( $this->club() ),
            'renderer'     => $this->capturing_renderer(),
        ] );

        // 1. Valid query var season 2025 in show_club_event
        $this->rendered = [];
        $GLOBALS['query_vars']['event']  = 'mens-doubles';
        $GLOBALS['query_vars']['season'] = '2025';
        $adapter->show_club_event( [] );
        $this->assertSame( 'club/event', $this->rendered[0]['path'] );
        $this->assertSame( '2025', $this->rendered[0]['vars']['current_season']['name'] );
        $this->assertSame( '2025', $this->rendered[0]['vars']['event_header']->curr_season );

        // 2. Valid query var season 2025 in show_club_team
        $this->rendered = [];
        $GLOBALS['query_vars']['team']   = 'first-team';
        $GLOBALS['query_vars']['event']  = 'mens-doubles';
        $GLOBALS['query_vars']['season'] = '2025';
        $adapter->show_club_team( [] );
        $this->assertSame( 'club/team', $this->rendered[0]['path'] );
        $this->assertSame( '2025', $this->rendered[0]['vars']['event']->current_season['name'] );

        // 3. Unknown query var season 1999 in show_club_team yields allowlist rejection
        $this->rendered = [];
        $GLOBALS['query_vars']['season'] = '1999';
        $adapter->show_club_team( [] );
        $this->assertSame( 'alert', $this->rendered[0]['path'] );
        $this->assertSame( 'Event not found for competition', $this->rendered[0]['vars']['msg'] );

        // 4. Unknown query var season 1999 in show_club_event yields allowlist rejection
        $this->rendered = [];
        $GLOBALS['query_vars']['season'] = '1999';
        $adapter->show_club_event( [] );
        $this->assertSame( 'alert', $this->rendered[0]['path'] );
        $this->assertSame( 'Event not found for competition', $this->rendered[0]['vars']['msg'] );
    }

    public function test_show_team_edit_modal_enforces_allowlist(): void {
        $competition   = $this->create_test_competition( 10, [ 101, 102 ] );
        $allowed_event = $this->create_test_event( 101, 'Mens Doubles', 10, $competition );
        $blocked_event = $this->create_test_event( 103, 'Ladies Doubles', 10, $competition );

        $GLOBALS['wp_stubs_events'][101] = $allowed_event;
        $GLOBALS['wp_stubs_events'][103] = $blocked_event;
        $GLOBALS['wp_stubs_teams'][3]    = Team::from_object( (object) [ 'id' => 3, 'title' => 'First Team' ] );

        $adapter = $this->adapter( [
            'club_service' => $this->club_service_for( $this->club() ),
            'renderer'     => $this->capturing_renderer(),
        ] );

        // 1. Blocked event
        $adapter->show_team_edit_modal( [ 'id' => '3', 'event_id' => '103' ] );
        $this->assertSame( 'alert-modal', $this->rendered[0]['path'] );
        $this->assertSame( 'Event not found for competition', $this->rendered[0]['vars']['msg'] );

        // 2. Allowed event
        $this->rendered = [];
        $adapter->show_team_edit_modal( [ 'id' => '3', 'event_id' => '101' ] );
        $this->assertSame( 'club/team-edit-modal', $this->rendered[0]['path'] );
        $this->assertSame( 101, $this->rendered[0]['vars']['event']->id );
    }

    public function test_club_routes_fail_closed_when_competition_missing_or_foreign(): void {
        $competition   = $this->create_test_competition( 10, [ 101, 102 ] );
        $foreign_event = $this->create_test_event( 101, 'Foreign Event', 999, $competition );
        $missing_event = $this->create_test_event( 101, 'Missing Event', 10, null );

        $GLOBALS['wp_stubs_events']['foreign event'] = $foreign_event;
        $GLOBALS['wp_stubs_events']['missing event'] = $missing_event;
        $GLOBALS['wp_stubs_events'][999]             = $foreign_event;
        $GLOBALS['wp_stubs_events'][888]             = $missing_event;
        $GLOBALS['wp_stubs_teams']['first team']     = Team::from_object( (object) [ 'id' => 3, 'title' => 'First Team' ] );
        $GLOBALS['wp_stubs_teams'][3]                = Team::from_object( (object) [ 'id' => 3, 'title' => 'First Team' ] );
        $GLOBALS['query_vars']['season']             = '2026';

        $adapter = $this->adapter( [
            'club_service' => $this->club_service_for( $this->club() ),
            'renderer'     => $this->capturing_renderer(),
        ] );

        // Foreign event in show_club_event
        $GLOBALS['query_vars']['event'] = 'foreign-event';
        $adapter->show_club_event( [] );
        $this->assertSame( 'alert', $this->rendered[0]['path'] );
        $this->assertSame( 'Event not found for competition', $this->rendered[0]['vars']['msg'] );

        // Missing competition event in show_club_event
        $this->rendered                 = [];
        $GLOBALS['query_vars']['event'] = 'missing-event';
        $adapter->show_club_event( [] );
        $this->assertSame( 'alert', $this->rendered[0]['path'] );
        $this->assertSame( 'Event not found for competition', $this->rendered[0]['vars']['msg'] );

        // Foreign event in show_club_team
        $this->rendered                 = [];
        $GLOBALS['query_vars']['team']  = 'first-team';
        $GLOBALS['query_vars']['event'] = 'foreign-event';
        $adapter->show_club_team( [] );
        $this->assertSame( 'alert', $this->rendered[0]['path'] );
        $this->assertSame( 'Event not found for competition', $this->rendered[0]['vars']['msg'] );

        // Foreign event in show_team_edit_modal
        $this->rendered = [];
        $adapter->show_team_edit_modal( [ 'id' => '3', 'event_id' => '999' ] );
        $this->assertSame( 'alert-modal', $this->rendered[0]['path'] );
        $this->assertSame( 'Event not found for competition', $this->rendered[0]['vars']['msg'] );

        // Missing competition event in show_team_edit_modal
        $this->rendered = [];
        $adapter->show_team_edit_modal( [ 'id' => '3', 'event_id' => '888' ] );
        $this->assertSame( 'alert-modal', $this->rendered[0]['path'] );
        $this->assertSame( 'Event not found for competition', $this->rendered[0]['vars']['msg'] );
    }

    // ---------------------------------------------------------------------
    // Architecture guards
    // ---------------------------------------------------------------------

    public function test_adapter_has_no_legacy_cycle_or_service_locator(): void {
        $adapter = (string) file_get_contents( RACKETMANAGER_PATH . 'src/php/Infrastructure/Wordpress/Shortcodes/Club_Shortcode_Adapter.php' );

        $this->assertStringNotContainsString( 'Racketmanager\\Public\\', $adapter );
        $this->assertStringNotContainsString( 'legacy_handler', $adapter );
        $this->assertStringNotContainsString( 'container', $adapter );
        $this->assertSame( 0, preg_match( '/\?(Club_Service|Get_\w+_Handler|Shortcode_View_Presenter)\s+\$/', $adapter ), 'Dependencies must be required' );
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function club(): Club {
        return Club_Test_Factory::create(
            id: 10,
            name: 'Leigh Tennis Club',
            shortcode: 'Leigh LTC',
            address: '123 Tennis Lane',
            overrides: [
                'single' => true,
            ],
        );
    }

    private function invoice( int $id, int $billable_id ): Invoice {
        $invoice              = new Invoice();
        $invoice->id          = $id;
        $invoice->billable_id = $billable_id;

        return $invoice;
    }

    private function club_service_for( Club $club ): Club_Service {
        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_club_by_shortcode' )->willReturn( $club );

        return $club_service;
    }

    /**
     * Registration as the players query returns it: a real Club_Player_DTO built from the domain entities.
     *
     * @param array<string, string> $wtn
     */
    private function club_player_dto( int $registration_id, int $player_id, string $display_name, string $gender, string $btm, array $wtn = [] ): Club_Player_DTO {
        $player               = new Player();
        $player->id           = $player_id;
        $player->display_name = $display_name;
        $player->gender       = $gender;
        $player->btm          = $btm;
        $player->wtn          = $wtn;
        $player->firstname    = explode( ' ', $display_name )[0];
        $player->surname      = explode( ' ', $display_name )[1] ?? '';

        $registration            = new Club_Player();
        $registration->id        = $registration_id;
        $registration->player_id = $player_id;
        $registration->club_id   = 10;

        return new Club_Player_DTO( $player, $this->club(), $registration );
    }

    private function player_service_returning( int $player_id ): Player_Service {
        $player     = new Player();
        $player->id = $player_id;
        $service    = $this->createStub( Player_Service::class );
        $service->method( 'get_player_by_name' )->willReturn( $player );

        return $service;
    }

    private function registration_returning_player( string $display_name ): Registration_Service {
        $registration = $this->createStub( Registration_Service::class );
        $registration->method( 'get_player_for_club' )->willReturn( $this->club_player_dto( 3, 7, $display_name, 'F', '11112222' ) );

        return $registration;
    }

    /**
     * Event holding the given teams (none for an event the club does not play in).
     *
     * @param array<string, mixed>|null $team
     */
    private function event_with_teams( int $id, string $name, ?array $team = null, bool $with_season = true ): Event {
        $event = $this->createStub( Event::class );
        $event->id   = $id;
        $event->name = $name;
        // Event_Query_Service::get_teams_info() returns no teams for an event whose get_season() is empty, so the
        // season stays resolvable here; only the `current_season` property (used for URLs) is left unset.
        $event->method( 'get_season' )->willReturn( '2026' );
        if ( $with_season ) {
            $event->current_season = [ 'name' => '2026' ];
            $event->method( 'get_seasons' )->willReturn( [ '2026' => [ 'name' => '2026' ] ] );
        }
        $this->event_teams[ $id ] = null === $team ? [] : [ (object) $team ];

        return $event;
    }

    /**
     * @param Event[] $events
     */
    private function competition_with_events( string $name, array $events ): Competition {
        $id          = 900 + count( $this->competition_events );
        $competition = Competition_Test_Factory::create( id: $id, name: $name, current_season: [ 'name' => '2026' ] );

        $this->competition_events[ $id ] = $events;

        return $competition;
    }

    /**
     * Competition service listing the given competitions per type and serving the events registered by
     * competition_with_events(); asking for the events of an unregistered competition fails the test.
     *
     * @param array<string, Competition[]> $by_type
     */
    private function competition_service_by_type( array $by_type ): Competition_Service {
        $competition_service = $this->createStub( Competition_Service::class );
        $competition_service->method( 'get_by_criteria' )->willReturnCallback(
            static fn( array $criteria ) => $by_type[ $criteria['type'] ] ?? []
        );
        $competition_service->method( 'get_events_for_competition' )->willReturnCallback(
            function ( null|int|string $competition_id, ?int $season = null ): array {
                $this->assertArrayHasKey( $competition_id, $this->competition_events, 'Events requested for an unknown competition' );
                $this->assertNull( $season, 'Club competitions must request all events of the competition' );

                return $this->competition_events[ $competition_id ];
            }
        );

        return $competition_service;
    }

    /**
     * Runs the callback, collecting every PHP warning/notice/deprecation raised meanwhile.
     *
     * @param array<int, string>|null $errors
     */
    private function capture_without_warnings( callable $callback, ?array &$errors ): string {
        $errors = [];
        set_error_handler(
            static function ( int $errno, string $errstr ) use ( &$errors ): bool {
                $errors[] = $errstr;

                return true;
            }
        );

        try {
            return (string) $callback();
        } finally {
            restore_error_handler();
        }
    }

    private function club_service_unknown(): Club_Service {
        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_club_by_shortcode' )->willThrowException( new Club_Not_Found_Exception( 'No such club' ) );

        return $club_service;
    }

    /**
     * Renderer that records the template path and variables instead of including template files.
     */
    private function capturing_renderer(): View_Renderer_Interface {
        $renderer = $this->createStub( View_Renderer_Interface::class );
        $renderer->method( 'render_to_string' )->willReturnCallback(
            function ( string $path, $vm, array $vars ): string {
                $this->rendered[] = [ 'path' => $path, 'vars' => $vars ];

                return 'rendered:' . $path;
            }
        );

        return $renderer;
    }

    /**
     * @param array<string, mixed> $overrides club_service, settings, players, registration, competition_service,
     *                                        competition_repository, finance, renderer, display (racketmanager_display option).
     */
    private function adapter( array $overrides = [] ): Club_Shortcode_Adapter {
        $club_service = $overrides['club_service'] ?? $this->createStub( Club_Service::class );
        $renderer     = $overrides['renderer'] ?? new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $players      = $overrides['players'] ?? $this->createStub( Player_Service::class );
        $registration = $overrides['registration'] ?? $this->createStub( Registration_Service::class );
        $competitions = $overrides['competition_service'] ?? $this->competition_service_by_type( [] );
        $repository   = $overrides['competition_repository'] ?? $this->createStub( Competition_Repository_Interface::class );
        $finance      = $overrides['finance'] ?? $this->createStub( Finance_Service::class );

        $template_renderer = new Shortcode_Template_Renderer( $renderer, new Club_Presenter(), new Player_Presenter() );
        $settings          = $overrides['settings'] ?? new Settings_Service( [ 'billing' => [ 'billingCurrency' => Currency::GBP->value ] ] );
        if ( is_array( $settings ) ) {
            $settings = new Settings_Service( $settings );
        }
        $security          = $overrides['security_service'] ?? $this->createStub( Security_Service_Interface::class );
        $club_presenter    = new Club_Presenter();
        $option_gateway    = $this->createStub( Option_Gateway_Interface::class );
        $option_gateway->method( 'get_option' )->willReturnCallback(
            static fn( string $option, mixed $default = false ) => 'racketmanager_display' === $option ? ( $overrides['display'] ?? [] ) : $default
        );

        $event_repository = $this->createStub( Event_Repository_Interface::class );
        $event_repository->method( 'get_teams_info' )->willReturnCallback(
            fn( int $event_id ) => $this->event_teams[ $event_id ] ?? []
        );
        $event_query_service = $overrides['event_query_service'] ?? $this->event_query_service( $event_repository );

        $lookup_event_repository = $overrides['event_repository'] ?? $this->createStub( Event_Repository_Interface::class );
        if ( ! isset( $overrides['event_repository'] ) ) {
            $lookup_event_repository->method( 'find_by_name' )->willReturnCallback(
                static fn( string $name ) => $GLOBALS['wp_stubs_events'][ $name ] ?? null
            );
            $lookup_event_repository->method( 'find_by_id' )->willReturnCallback(
                static fn( $id ) => $GLOBALS['wp_stubs_events'][ $id ] ?? null
            );
        }
        $team_repository = $overrides['team_repository'] ?? $this->createStub( Team_Repository_Interface::class );
        if ( ! isset( $overrides['team_repository'] ) ) {
            $team_repository->method( 'find_by_id' )->willReturnCallback(
                static fn( int|string|null $id ) => $GLOBALS['wp_stubs_teams'][ $id ] ?? null
            );
        }

        return new Club_Shortcode_Adapter(
            $club_service,
            new Get_Club_Handler( $club_service, $settings, $security ),
            new Club_Shortcode_Presenter( $renderer, new Club_Presenter(), new Player_Presenter() ),
            new Get_Club_Players_Handler( $club_service, $players, $registration, $option_gateway ),
            new Get_Club_Roles_Handler( $club_service ),
            new Get_Club_Role_Modal_Handler( $club_service ),
            new Get_Club_Competitions_Handler( $club_service, $competitions, $repository, $event_query_service ),
            new Get_Club_Invoices_Handler( $club_service, $finance, $settings ),
            new Shortcode_View_Presenter( $template_renderer ),
            $overrides['competition_presenter'] ?? new Competition_Shortcode_Presenter( $renderer ),
            $event_query_service,
            $lookup_event_repository,
            $team_repository,
            club_presenter: $club_presenter,
            player_presenter: new Player_Presenter(),
            invoice_presenter: new Invoice_Presenter(),
            team_details_presenter: new Team_Details_Presenter( new Wp_Option_Gateway(), $settings )
        );
    }
}
