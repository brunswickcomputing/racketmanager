<?php
/**
 * Admin Result delivery test
 *
 * @package RacketManager
 * @subpackage Unit/Controllers/Admin
 */

declare( strict_types=1 );

namespace Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Result;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Admin\Result\Results_Checker_Action_Dispatcher_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Season_Service;

require_once __DIR__ . '/../../../wp-stubs.php';

final class Admin_Result_Delivery_Test extends TestCase {

    private $redirector;
    private $flash_store;
    private $dispatcher;
    private $competition_service;
    private $season_service;
    private Admin_Result $admin_result;
    private array $saved_post;
    private array $saved_get;
    private array $saved_server;
    private mixed $saved_racketmanager = null;
    private array $pending_league_matches = array();
    private array $awaiting_matches = array();
    private array $challenged_matches = array();
    private array $approved_matches = array();
    private array $postponed_matches = array();
    private array $tournament_matches = array();
    private array $plugin_options = array();
    private array $recorded_queries = array();

    protected function setUp(): void {
        parent::setUp();

        $this->saved_post   = $_POST;
        $this->saved_get    = $_GET;
        $this->saved_server = $_SERVER;
        $this->saved_racketmanager = $GLOBALS['racketmanager'] ?? null;
        $this->pending_league_matches = array();
        $this->awaiting_matches       = array();
        $this->challenged_matches     = array();
        $this->approved_matches       = array();
        $this->postponed_matches      = array();
        $this->tournament_matches     = array();
        $this->plugin_options         = array();
        $this->recorded_queries       = array();

        $_POST   = array();
        $_GET    = array();
        $_SERVER = array();

        $GLOBALS['wp_stubs_current_user_can'] = true;

        $this->redirector          = $this->createMock( Admin_Redirector_Interface::class );
        $this->flash_store         = $this->createMock( Admin_Flash_Message_Store::class );
        $message_service           = new Admin_Message_Service( $this->flash_store );
        $action_adapter            = new Admin_Action_Adapter( $message_service, $this->redirector );
        $this->dispatcher          = $this->createMock( Results_Checker_Action_Dispatcher_Interface::class );
        $this->competition_service = $this->createStub( Competition_Service::class );
        $this->season_service      = $this->createStub( Season_Service::class );

        $container = new Simple_Container();
        $container->set( 'club_service', fn() => $this->createStub( \Racketmanager\Services\Club_Service::class ) );
        $container->set( 'player_service', fn() => $this->createStub( \Racketmanager\Services\Player_Service::class ) );
        $container->set( 'registration_service', fn() => $this->createStub( \Racketmanager\Services\Registration_Service::class ) );
        $container->set( 'team_service', fn() => $this->createStub( \Racketmanager\Services\Team_Service::class ) );
        $container->set( 'league_service', fn() => $this->createStub( \Racketmanager\Services\League_Service::class ) );
        $container->set( 'competition_service', fn() => $this->competition_service );
        $container->set( 'finance_service', fn() => $this->createStub( \Racketmanager\Services\Finance_Service::class ) );
        $container->set( 'season_service', fn() => $this->season_service );
        $container->set( 'tournament_service', fn() => $this->createStub( \Racketmanager\Services\Tournament_Service::class ) );
        $container->set( 'fixture_repository', fn() => $this->createStub( \Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface::class ) );
        $container->set( 'results_checker_presenter', fn() => $this->createStub( \Racketmanager\Presentation\Presenters\Results_Checker_Presenter::class ) );
        $container->set( 'admin_action_adapter', fn() => $action_adapter );
        $container->set( 'admin_message_service', fn() => $message_service );

        $container->set( 'league_contact_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Contact_Action_Dispatcher( $this->createStub( \Racketmanager\Services\League_Service::class ), $this->createStub( \Racketmanager\Services\Competition_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'league_setup_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Setup_Action_Dispatcher( $this->createStub( \Racketmanager\Services\Competition_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'event_setup_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\Event_Setup_Action_Dispatcher( $this->createStub( \Racketmanager\Services\Competition_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'season_delete_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\Season_Delete_Action_Dispatcher( $this->createStub( \Racketmanager\Services\Competition_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'league_team_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Team_Action_Dispatcher( $this->createStub( \Racketmanager\Services\League_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'league_match_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Match_Action_Dispatcher( $this->createStub( \Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface::class ), $this->createStub( \Racketmanager\Services\Fixture\Fixture_Maintenance_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'league_constitution_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Constitution_Action_Dispatcher( $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'league_event_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Event_Action_Dispatcher( $this->createStub( \Racketmanager\Services\League_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'league_schedule_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Schedule_Action_Dispatcher( new \Racketmanager\Services\Admin\League\League_Schedule_Service( $this->createStub( \Racketmanager\Services\Fixture\Fixture_Maintenance_Service::class ) ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'club_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Club\Club_Action_Dispatcher( $this->createStub( \Racketmanager\Services\Club_Service::class ), $this->createStub( \Racketmanager\Services\Player_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'club_player_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Club\Club_Player_Action_Dispatcher( $this->createStub( \Racketmanager\Services\Registration_Service::class ), $this->createStub( \Racketmanager\Services\Player_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'club_team_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Club\Club_Team_Action_Dispatcher( $this->createStub( \Racketmanager\Services\Club_Service::class ), $this->createStub( \Racketmanager\Services\Team_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'club_role_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Club\Club_Role_Action_Dispatcher( $this->createStub( \Racketmanager\Services\Club_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'season_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Season\Season_Action_Dispatcher( $this->createStub( \Racketmanager\Services\Season_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );

        $plugin = $this->createStub( RacketManager::class );
        $plugin->container = $container;
        $plugin->method( 'get_result_warnings' )->willReturn( array() );
        $plugin->method( 'get_options' )->willReturnCallback( fn() => $this->plugin_options );
        $plugin->method( 'get_matches' )->willReturnCallback(
            function ( array $args ): array {
                return $this->matches_for_args( $args );
            }
        );

        global $racketmanager;
        $racketmanager = $plugin;

        $this->admin_result = new Admin_Result(
            $plugin,
            $action_adapter,
            $this->dispatcher
        );
    }

    protected function tearDown(): void {
        $_POST   = $this->saved_post;
        $_GET    = $this->saved_get;
        $_SERVER = $this->saved_server;
        unset( $GLOBALS['wp_stubs_current_user_can'] );
        if ( null === $this->saved_racketmanager ) {
            unset( $GLOBALS['racketmanager'] );
        } else {
            $GLOBALS['racketmanager'] = $this->saved_racketmanager;
        }

        parent::tearDown();
    }

    public function test_post_moderation_redirects_with_success_flash_and_preserves_context_and_forces_resultschecker_tab(): void {
        $_SERVER['REQUEST_METHOD']    = 'POST';
        $_POST['doResultsChecker']    = '1';
        $_GET['season']               = '2024';
        $_GET['competition']          = '5';
        $_GET['event']                = '12';
        $_GET['filterResultsChecker'] = 'outstanding';
        $_GET['tab']                  = 'different_tab';

        $result = new Action_Result_DTO(
            message: 'Result check(s) approved',
            message_type: Admin_Message_Type::SUCCESS,
            tab_override: 'resultschecker'
        );
        $this->dispatcher->expects( $this->once() )
            ->method( 'handle' )
            ->with( $_POST )
            ->willReturn( $result );

        $this->flash_store->expects( $this->once() )
            ->method( 'set' )
            ->with( 'Result check(s) approved', false );
        $this->flash_store->expects( $this->never() )->method( 'pop' );

        $this->redirector->expects( $this->once() )
            ->method( 'redirect' )
            ->with(
                $this->callback(
                    function( string $url ): bool {
                        return str_contains( $url, 'tab=resultschecker' )
                            && str_contains( $url, 'season=2024' )
                            && str_contains( $url, 'competition=5' )
                            && str_contains( $url, 'event=12' )
                            && str_contains( $url, 'filterResultsChecker=outstanding' );
                    }
                )
            );

        ob_start();
        try {
            $this->admin_result->display_results_page();
            $output = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        $this->assertSame( '', $output );
    }

    public function test_post_moderation_guard_failure_redirects_with_error_flash_and_no_output(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['doResultsChecker'] = '1';

        $result = new Action_Result_DTO(
            message: 'Security token invalid',
            message_type: Admin_Message_Type::ERROR,
            tab_override: 'resultschecker'
        );
        $this->dispatcher->expects( $this->once() )
            ->method( 'handle' )
            ->willReturn( $result );

        $this->flash_store->expects( $this->once() )
            ->method( 'set' )
            ->with( 'Security token invalid', true );
        $this->flash_store->expects( $this->never() )->method( 'pop' );

        $this->redirector->expects( $this->once() )
            ->method( 'redirect' )
            ->with(
                $this->callback(
                    fn( string $url ) => str_contains( $url, 'tab=resultschecker' )
                )
            );

        ob_start();
        try {
            $this->admin_result->display_results_page();
            $output = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        $this->assertSame( '', $output );
    }

    public function test_get_displays_flashed_message_and_renders_page(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->dispatcher->expects( $this->never() )->method( 'handle' );
        $this->redirector->expects( $this->never() )->method( 'redirect' );
        $this->flash_store->expects( $this->never() )->method( 'set' );

        $this->flash_store->expects( $this->once() )
            ->method( 'pop' )
            ->willReturn(
                array(
                    'message'      => 'Result check(s) approved',
                    'message_type' => false,
                )
            );

        $this->competition_service->method( 'get_league_competitions' )->willReturn( array() );
        $this->season_service->method( 'get_all_seasons' )->willReturn( array() );

        ob_start();
        try {
            $this->admin_result->display_results_page();
            $output = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        $this->assertStringContainsString( 'Result check(s) approved', $output );
        $this->assertStringContainsString( 'Results Checker', $output );
        $this->assertStringNotContainsString( '; ?>', $output );
    }

    public function test_get_renders_pending_match_result_link_with_referrer(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->dispatcher->expects( $this->never() )->method( 'handle' );
        $this->redirector->expects( $this->never() )->method( 'redirect' );
        $this->flash_store->expects( $this->once() )->method( 'pop' )->willReturn( array() );
        $this->flash_store->expects( $this->never() )->method( 'set' );
        $this->competition_service->method( 'get_league_competitions' )->willReturn( array() );
        $this->season_service->method( 'get_all_seasons' )->willReturn( array() );

        $match                    = new \stdClass();
        $match->league_id         = 12;
        $match->date              = '2026-04-04 18:30:00';
        $match->match_title       = 'Home vs Away';
        $match->link              = 'https://example.test/match/home-vs-away/';
        $match->confirmed_display = 'Pending';
        $match->score             = '';
        $match->league            = (object) array( 'id' => 12, 'title' => 'Division 1' );
        $this->pending_league_matches = array( $match );

        ob_start();
        try {
            $this->admin_result->display_results_page();
            $output = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        $this->assertStringContainsString( 'Home vs Away', $output );
        $this->assertStringContainsString( 'https://example.test/match/home-vs-away/?referrer=pending-results', $output );
        $this->assertStringContainsString( 'https://example.test/match/home-vs-away/result/?referrer=pending-results', $output );
        $this->assertStringNotContainsString( '; ?>', $output );
    }

    public function test_get_executes_all_six_match_queries_with_expected_args_and_renders_all_tabs(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->plugin_options      = array( 'resultPending' => '1' );

        $this->dispatcher->expects( $this->never() )->method( 'handle' );
        $this->redirector->expects( $this->never() )->method( 'redirect' );
        $this->flash_store->expects( $this->once() )->method( 'pop' )->willReturn( array() );
        $this->competition_service->method( 'get_league_competitions' )->willReturn( array() );
        $this->season_service->method( 'get_all_seasons' )->willReturn( array() );

        $create_match = function ( string $name, int $id ): \stdClass {
            $match                      = new \stdClass();
            $match->id                  = $id;
            $match->league_id           = 12;
            $match->date                = '2026-04-04 18:30:00';
            $match->match_title         = $name;
            $slug                       = strtolower( str_replace( ' ', '-', $name ) );
            $match->link                = "https://example.test/match/{$slug}/";
            $match->confirmed_display   = 'Pending';
            $match->score               = '6-4 6-2';
            $match->league              = (object) array( 'id' => 12, 'title' => 'Division 1' );
            $match->result_overdue_date = '2026-04-01';
            $match->overdue_time        = '10:00';
            return $match;
        };

        $this->pending_league_matches = array( $create_match( 'Pending League Match', 101 ) );
        $this->awaiting_matches       = array( $create_match( 'Awaiting Confirmation Match', 102 ) );
        $this->challenged_matches     = array( $create_match( 'Challenged Result Match', 103 ) );
        $this->approved_matches       = array( $create_match( 'Approved Result Match', 104 ) );
        $this->postponed_matches      = array( $create_match( 'Postponed Match', 105 ) );
        $this->tournament_matches     = array( $create_match( 'Tournament Match', 106 ) );

        ob_start();
        try {
            $this->admin_result->display_results_page();
            $output = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        // Verify that 6 queries were executed.
        $this->assertCount( 6, $this->recorded_queries );

        $expected_orderby = array( 'updated' => 'ASC', 'id' => 'ASC' );

        // 1. Pending league query
        $this->assertSame( 'outstanding', $this->recorded_queries[0]['time'] ?? null );
        $this->assertSame( 'league', $this->recorded_queries[0]['competition_type'] ?? null );
        $this->assertSame( $expected_orderby, $this->recorded_queries[0]['orderby'] ?? null );
        $this->assertSame( '1', $this->recorded_queries[0]['resultPending'] ?? null );

        // 2. Awaiting confirmation query
        $this->assertSame( 'P', $this->recorded_queries[1]['status'] ?? null );
        $this->assertSame( $expected_orderby, $this->recorded_queries[1]['orderby'] ?? null );
        $this->assertArrayNotHasKey( 'resultPending', $this->recorded_queries[1] );

        // 3. Challenged results query
        $this->assertSame( 'C', $this->recorded_queries[2]['status'] ?? null );
        $this->assertSame( $expected_orderby, $this->recorded_queries[2]['orderby'] ?? null );
        $this->assertArrayNotHasKey( 'resultPending', $this->recorded_queries[2] );

        // 4. Approved results query
        $this->assertSame( 'A', $this->recorded_queries[3]['status'] ?? null );
        $this->assertSame( $expected_orderby, $this->recorded_queries[3]['orderby'] ?? null );
        $this->assertArrayNotHasKey( 'resultPending', $this->recorded_queries[3] );

        // 5. Postponed matches query
        $this->assertSame( 'D', $this->recorded_queries[4]['status'] ?? null );
        $this->assertSame( $expected_orderby, $this->recorded_queries[4]['orderby'] ?? null );
        $this->assertSame( '1', $this->recorded_queries[4]['resultPending'] ?? null );

        // 6. Tournament matches query
        $this->assertSame( 'outstanding', $this->recorded_queries[5]['time'] ?? null );
        $this->assertSame( 'tournament', $this->recorded_queries[5]['competition_type'] ?? null );
        $this->assertSame( $expected_orderby, $this->recorded_queries[5]['orderby'] ?? null );
        $this->assertSame( '1', $this->recorded_queries[5]['resultPending'] ?? null );

        // Verify rendering of all match rows with their respective referrers.
        $this->assertStringContainsString( 'Pending League Match', $output );
        $this->assertStringContainsString( 'https://example.test/match/pending-league-match/?referrer=pending-results', $output );
        $this->assertStringContainsString( 'https://example.test/match/pending-league-match/result/?referrer=pending-results', $output );

        $this->assertStringContainsString( 'Awaiting Confirmation Match', $output );
        $this->assertStringContainsString( 'https://example.test/match/awaiting-confirmation-match/?referrer=awaiting-confirmation', $output );
        $this->assertStringContainsString( 'https://example.test/match/awaiting-confirmation-match/result/?referrer=awaiting-confirmation', $output );

        $this->assertStringContainsString( 'Challenged Result Match', $output );
        $this->assertStringContainsString( 'https://example.test/match/challenged-result-match/?referrer=challenge-results', $output );
        $this->assertStringContainsString( 'https://example.test/match/challenged-result-match/result/?referrer=challenge-results', $output );

        $this->assertStringContainsString( 'Approved Result Match', $output );
        $this->assertStringContainsString( 'https://example.test/match/approved-result-match/?referrer=results', $output );
        $this->assertStringContainsString( 'https://example.test/match/approved-result-match/result/?referrer=results', $output );

        $this->assertStringContainsString( 'Postponed Match', $output );
        $this->assertStringContainsString( 'https://example.test/match/postponed-match/?referrer=postponed', $output );
        $this->assertStringContainsString( 'https://example.test/match/postponed-match/result/?referrer=postponed', $output );

        $this->assertStringContainsString( 'Tournament Match', $output );
        $this->assertStringContainsString( 'https://example.test/match/tournament-match/?referrer=tournament', $output );
        $this->assertStringNotContainsString( 'https://example.test/match/tournament-match/result/?referrer=tournament', $output );

        $this->assertStringNotContainsString( '; ?>', $output );
    }

    public function test_view_leagues_permission_denial_displays_no_permission_and_does_not_dispatch(): void {
        $GLOBALS['wp_stubs_current_user_can'] = false;
        $_POST['doResultsChecker']             = '1';

        $this->dispatcher->expects( $this->never() )->method( 'handle' );
        $this->redirector->expects( $this->never() )->method( 'redirect' );
        $this->flash_store->expects( $this->never() )->method( 'set' );
        $this->flash_store->expects( $this->never() )->method( 'pop' );

        ob_start();
        try {
            $this->admin_result->display_results_page();
            $output = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        $this->assertStringContainsString( 'You do not have permission', $output );
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return object[]
     */
    private function matches_for_args( array $args ): array {
        $this->recorded_queries[] = $args;

        if ( ( $args['time'] ?? '' ) === 'outstanding' && ( $args['competition_type'] ?? '' ) === 'league' ) {
            return $this->pending_league_matches;
        }

        if ( ( $args['status'] ?? '' ) === 'P' ) {
            return $this->awaiting_matches;
        }

        if ( ( $args['status'] ?? '' ) === 'C' ) {
            return $this->challenged_matches;
        }

        if ( ( $args['status'] ?? '' ) === 'A' ) {
            return $this->approved_matches;
        }

        if ( ( $args['status'] ?? '' ) === 'D' ) {
            return $this->postponed_matches;
        }

        if ( ( $args['time'] ?? '' ) === 'outstanding' && ( $args['competition_type'] ?? '' ) === 'tournament' ) {
            return $this->tournament_matches;
        }

        return array();
    }
}
