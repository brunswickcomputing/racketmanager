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
use Racketmanager\Domain\Tournament\Championship_Settings;
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
            renderer: new \Racketmanager\Services\View\Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ),
            flash_store: $this->flash_store,
        );

        $racketmanager            = $this->createMock( RacketManager::class );
        $racketmanager->container = $this->container;
        $GLOBALS['racketmanager'] = $racketmanager;

        $this->screen = $this->create_screen();
    }

    /**
     * Build the screen; query services are resolved from the container in the constructor,
     * so tests that rebind them must rebuild the screen afterwards.
     */
    private function create_screen(): Admin_Cup {
        return $this->container->get( 'admin_cup_screen' );
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

        $this->cup_setup_dispatcher->expects( self::never() )
            ->method( 'handle' );

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

        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_leagues' )
            ->willThrowException( new \Racketmanager\Exceptions\Invalid_Status_Exception( 'Permission denied' ) );
        $this->redirector->expects( self::never() )->method( 'redirect' );

        ob_start();
        $this->screen->handle_display( null );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'alert', $output );
        $this->assertStringContainsString( 'Permission denied', $output );
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

        $league            = $this->create_test_league( 12, $competition, 'Cup League' );
        $league_repository = $this->createMock( League_Repository_Interface::class );
        $league_repository->expects( self::once() )->method( 'find_by_id' )->with( 12 )->willReturn( $league );
        $league_repository->method( 'query_matches' )->willReturn( 0 );
        $this->bind_league_repository( $league_repository );

        ob_start();
        $this->screen->display_setup_event_page();
        $output = ob_get_clean();

        $this->assertNotEmpty( $output );
    }

    public function test_display_setup_event_page_without_league_does_not_hit_repository(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'setup-event', 'competition_id' => '5', 'season' => '2024' );

        $league_repository = $this->createMock( League_Repository_Interface::class );
        $league_repository->expects( self::never() )->method( 'find_by_id' );
        $this->bind_league_repository( $league_repository );

        ob_start();
        $this->screen->handle_display( 'setup-event' );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'League not found', $output );
    }

    public function test_display_cup_plan_page_resolves_venue_club_from_club_repository(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'plan', 'competition_id' => '5', 'season' => '2024' );

        $this->container->set( 'fixture_relation_loader', new Fixture_Relation_Loader( $this->createStub( League_Repository_Interface::class ) ) );

        $competition = Competition_Test_Factory::create(
            id: 5,
            name: 'Test Cup',
            type: \Racketmanager\Domain\Competition\Competition_Type::CUP,
            seasons: Competition_Test_Factory::season_map(
                '2024',
                array(
                    'venue'          => 7,
                    'match_dates'    => array( '2024-05-01' ),
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
        $this->competition_service->expects( self::once() )->method( 'get_competition_by_season' )->with( 5, 2024 )->willReturn( $competition );
        $this->competition_service->method( 'get_events_for_competition' )->willReturn( array() );
        $this->competition_service->method( 'get_matches_for_competition' )->willReturn( array() );
        $this->flash_store->method( 'pop' )->willReturn( array() );

        $club            = \Racketmanager\Domain\Club\Club::from_object( (object) array( 'id' => 7, 'name' => 'Venue Club', 'shortcode' => 'VEN' ) );
        $club_repository = $this->createMock( \Racketmanager\Repositories\Interfaces\Club_Repository_Interface::class );
        $club_repository->expects( self::once() )->method( 'find_by_id' )->with( 7 )->willReturn( $club );
        $this->container->set( 'club_repository', fn() => $club_repository );
        $this->screen = $this->create_screen();

        ob_start();
        try {
            $this->screen->display_cup_plan_page();
        } finally {
            $output = ob_get_clean();
        }

        $this->assertStringContainsString( 'VEN', $output );
    }

    private function create_test_league( int $id, Competition $competition, string $title ): League {
        $event = \Racketmanager\Domain\Competition\Event::from_object(
            (object) array(
                'id'      => 3,
                'name'    => 'Cup Event',
                'seasons' => array( '2024' => array( 'name' => '2024', 'match_dates' => array( '2024-05-01' ) ) ),
            ),
            $competition
        );
        $league = League::from_object( (object) array( 'id' => $id, 'title' => $title ), $event );
        $league->mode            = 'championship';
        $league->num_teams_total = 8;
        $league->set_season( '2024' );
        return $league;
    }

    /**
     * Rebind the league repository and rebuild the screen so the constructor picks it up.
     */
    private function bind_league_repository( League_Repository_Interface $league_repository ): void {
        $this->container->set( 'league_repository', fn() => $league_repository );
        $this->container->set( 'league_query_service', new \Racketmanager\Services\Competition\League_Query_Service(
            $league_repository,
            $this->createStub( \Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface::class ),
            $this->createStub( \Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface::class )
        ) );
        $this->screen = $this->create_screen();
    }

    public function test_display_cup_matches_page_renders_with_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'matches', 'competition_id' => '5', 'league' => '12', 'season' => '2024', 'final' => 'quarter' );

        $competition = $this->create_test_competition( 5, '2024' );
        $this->competition_service->expects( self::once() )->method( 'get_competition' )->with( 5 )->willReturn( $competition );

        $league            = $this->create_test_league( 12, $competition, 'Cup League' );
        $league_repository = $this->createMock( League_Repository_Interface::class );
        $league_repository->expects( self::once() )->method( 'find_by_id' )->with( 12 )->willReturn( $league );
        $league_repository->method( 'query_league_teams' )->willReturn( 8 );
        $league_repository->method( 'query_matches' )->willReturn( array() );
        $this->bind_league_repository( $league_repository );
        $this->container->get( 'league_query_service' )->set_season( $league, '2024', true );

        ob_start();
        try {
            $this->screen->display_cup_matches_page();
        } finally {
            $output = ob_get_clean();
        }

        $this->assertStringContainsString( 'page=racketmanager-cups', $output );
        $this->assertStringContainsString( 'Cup League', $output );
        $this->assertStringContainsString( 'Quarter Final', $output );
        $this->assertStringContainsString( 'view=draw&amp;competition_id=5&amp;season=2024&amp;league=12', $output );
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

        $champ_league                  = League::from_object( (object) array( 'id' => 12, 'title' => 'Men Championship Draw' ) );
        $champ_league->num_teams_total = 16;
        $champ_league->is_championship = true;
        $champ_league->championship    = $champ_obj;

        $non_champ_league                  = League::from_object( (object) array( 'id' => 13, 'title' => 'Ladies Plate Draw' ) );
        $non_champ_league->num_teams_total = 10;
        $non_champ_league->is_championship = false;

        $league_repository = $this->createStub( League_Repository_Interface::class );
        $league_repository->method( 'query_matches' )->willReturn( 14 );
        $this->container->set( 'league_query_service', new \Racketmanager\Services\Competition\League_Query_Service(
            $league_repository,
            $this->createStub( \Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface::class ),
            $this->createStub( \Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface::class )
        ) );
        $this->screen = $this->create_screen();

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

    public function test_handle_display_draw_renders_cleanly_with_warning_check(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'draw', 'competition_id' => '5', 'league' => '12', 'season' => '2024' );

        $competition = $this->create_test_competition( 5, '2024' );
        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );

        $league        = $this->create_test_league( 12, $competition, 'Cup League' );
        $champ         = new Championship(
            league_id: 12,
            is_consolation: false,
            settings: new Championship_Settings( groups: array( 'Group A' ) ),
            num_advance: 2,
            num_rounds: 1,
            num_teams: 8,
            num_teams_first_round: 8,
            num_seeds: 2,
            keys_by_round: array( 1 => 'final' ),
            finals_by_key: array( 'final' => array( 'key' => 'final', 'name' => 'Final', 'round' => 1 ) ),
            final_teams_by_round: array( 'final' => array() ),
            current_final: 'final'
        );
        $league->championship = $champ;

        $league_repository = $this->createStub( League_Repository_Interface::class );
        $league_repository->method( 'find_by_id' )->willReturn( $league );
        $league_repository->method( 'query_league_teams' )->willReturn( 8 );
        $league_repository->method( 'query_matches' )->willReturn( array() );
        $this->bind_league_repository( $league_repository );

        $warnings = array();
        set_error_handler(
            static function ( int $severity, string $message ) use ( &$warnings ): bool {
                $warnings[] = $message;
                return true;
            }
        );
        ob_start();
        try {
            $this->screen->handle_display( 'draw' );
        } finally {
            $output = (string) ob_get_clean();
            restore_error_handler();
        }

        $this->assertSame( array(), $warnings );
        $this->assertStringContainsString( "activaTab('finalResults')", $output );
        $this->assertStringContainsString( 'Cup League', $output );
        $this->assertStringContainsString( 'Test Cup', $output );
    }

    public function test_handle_display_match_renders_cleanly_with_warning_check(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'match', 'competition_id' => '5', 'league' => '12', 'season' => '2024', 'edit' => '101' );

        $competition = $this->create_test_competition( 5, '2024' );
        $this->competition_service->method( 'get_competition' )->willReturn( $competition );

        $league            = $this->create_test_league( 12, $competition, 'Cup League' );
        $league_repository = $this->createStub( League_Repository_Interface::class );
        $league_repository->method( 'find_by_id' )->willReturn( $league );
        $this->bind_league_repository( $league_repository );

        $fixture            = new \Racketmanager\Domain\Fixture\Fixture();
        $fixture->id        = 101;
        $fixture->home_team = 'W1';
        $fixture->away_team = 'W2';
        $fixture->date      = '2024-05-01';

        $fixture_repo = $this->createStub( \Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface::class );
        $fixture_repo->method( 'find_by_id' )->willReturn( $fixture );
        $this->container->set( 'fixture_repository', fn() => $fixture_repo );

        $warnings = array();
        set_error_handler(
            static function ( int $severity, string $message ) use ( &$warnings ): bool {
                $warnings[] = $message;
                return true;
            }
        );
        ob_start();
        try {
            $this->screen->handle_display( 'match' );
        } finally {
            $output = (string) ob_get_clean();
            restore_error_handler();
        }

        $this->assertSame( array(), $warnings );
        $this->assertStringContainsString( 'Edit Match', $output );
        $this->assertStringContainsString( 'action="/wp-admin/admin.php?page=racketmanager-cups&amp;view=draw&amp;competition_id=5&amp;league=12&amp;season=2024', $output );
    }

    public function test_handle_display_routes_teams_page(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'teams', 'league_id' => '12', 'season' => '2024' );

        $competition                     = $this->create_test_competition( 5, '2024' );
        $league                          = $this->create_test_league( 12, $competition, 'Cup League' );
        $GLOBALS['wp_stubs_leagues'][12] = $league;

        $team_service = $this->createStub( \Racketmanager\Services\Team_Service::class );
        $team_service->method( 'get_club_teams' )->willReturn( array() );
        $this->container->set( 'team_service', $team_service );

        $league_service = $this->createStub( \Racketmanager\Services\League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );
        $this->container->set( 'league_service', $league_service );

        $this->screen = $this->create_screen();

        ob_start();
        $this->screen->handle_display( 'teams' );
        $output = (string) ob_get_clean();

        $this->assertStringContainsString( 'Add Teams', $output );
        $this->assertStringContainsString( 'Cup League', $output );
    }

    public function test_handle_display_teams_post_redirects_to_draw_screen_with_teams_tab(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'teams', 'league_id' => '12', 'season' => '2024' );
        $_POST                     = array(
            'team'                => array( '1' ),
            'season'              => '2024',
            'league_id'           => '12',
            'racketmanager_nonce' => 'racketmanager_add-teams-bulk',
        );

        $competition                     = $this->create_test_competition( 5, '2024' );
        $league                          = $this->create_test_league( 12, $competition, 'Cup League' );
        $GLOBALS['wp_stubs_leagues'][12] = $league;

        $league_service = $this->createStub( \Racketmanager\Services\League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );
        $this->container->set( 'league_service', $league_service );

        $league_team_admin_service = $this->createMock( \Racketmanager\Services\Admin\League_Team_Admin_Service::class );
        $league_team_admin_service->expects( self::once() )
            ->method( 'add_teams' )
            ->with( $league, array( '1' ), 2024 )
            ->willReturn( array( 'count' => 1, 'messages' => array(), 'errors' => array() ) );
        $this->container->set( 'league_team_admin_service', $league_team_admin_service );

        $this->screen = $this->create_screen();

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with(
                '1 team added',
                false,
                self::anything()
            );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::callback( function ( string $url ) {
                return str_contains( $url, 'page=racketmanager-cups' )
                    && str_contains( $url, 'view=draw' )
                    && str_contains( $url, 'league=12' )
                    && str_contains( $url, 'tab=teams' );
            } ) );

        $this->screen->handle_display( 'teams' );
    }

    public function test_handle_display_routes_contact_page(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'contact', 'league_id' => '12', 'season' => '2024' );

        $competition                     = $this->create_test_competition( 5, '2024' );
        $league                          = $this->create_test_league( 12, $competition, 'Cup League' );
        $GLOBALS['wp_stubs_leagues'][12] = $league;

        $league_service = $this->createStub( \Racketmanager\Services\League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );
        $this->container->set( 'league_service', $league_service );

        $this->screen = $this->create_screen();

        ob_start();
        $this->screen->handle_display( 'contact' );
        $output = (string) ob_get_clean();

        $this->assertStringContainsString( '<form class="g-3 mt-3 form-control"', $output );
        $this->assertStringContainsString( 'name="contactTeamPreview"', $output );
    }

    public function test_handle_display_routes_seasons_page(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-cups', 'view' => 'seasons', 'competition_id' => '5' );

        $competition = $this->create_test_competition( 5, '2024' );
        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );

        $this->screen = $this->create_screen();

        ob_start();
        $this->screen->handle_display( 'seasons' );
        $output = (string) ob_get_clean();

        $this->assertStringContainsString( 'Seasons', $output );
    }

    public function test_handle_display_delegates_modify_and_config_to_competition_screen(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'competition_id' => '5', 'season' => '2024' );

        $competition = $this->create_test_competition( 5, '2024' );
        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );

        $this->screen = $this->create_screen();

        ob_start();
        $this->screen->handle_display( 'modify' );
        $modify_output = (string) ob_get_clean();

        $this->assertStringContainsString( 'Modify season', $modify_output );

        ob_start();
        $this->screen->handle_display( 'config' );
        $config_output = (string) ob_get_clean();

        $this->assertStringContainsString( 'Test Cup', $config_output );
        $this->assertStringContainsString( 'data-action="racketmanager_manage-competition-config"', $config_output );
    }

    public function test_handle_display_delegates_team_to_club_screen(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'edit' => '10' );

        $team         = $this->createStub( \Racketmanager\Domain\Team::class );
        $team_service = $this->createStub( \Racketmanager\Services\Team_Service::class );
        $team_service->method( 'get_team_by_id' )->willReturn( $team );
        $this->container->set( 'team_service', $team_service );

        $club_service = $this->createStub( \Racketmanager\Services\Club_Service::class );
        $club_service->method( 'get_clubs' )->willReturn( array() );
        $this->container->set( 'club_service', $club_service );

        $this->screen = $this->create_screen();

        ob_start();
        $this->screen->handle_display( 'team' );
        $output = (string) ob_get_clean();

        $this->assertStringContainsString( 'name="team_edit"', $output );
        $this->assertStringContainsString( 'name="updateLeague" value="team"', $output );
    }

    public function test_handle_display_delegates_event_config_to_event_screen(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'competition_id' => '5', 'event_id' => '3' );

        $competition = $this->create_test_competition( 5, '2024' );
        $event       = \Racketmanager\Domain\Competition\Event::from_object(
            (object) array( 'id' => 3, 'name' => 'Cup Event', 'settings' => array() ),
            $competition
        );

        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );
        $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

        $this->screen = $this->create_screen();

        ob_start();
        $this->screen->handle_display( 'event-config' );
        $output = (string) ob_get_clean();

        $this->assertStringContainsString( 'Cup Event - config', $output );
    }
}
