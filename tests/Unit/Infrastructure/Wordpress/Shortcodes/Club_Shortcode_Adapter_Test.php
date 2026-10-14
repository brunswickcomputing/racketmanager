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
use Racketmanager\Domain\Club;
use Racketmanager\Domain\Club_Role;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\DTO\Club\Club_Details_DTO;
use Racketmanager\Domain\Invoice;
use Racketmanager\Domain\Player;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Exceptions\Role_Assignment_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Club_Shortcode_Adapter;
use Racketmanager\Presenters\Club_Presenter;
use Racketmanager\Presenters\Club_Shortcode_Presenter;
use Racketmanager\Presenters\Player_Presenter;
use Racketmanager\Presenters\Shortcode_View_Presenter;
use Racketmanager\Public\Shortcodes_Club;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Services\View\View_Renderer_Interface;

class Club_Shortcode_Adapter_Test extends TestCase {
    private mixed $original_wp;
    private mixed $original_racketmanager;
    private mixed $original_query_vars;

    /** @var array<int, array{path: string, vars: array}> Templates rendered through the capturing renderer. */
    private array $rendered = [];

    protected function setUp(): void {
        parent::setUp();
        if ( ! defined( 'RACKETMANAGER_PATH' ) ) {
            define( 'RACKETMANAGER_PATH', dirname( __DIR__, 5 ) . '/' );
        }
        if ( ! defined( 'RACKETMANAGER_URL' ) ) {
            define( 'RACKETMANAGER_URL', 'https://example.test/wp-content/plugins/racketmanager/' );
        }
        global $wp, $racketmanager;
        $this->original_wp            = $wp ?? null;
        $this->original_racketmanager = $racketmanager ?? null;
        $this->original_query_vars    = $GLOBALS['query_vars'] ?? null;
        $wp                           = (object) [ 'query_vars' => [] ];
        $racketmanager                = $this->createStub( RacketManager::class );
        $GLOBALS['query_vars']        = [ 'club_name' => 'leigh-ltc' ];
        $this->rendered               = [];
        unset( $GLOBALS['wp_stubs_is_user_logged_in'], $GLOBALS['wp_stubs_current_user_can'] );
        $GLOBALS['wp_stubs_teams'] = [];
    }

    protected function tearDown(): void {
        global $wp, $racketmanager;
        $wp                    = $this->original_wp;
        $racketmanager         = $this->original_racketmanager;
        $GLOBALS['query_vars'] = $this->original_query_vars;
        unset( $GLOBALS['wp_stubs_is_user_logged_in'], $GLOBALS['wp_stubs_current_user_can'], $GLOBALS['wp_stubs_get_current_user_id'], $GLOBALS['wp_stubs_teams'] );
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
        $registration->expects( $this->once() )->method( 'get_registered_players_list' )->with( 'active', null, 10 )->willReturn( [ 'p1', 'p2' ] );

        $output = $this->adapter(
            [ 'club_service' => $club_service, 'registration' => $registration, 'renderer' => $this->capturing_renderer() ]
        )->show_club_players( [] );

        $this->assertSame( 'rendered:club/players', $output );
        $vars = $this->rendered[0]['vars'];
        $this->assertSame( [ 'p1', 'p2' ], $vars['players'] );
        $this->assertNull( $vars['player'] );
        $this->assertTrue( $vars['user_can_update'] );
    }

    public function test_show_club_players_single_player_from_query_var(): void {
        $GLOBALS['query_vars']['player_id'] = 'jane-doe';
        $club_service                       = $this->club_service_for( $this->club() );
        $player                             = $this->createStub( Player::class );
        $player->id                         = 7;
        $players                            = $this->createMock( Player_Service::class );
        $players->expects( $this->once() )->method( 'get_player_by_name' )->with( 'jane doe' )->willReturn( $player );
        $registration = $this->createMock( Registration_Service::class );
        $registration->expects( $this->once() )->method( 'get_player_for_club' )->with( 10, 7 )->willReturn( null );
        $registration->expects( $this->never() )->method( 'get_registered_players_list' );

        $this->adapter(
            [ 'club_service' => $club_service, 'players' => $players, 'registration' => $registration, 'renderer' => $this->capturing_renderer() ]
        )->show_club_players( [ 'template' => 'single' ] );

        $this->assertSame( 'club/players-single', $this->rendered[0]['path'] );
        $this->assertSame( [], $this->rendered[0]['vars']['players'] );
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
        $role         = new Club_Role( (object) [
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

        $event_with_teams = $this->createStub( Event::class );
        $event_with_teams->method( 'get_teams_info' )->willReturn( [ 'team-a' ] );
        $event_without    = $this->createStub( Event::class );
        $event_without->method( 'get_teams_info' )->willReturn( [] );
        $with_teams       = $this->createStub( Competition::class );
        $with_teams->method( 'get_events' )->willReturn( [ $event_with_teams, $event_without ] );
        $without_teams    = $this->createStub( Competition::class );
        $without_teams->method( 'get_events' )->willReturn( [ $event_without ] );

        $competition_service = $this->createStub( Competition_Service::class );
        $competition_service->method( 'get_by_criteria' )->willReturnCallback(
            static fn( array $criteria ) => 'league' === $criteria['type'] ? [ $with_teams, $without_teams ] : []
        );

        $this->adapter( [ 'club_service' => $club_service, 'competition_service' => $competition_service, 'renderer' => $this->capturing_renderer() ] )
            ->show_club_competitions( [] );

        $vars = $this->rendered[0]['vars'];
        $this->assertSame( 'club/competitions', $this->rendered[0]['path'] );
        $this->assertTrue( $vars['user_can_update'] );
        $this->assertCount( 1, $vars['club']->competitions );
        $this->assertSame( [ $event_with_teams ], array_values( $vars['club']->competitions[0]->events ) );
    }

    public function test_show_club_competitions_named_competition_from_query_var(): void {
        $GLOBALS['query_vars']['competition_name'] = 'summer-cup';
        $competition                               = $this->createStub( Competition::class );
        $repository                                = $this->createMock( Competition_Repository_Interface::class );
        $repository->expects( $this->once() )->method( 'find_by_name' )->with( 'summer cup' )->willReturn( $competition );

        $this->adapter(
            [ 'club_service' => $this->club_service_for( $this->club() ), 'competition_repository' => $repository, 'renderer' => $this->capturing_renderer() ]
        )->show_club_competitions( [] );

        $this->assertSame( $competition, $this->rendered[0]['vars']['club']->competition );
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
        $this->assertSame( [ 'inv-1' ], $this->rendered[0]['vars']['invoices'] );
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

        $GLOBALS['wp_stubs_teams']['first team'] = (object) [ 'id' => 3 ];
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

    // ---------------------------------------------------------------------
    // Facade and architecture guards
    // ---------------------------------------------------------------------

    public function test_shortcodes_club_facade_delegates_to_adapter(): void {
        $app       = $this->createStub( RacketManager::class );
        $container = new Simple_Container();
        $adapter   = $this->createMock( Club_Shortcode_Adapter::class );
        $adapter->expects( $this->once() )->method( 'show_clubs' )->with( [ 'template' => 'list' ] )->willReturn( '<div class="clubs"></div>' );
        $adapter->expects( $this->once() )->method( 'show_club_roles' )->with( [] )->willReturn( '<div class="roles"></div>' );
        $adapter->expects( $this->once() )->method( 'show_club_invoices' )->with( [ 'template' => 'x' ] )->willReturn( 'invoices' );

        $container->set( 'club_shortcode_adapter', fn() => $adapter );
        $app->container = $container;

        $facade = new Shortcodes_Club( $app );
        $this->assertSame( '<div class="clubs"></div>', $facade->show_clubs( [ 'template' => 'list' ] ) );
        $this->assertSame( '<div class="roles"></div>', $facade->show_club_roles( [] ) );
        $this->assertSame( 'invoices', $facade->show_club_invoices( [ 'template' => 'x' ] ) );
    }

    public function test_adapter_and_facade_have_no_legacy_cycle_or_service_locator(): void {
        $adapter = (string) file_get_contents( RACKETMANAGER_PATH . 'src/php/Infrastructure/Wordpress/Shortcodes/Club_Shortcode_Adapter.php' );
        $facade  = (string) file_get_contents( RACKETMANAGER_PATH . 'src/php/Public/Shortcodes_Club.php' );

        $this->assertStringNotContainsString( 'Racketmanager\\Public\\', $adapter );
        $this->assertStringNotContainsString( 'legacy_handler', $adapter );
        $this->assertStringNotContainsString( 'container', $adapter );
        $this->assertStringNotContainsString( 'load_template', $facade );
        $this->assertSame( 0, preg_match( '/\?(Club_Service|Get_\w+_Handler|Shortcode_View_Presenter)\s+\$/', $adapter ), 'Dependencies must be required' );
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function club(): Club {
        return new Club( (object) [
            'id'        => 10,
            'name'      => 'Leigh Tennis Club',
            'shortcode' => 'Leigh LTC',
            'single'    => true,
            'address'   => '123 Tennis Lane',
            'postcode'  => 'SS9 1AA',
            'latitude'  => '51.54',
            'longitude' => '0.65',
        ] );
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
     *                                        competition_repository, finance, renderer.
     */
    private function adapter( array $overrides = [] ): Club_Shortcode_Adapter {
        $club_service = $overrides['club_service'] ?? $this->createStub( Club_Service::class );
        $renderer     = $overrides['renderer'] ?? new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $players      = $overrides['players'] ?? $this->createStub( Player_Service::class );
        $registration = $overrides['registration'] ?? $this->createStub( Registration_Service::class );
        $competitions = $overrides['competition_service'] ?? $this->createStub( Competition_Service::class );
        $repository   = $overrides['competition_repository'] ?? $this->createStub( Competition_Repository_Interface::class );
        $finance      = $overrides['finance'] ?? $this->createStub( Finance_Service::class );

        $template_renderer = new Shortcode_Template_Renderer( $renderer, new Club_Presenter(), new Player_Presenter() );

        return new Club_Shortcode_Adapter(
            $club_service,
            new Get_Club_Handler( $club_service, $overrides['settings'] ?? null ),
            new Club_Shortcode_Presenter( $renderer, new Club_Presenter(), new Player_Presenter() ),
            new Get_Club_Players_Handler( $club_service, $players, $registration ),
            new Get_Club_Roles_Handler( $club_service ),
            new Get_Club_Role_Modal_Handler( $club_service ),
            new Get_Club_Competitions_Handler( $club_service, $competitions, $repository ),
            new Get_Club_Invoices_Handler( $club_service, $finance ),
            new Shortcode_View_Presenter( $template_renderer )
        );
    }
}
