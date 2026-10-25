<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Cup;
use Racketmanager\Admin\Presenters\Admin_Plan_Presenter;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Fixture\Fixture_Relation_Loader;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Competition\Competition_Overview_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Response_DTO;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Championship\Draw_Action_Dispatcher;
use Racketmanager\Services\Admin\Cup\Cup_Plan_Action_Dispatcher;
use Racketmanager\Services\Admin\Cup\Cup_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Creates_Admin_Delivery_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;

#[AllowMockObjectsWithoutExpectations]
final class Admin_Cup_Delivery_Test extends TestCase {
    use Creates_Admin_Delivery_Container;
    use Manages_Wordpress_Globals;

    private Simple_Container $container;
    private Admin_Redirector_Interface $redirector;
    private View_Renderer_Interface $renderer;
    private Admin_Flash_Message_Store $flash_store;
    private Action_Guard_Interface $guard;
    private Competition_Service $competition_service;
    private Cup_Setup_Action_Dispatcher $cup_setup_dispatcher;
    private Cup_Plan_Action_Dispatcher $cup_plan_dispatcher;
    private Draw_Action_Dispatcher $draw_action_dispatcher;
    private Admin_Cup $screen;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_current_user_can'] = true;

        $this->redirector             = $this->createMock( Admin_Redirector_Interface::class );
        $this->renderer               = $this->createMock( View_Renderer_Interface::class );
        $this->flash_store            = $this->createMock( Admin_Flash_Message_Store::class );
        $this->guard                  = $this->createMock( Action_Guard_Interface::class );
        $this->competition_service    = $this->createMock( Competition_Service::class );
        $this->cup_setup_dispatcher   = $this->createMock( Cup_Setup_Action_Dispatcher::class );
        $this->cup_plan_dispatcher    = $this->createMock( Cup_Plan_Action_Dispatcher::class );
        $this->draw_action_dispatcher = $this->createMock( Draw_Action_Dispatcher::class );

        $this->container = $this->create_admin_delivery_container(
            services: array(
                'competition_service'         => $this->competition_service,
                'cup_setup_action_dispatcher' => $this->cup_setup_dispatcher,
                'cup_plan_action_dispatcher'  => $this->cup_plan_dispatcher,
                'draw_action_dispatcher'      => $this->draw_action_dispatcher,
            ),
            guard: $this->guard,
            redirector: $this->redirector,
            renderer: $this->renderer,
            flash_store: $this->flash_store,
        );

        $racketmanager            = $this->createMock( RacketManager::class );
        $racketmanager->container = $this->container;
        $GLOBALS['racketmanager'] = $racketmanager;

        $this->screen = new Admin_Cup(
            $racketmanager,
            $this->container->get( 'admin_action_adapter' ),
            $this->cup_setup_dispatcher,
            $this->cup_plan_dispatcher,
            $this->draw_action_dispatcher,
            new Admin_Plan_Presenter(
                $this->createStub( Fixture_Detail_Service::class ),
                $this->createStub( Player_Repository_Interface::class ),
                new Fixture_Relation_Loader()
            )
        );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_setup_page_post_dispatches_and_redirects(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'setup', 'competition_id' => '5', 'season' => '2024' );
        $_POST                     = array( 'rank' => 'calculate_rank', 'season' => '2024', 'competition_id' => '5' );

        $this->cup_setup_dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->with( $_GET, $_POST )
            ->willReturn( new Action_Result_DTO( 'Cup ratings set', Admin_Message_Type::SUCCESS ) );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with(
                'Cup ratings set',
                false,
                self::anything()
            );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::callback( function ( string $url ) {
                return str_contains( $url, 'page=racketmanager-cups' )
                    && str_contains( $url, 'view=setup' )
                    && str_contains( $url, 'competition_id=5' )
                    && str_contains( $url, 'season=2024' );
            } ) );

        $this->screen->display_cup_setup_page();
    }

    public function test_setup_event_page_post_dispatches_and_redirects(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'setup-event', 'competition_id' => '5', 'league' => '12', 'season' => '2024' );
        $_POST                     = array( 'action' => 'add', 'league_id' => '12', 'season' => '2024', 'rounds' => array() );

        $this->cup_setup_dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->with( $_GET, $_POST )
            ->willReturn( new Action_Result_DTO( 'Fixtures added', Admin_Message_Type::SUCCESS ) );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with(
                'Fixtures added',
                false,
                self::anything()
            );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::callback( function ( string $url ) {
                return str_contains( $url, 'page=racketmanager-cups' )
                    && str_contains( $url, 'view=setup-event' )
                    && str_contains( $url, 'competition_id=5' )
                    && str_contains( $url, 'league=12' );
            } ) );

        $this->screen->display_setup_event_page();
    }

    public function test_plan_page_post_save_cup_dispatches_and_redirects_with_tab(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'plan', 'competition_id' => '5', 'season' => '2024' );
        $_POST                     = array( 'saveCup' => 'Save', 'startTime' => '10:00' );

        $this->cup_plan_dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->with( $_GET, $_POST )
            ->willReturn( new Action_Result_DTO( 'Plan updated', Admin_Message_Type::SUCCESS, tab_override: 'config' ) );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with(
                'Plan updated',
                false,
                self::anything()
            );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::callback( function ( string $url ) {
                return str_contains( $url, 'page=racketmanager-cups' )
                    && str_contains( $url, 'view=plan' )
                    && str_contains( $url, 'tab=config' );
            } ) );

        $this->screen->display_cup_plan_page();
    }

    public function test_draw_page_post_dispatches_and_redirects(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'draw', 'competition_id' => '5', 'league' => '12', 'season' => '2024' );
        $_POST                     = array( 'saveRanking' => 'Save', 'season' => '2024' );

        $this->draw_action_dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->willReturn( new Draw_Action_Response_DTO(
                message: 'Team ranking saved',
                message_type: Admin_Message_Type::SUCCESS,
                tab_override: 'preliminary'
            ) );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with(
                'Team ranking saved',
                false,
                self::anything()
            );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::callback( function ( string $url ) {
                return str_contains( $url, 'page=racketmanager-cups' )
                    && str_contains( $url, 'view=draw' )
                    && str_contains( $url, 'tab=preliminary' );
            } ) );

        $this->screen->display_cup_draw_page();
    }

    public function test_setup_page_guard_failure_redirects_with_error_flash(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'setup', 'competition_id' => '5', 'season' => '2024' );
        $_POST                     = array( 'rank' => 'calculate_rank', 'season' => '2024', 'competition_id' => '5' );

        $this->cup_setup_dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->with( $_GET, $_POST )
            ->willReturn( new Action_Result_DTO( 'Forbidden', Admin_Message_Type::ERROR ) );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with(
                'Forbidden',
                true,
                self::anything()
            );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::callback( function ( string $url ) {
                return str_contains( $url, 'page=racketmanager-cups' )
                    && str_contains( $url, 'view=setup' );
            } ) );

        $this->screen->display_cup_setup_page();
    }

    public function test_plan_page_guard_failure_redirects_with_error_flash_and_tab(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'plan', 'competition_id' => '5', 'season' => '2024' );
        $_POST                     = array( 'saveCup' => 'Save' );

        $this->cup_plan_dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->with( $_GET, $_POST )
            ->willReturn( new Action_Result_DTO( 'Permission denied', Admin_Message_Type::ERROR, tab_override: 'config' ) );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with(
                'Permission denied',
                true,
                self::anything()
            );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::callback( function ( string $url ) {
                return str_contains( $url, 'page=racketmanager-cups' )
                    && str_contains( $url, 'view=plan' )
                    && str_contains( $url, 'tab=config' );
            } ) );

        $this->screen->display_cup_plan_page();
    }

    public function test_draw_page_guard_failure_redirects_with_error_flash(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'draw', 'competition_id' => '5', 'league' => '12', 'season' => '2024' );
        $_POST                     = array( 'saveRanking' => 'Save', 'season' => '2024' );

        $this->draw_action_dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->willReturn( new Draw_Action_Response_DTO(
                message: 'Invalid nonce',
                message_type: Admin_Message_Type::ERROR
            ) );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with(
                'Invalid nonce',
                true,
                self::anything()
            );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::callback( function ( string $url ) {
                return str_contains( $url, 'page=racketmanager-cups' )
                    && str_contains( $url, 'view=draw' );
            } ) );

        $this->screen->display_cup_draw_page();
    }

    private function create_test_competition( int $id = 5, string $season = '2024' ): Competition {
        return Competition_Test_Factory::create(
            id: $id,
            name: 'Test Cup',
            type: \Racketmanager\Domain\Competition\Competition_Type::CUP,
            seasons: Competition_Test_Factory::season_map(
                $season,
                array(
                    'date_start'     => '2024-05-01',
                    'date_end'       => '2024-09-01',
                    'date_closing'   => '2024-04-15',
                    'round_length'   => 7,
                    'match_dates'    => array( '2024-05-01', '2024-06-01', '2024-07-01' ),
                    'starttime'      => '10:00',
                    'time_increment' => '01:00:00',
                    'num_courts'     => 4,
                )
            ),
            settings: array( 'fixed_match_dates' => false ),
            finals: array(
                array(
                    'round'       => 1,
                    'name'        => 'Final',
                    'num_matches' => 1,
                ),
            )
        );
    }

    public function test_unhandled_post_does_not_redirect(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'setup', 'competition_id' => '5', 'season' => '2024' );
        $_POST                     = array( 'unknown_field' => 'value' );

        $competition = $this->create_test_competition( 5, '2024' );
        $this->competition_service->expects( self::once() )->method( 'get_by_id' )->with( 5 )->willReturn( $competition );

        $this->cup_setup_dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->willReturn( new Action_Result_DTO() );

        $this->redirector->expects( self::never() )->method( 'redirect' );
        $this->flash_store->expects( self::never() )->method( 'set' );

        ob_start();
        $this->screen->display_cup_setup_page();
        $output = ob_get_clean();

        $this->assertNotEmpty( $output );
    }

    public function test_display_cups_page_capability_denied(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups' );

        $GLOBALS['wp_stubs_current_user_can'] = false;
        $this->redirector->expects( self::never() )->method( 'redirect' );

        ob_start();
        $this->screen->display_cups_page();
        $output = ob_get_clean();

        $this->assertStringContainsString( 'alert', $output );
    }

    public function test_display_cups_page_renders_cleanly(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups' );

        $GLOBALS['wp_stubs_current_user_can'] = true;

        ob_start();
        $this->screen->display_cups_page();
        $output = ob_get_clean();

        $this->assertNotEmpty( $output );
    }

    public function test_display_cup_overview_page_renders_with_season(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'overview', 'competition_id' => '5', 'season' => '2024' );

        $competition = $this->create_test_competition( 5, '2024' );
        $this->competition_service->expects( self::once() )->method( 'get_competition_by_season' )->with( 5, 2024 )->willReturn( $competition );
        $this->competition_service->expects( self::once() )->method( 'get_events_for_competition' )->with( 5, 2024 )->willReturn( array() );
        $this->competition_service->expects( self::once() )->method( 'get_clubs_for_competition' )->with( 5, 2024 )->willReturn( array() );
        $this->competition_service->expects( self::once() )->method( 'get_competition_overview' )->with( 5, 2024 )->willReturn( null );

        ob_start();
        $this->screen->display_cup_overview_page();
        $output = ob_get_clean();

        $this->assertNotEmpty( $output );
    }

    public function test_display_cup_setup_page_renders_with_season(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'setup', 'competition_id' => '5', 'season' => '2024' );

        $competition = $this->create_test_competition( 5, '2024' );
        $this->competition_service->expects( self::once() )->method( 'get_by_id' )->with( 5 )->willReturn( $competition );

        ob_start();
        $this->screen->display_cup_setup_page();
        $output = ob_get_clean();

        $this->assertNotEmpty( $output );
    }

    public function test_display_setup_event_page_renders_with_season(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'setup-event', 'competition_id' => '5', 'league' => '12', 'season' => '2024' );

        $competition = $this->create_test_competition( 5, '2024' );
        $this->competition_service->expects( self::once() )->method( 'get_by_id' )->with( 5 )->willReturn( $competition );

        $event = new class {
            public int $offset = 0;
            public $competition;
            public function get_season_by_name( string $name ): array {
                return array( 'match_dates' => array( '2024-05-01' ) );
            }
        };

        $league = new class( $event, $competition ) {
            public int $id = 12;
            public $event;
            public $competition;
            public array $seasons = array();
            public function __construct( $event, $competition ) {
                $this->event = $event;
                $this->event->competition = $competition;
                $this->competition = $competition;
            }
            public function get_matches( array $args = array() ): int {
                return 0;
            }
        };

        $GLOBALS['wp_stubs_leagues'][12] = $league;

        ob_start();
        $this->screen->display_setup_event_page();
        $output = ob_get_clean();

        $this->assertNotEmpty( $output );
    }

    public function test_display_cup_matches_page_renders_via_legacy_locals_fallback(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'matches', 'competition_id' => '5', 'league' => '12', 'season' => '2024', 'final' => 'quarter' );

        $competition = $this->create_test_competition( 5, '2024' );
        $this->competition_service->expects( self::once() )->method( 'get_competition' )->with( 5 )->willReturn( $competition );

        $event_competition = (object) array(
            'id'            => 5,
            'name'          => 'Test Cup',
            'type'          => \Racketmanager\Domain\Competition\Competition_Type::CUP,
            'is_cup'        => true,
            'is_league'     => false,
            'is_tournament' => false,
            'is_team_entry' => true,
        );
        $event             = new class( $event_competition ) {
            public int $id = 3;
            public string $name = 'Cup Event';
            public $competition;
            public function __construct( $competition ) {
                $this->competition = $competition;
            }
            public function get_season_by_name( string $name ): array {
                return array();
            }
        };
        $championship      = new class {
            public bool $is_consolation = false;
            public int $num_teams_first_round = 8;
            public function get_finals( $key ): array {
                return array( 'num_matches' => 0, 'round' => 1, 'key' => $key );
            }
            public function get_final_teams( $key ): array {
                return array();
            }
        };
        $league            = new class( $event, $championship ) {
            public int $id = 12;
            public string $title = 'Cup League';
            public string $sport = 'tennis';
            public bool $is_championship = true;
            public array $current_season = array( 'name' => '2024' );
            public $event;
            public $championship;
            public function __construct( $event, $championship ) {
                $this->event        = $event;
                $this->championship = $championship;
            }
            public function get_matches( array $args = array(), ?\Racketmanager\Repositories\Interfaces\Event_Repository_Interface $event_repository = null, ?\Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface $fixture_repository = null, ?\Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface $rubber_repository = null ): array {
                return array( (object) array( 'id' => 77, 'date' => '2024-05-01 10:00:00', 'home_team' => 1, 'away_team' => 2, 'location' => '', 'final_round' => 'quarter' ) );
            }
        };

        $GLOBALS['wp_stubs_leagues'][12] = $league;

        ob_start();
        try {
            $this->screen->display_cup_matches_page();
        } finally {
            $output = ob_get_clean();
        }

        $this->assertStringContainsString( 'page=racketmanager-cups', $output );
        $this->assertStringContainsString( 'Cup League', $output );
        $this->assertStringContainsString( 'Quarter Final', $output );
        $this->assertStringContainsString( 'view=draw&amp;competition_id=5&amp;league=12&amp;season=2024', $output );
    }

    public function test_display_cup_plan_page_renders_with_season_and_flash_errors(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'plan', 'competition_id' => '5', 'season' => '2024' );

        $this->container->set( 'fixture_relation_loader', new Fixture_Relation_Loader( $this->createStub( League_Repository_Interface::class ) ) );

        $competition = $this->create_test_competition( 5, '2024' );
        $this->competition_service->expects( self::once() )->method( 'get_competition_by_season' )->with( 5, 2024 )->willReturn( $competition );
        $this->competition_service->expects( self::once() )->method( 'get_events_for_competition' )->with( 5, 2024 )->willReturn( array() );
        $this->competition_service->expects( self::once() )->method( 'get_matches_for_competition' )->with( $competition, array( 'season' => 2024, 'final' => 'final' ) )->willReturn( array() );

        $this->flash_store->method( 'pop' )->willReturn( array(
            'message'      => 'Error with plan details',
            'message_type' => 'error',
            'flash_data'   => array(
                'err_flds'  => array( 'start_time', 'timeIncrement' ),
                'err_msgs'  => array( 'Start time required', 'Time increment required' ),
                'form_data' => array(
                    'startTime'           => '11:30',
                    'timeIncrement'       => '02:00:00',
                    'numCourtsAvailable'  => 6,
                ),
            ),
        ) );

        ob_start();
        try {
            $this->screen->display_cup_plan_page();
        } finally {
            $output = ob_get_clean();
        }

        $this->assertStringContainsString( 'is-invalid', $output );
        $this->assertStringContainsString( 'Start time required', $output );
        $this->assertStringContainsString( '11:30', $output );
    }

    public function test_display_cup_overview_page_renders_entries_with_entry_urls(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'overview', 'competition_id' => '5', 'season' => '2024' );

        $competition = $this->create_test_competition( 5, '2024' );
        $overview    = new Competition_Overview_DTO(
            (object) array(
                'id'          => 5,
                'name'        => 'Test Cup',
                'settings'    => wp_json_encode( array( 'competition_code' => 'TC' ) ),
                'num_events'  => 0,
                'num_teams'   => 0,
                'num_clubs'   => 1,
                'num_players' => 0,
            )
        );
        $entries = array(
            (object) array(
                'id'        => 1,
                'shortcode' => 'WTC',
            ),
        );

        $this->competition_service->expects( self::once() )
            ->method( 'get_competition_by_season' )
            ->with( 5, 2024 )
            ->willReturn( $competition );
        $this->competition_service->expects( self::once() )
            ->method( 'get_events_for_competition' )
            ->with( 5, 2024 )
            ->willReturn( array() );
        $this->competition_service->expects( self::once() )
            ->method( 'get_competition_overview' )
            ->with( 5, 2024 )
            ->willReturn( $overview );
        $this->competition_service->expects( self::once() )
            ->method( 'get_clubs_for_competition' )
            ->with( 5, 2024 )
            ->willReturn( $entries );

        $warnings = array();
        set_error_handler(
            static function ( int $severity, string $message ) use ( &$warnings ): bool {
                $warnings[] = $message;
                return true;
            }
        );
        ob_start();
        try {
            $this->screen->display_cup_overview_page();
        } finally {
            $output = (string) ob_get_clean();
            restore_error_handler();
        }

        $this->assertSame( array(), $warnings );
        $this->assertStringContainsString( 'WTC', $output );
        $this->assertStringContainsString( '/entry-form/test-cup/2024/wtc/', $output );
    }

    public function test_display_cup_overview_page_renders_events_with_leagues_and_draw_size(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'overview', 'competition_id' => '5', 'season' => '2024' );

        $competition = $this->create_test_competition( 5, '2024' );
        $overview    = new Competition_Overview_DTO(
            (object) array(
                'id'          => 5,
                'name'        => 'Test Cup',
                'settings'    => wp_json_encode( array( 'competition_code' => 'TC' ) ),
                'num_events'  => 1,
                'num_teams'   => 16,
                'num_clubs'   => 4,
                'num_players' => 32,
            )
        );

        $champ_ref = new \ReflectionClass( Championship::class );
        $champ_obj = $champ_ref->newInstanceWithoutConstructor();
        $prop      = $champ_ref->getProperty( 'num_teams_first_round' );
        $prop->setAccessible( true );
        $prop->setValue( $champ_obj, 8 );

        $champ_league                  = new League( (object) array( 'id' => 12, 'title' => 'Men Championship Draw' ) );
        $champ_league->num_teams_total = 16;
        $champ_league->is_championship = true;
        $champ_league->championship    = $champ_obj;

        $non_champ_league                  = new League( (object) array( 'id' => 13, 'title' => 'Ladies Plate Draw' ) );
        $non_champ_league->num_teams_total = 10;
        $non_champ_league->is_championship = false;

        $league_repository = $this->createStub( League_Repository_Interface::class );
        $league_repository->method( 'query_matches' )->willReturn( 14 );
        $this->container->set( 'league_query_service', new \Racketmanager\Services\Competition\League_Query_Service(
            $league_repository,
            $this->createStub( \Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface::class ),
            $this->createStub( \Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface::class )
        ) );

        $event = new class( array( $champ_league, $non_champ_league ) ) {
            public array $leagues = array();
            private array $leagues_list;
            public function __construct( array $leagues_list ) {
                $this->leagues_list = $leagues_list;
            }
            public function get_leagues( array $args = [], ?\Racketmanager\Repositories\Interfaces\Event_Repository_Interface $event_repository = null ): array {
                return $this->leagues_list;
            }
        };

        $this->competition_service->expects( self::once() )
            ->method( 'get_competition_by_season' )
            ->with( 5, 2024 )
            ->willReturn( $competition );
        $this->competition_service->expects( self::once() )
            ->method( 'get_events_for_competition' )
            ->with( 5, 2024 )
            ->willReturn( array( $event ) );
        $this->competition_service->expects( self::once() )
            ->method( 'get_competition_overview' )
            ->with( 5, 2024 )
            ->willReturn( $overview );
        $this->competition_service->expects( self::once() )
            ->method( 'get_clubs_for_competition' )
            ->with( 5, 2024 )
            ->willReturn( array() );

        $warnings = array();
        set_error_handler(
            static function ( int $severity, string $message ) use ( &$warnings ): bool {
                $warnings[] = $message;
                return true;
            }
        );
        ob_start();
        try {
            $this->screen->display_cup_overview_page();
        } finally {
            $output = (string) ob_get_clean();
            restore_error_handler();
        }

        $this->assertSame( array(), $warnings );
        $this->assertStringContainsString( 'Men Championship Draw', $output );
        $this->assertStringContainsString( 'view=draw&competition_id=5&league=12&season=2024', $output );
        $this->assertStringContainsString( 'Ladies Plate Draw', $output );
        $this->assertStringContainsString( 'view=draw&competition_id=5&league=13&season=2024', $output );
        $this->assertMatchesRegularExpression( '#<td>\s*16\s*</td>#', $output );
        $this->assertMatchesRegularExpression( '#<td>\s*8\s*</td>#', $output );
        $this->assertMatchesRegularExpression( '#<td>\s*10\s*</td>#', $output );
        $this->assertMatchesRegularExpression( '#<td>\s*14\s*</td>#', $output );
    }
}
