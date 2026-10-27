<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Fixture\Fixture_Hydration_DTO;
use Racketmanager\Domain\DTO\Player\Player_Hydration_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Season\Season;
use Racketmanager\Infrastructure\Wordpress\Assets\Asset_Manager;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Shortcode_Registrar;
use Racketmanager\Infrastructure\Wordpress\Templates\Template_Loader;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Season_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Mail\Mail_Filter_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Result\Result_Reporting_Service;
use Racketmanager\Services\Seo\Page_Title_Service;
use Racketmanager\Services\Validator\Player_Validation_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

final class RacketManager_Facade_Delegation_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Simple_Container $container;
    private RacketManager $facade;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->container = new Simple_Container();
        // Constructing RacketManager loads template-tags.php, which clashes with test stubs.
        $this->facade            = ( new \ReflectionClass( RacketManager::class ) )->newInstanceWithoutConstructor();
        $this->facade->container = $this->container;
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_get_league_standings_delegates_to_league_repository(): void {
        $mock = $this->createMock( League_Repository_Interface::class );
        $mock->expects( $this->once() )
            ->method( 'get_league_standings' )
            ->with( [ 'season' => '2026' ] )
            ->willReturn( [ (object) [ 'team_id' => 1, 'points' => 10 ] ] );

        $this->container->set( 'league_repository', fn() => $mock );

        $result = $this->facade->get_league_standings( [ 'season' => '2026' ] );
        $this->assertCount( 1, $result );
        $this->assertSame( 1, $result[0]->team_id );
    }

    public function test_get_season_delegates_to_season_repository(): void {
        $mock = $this->createMock( Season_Repository_Interface::class );
        $mock->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 7, 'id' )
            ->willReturn( Season::from_object( (object) [ 'id' => 7, 'name' => '2026' ] ) );

        $this->container->set( 'season_repository', fn() => $mock );

        $result = $this->facade->get_season( [ 'id' => 7 ] );
        $this->assertInstanceOf( stdClass::class, $result );
        $this->assertSame( 7, $result->id );
        $this->assertSame( '2026', $result->name );
    }

    public function test_get_season_returns_false_when_id_and_name_do_not_match(): void {
        $mock = $this->createStub( Season_Repository_Interface::class );
        $mock->method( 'find_by_id' )->willReturn( Season::from_object( (object) [ 'id' => 7, 'name' => '2026' ] ) );
        $this->container->set( 'season_repository', fn() => $mock );

        $this->assertFalse( $this->facade->get_season( [ 'id' => 7, 'name' => '2025' ] ) );
        $this->assertFalse( $this->facade->get_season( [] ) );
    }

    public function test_get_clubs_delegates_to_club_repository(): void {
        $mock = $this->createMock( Club_Repository_Interface::class );
        $mock->expects( $this->once() )
            ->method( 'find_all' )
            ->with( [ 'type' => false ] )
            ->willReturn( [ (object) [ 'id' => 5 ] ] );

        $this->container->set( 'club_repository', fn() => $mock );

        $result = $this->facade->get_clubs();
        $this->assertCount( 1, $result );
        $this->assertSame( 5, $result[0]->id );
    }

    public function test_get_clubs_passes_custom_arguments_to_club_repository(): void {
        $mock = $this->createMock( Club_Repository_Interface::class );
        $mock->expects( $this->once() )
            ->method( 'find_all' )
            ->with( [ 'type' => 'affiliated' ] )
            ->willReturn( [ (object) [ 'id' => 10 ] ] );

        $this->container->set( 'club_repository', fn() => $mock );

        $result = $this->facade->get_clubs( [ 'type' => 'affiliated' ] );
        $this->assertCount( 1, $result );
        $this->assertSame( 10, $result[0]->id );
    }

    public function test_get_events_delegates_to_event_repository(): void {
        $mock = $this->createMock( Event_Repository_Interface::class );
        $mock->expects( $this->once() )
            ->method( 'find_all' )
            ->with( [ 'season' => '2026' ] )
            ->willReturn( [ (object) [ 'id' => 12 ] ] );

        $this->container->set( 'event_repository', fn() => $mock );

        $result = $this->facade->get_events( [ 'season' => '2026' ] );
        $this->assertCount( 1, $result );
        $this->assertSame( 12, $result[0]->id );
    }

    public function test_get_leagues_delegates_to_league_repository(): void {
        $mock = $this->createMock( League_Repository_Interface::class );
        $mock->expects( $this->once() )
            ->method( 'find_all' )
            ->with( [ 'name' => 'Premier' ] )
            ->willReturn( [ (object) [ 'id' => 45 ] ] );

        $this->container->set( 'league_repository', fn() => $mock );

        $result = $this->facade->get_leagues( [ 'name' => 'Premier' ] );
        $this->assertCount( 1, $result );
        $this->assertSame( 45, $result[0]->id );
    }

    public function test_get_team_id_delegates_to_team_repository(): void {
        $mock = $this->createMock( Team_Repository_Interface::class );
        $mock->expects( $this->once() )
            ->method( 'find_id_by_title' )
            ->with( 'Leigh 1' )
            ->willReturn( 99 );

        $this->container->set( 'team_repository', fn() => $mock );

        $result = $this->facade->get_team_id( 'Leigh 1' );
        $this->assertSame( 99, $result );
    }

    public function test_is_player_in_club_delegates_to_club_repository(): void {
        $mock = $this->createMock( Club_Repository_Interface::class );
        $mock->expects( $this->once() )
            ->method( 'is_player_in_club' )
            ->with( [ 'player' => 10, 'team' => 2 ] )
            ->willReturn( true );

        $this->container->set( 'club_repository', fn() => $mock );

        $result = $this->facade->is_player_in_club( [ 'player' => 10, 'team' => 2 ] );
        $this->assertTrue( $result );
    }

    public function test_get_all_players_active_uses_rubber_players_only(): void {
        $mock = $this->createMock( Player_Repository_Interface::class );
        $mock->expects( $this->never() )->method( 'find_all' );
        $mock->expects( $this->never() )->method( 'get_active_players' );
        $mock->expects( $this->once() )
            ->method( 'get_active_rubber_players' )
            ->willReturn( [ (object) [ 'id' => 101 ] ] );

        $this->container->set( 'player_repository', fn() => $mock );

        $result = $this->facade->get_all_players( [ 'active' => true ] );
        $this->assertCount( 1, $result );
        $this->assertSame( 101, $result[0]->id );
    }

    public function test_get_all_players_without_active_delegates_to_find_all(): void {
        $mock = $this->createMock( Player_Repository_Interface::class );
        $mock->expects( $this->once() )
            ->method( 'find_all' )
            ->with( [ 'name' => 'Jo' ] )
            ->willReturn( [ (object) [ 'id' => 5 ] ] );
        $mock->expects( $this->never() )->method( 'get_active_rubber_players' );
        $this->container->set( 'player_repository', fn() => $mock );

        $this->assertSame( 5, $this->facade->get_all_players( [ 'name' => 'Jo' ] )[0]->id );
    }

    public function test_get_matches_delegates_to_fixture_repository(): void {
        $mock = $this->createMock( Fixture_Repository_Interface::class );
        $mock->expects( $this->once() )
            ->method( 'find_all' )
            ->with( [ 'season' => '2026' ] )
            ->willReturn( [ (object) [ 'id' => 88 ] ] );

        $this->container->set( 'fixture_repository', fn() => $mock );

        $result = $this->facade->get_matches( [ 'season' => '2026' ] );
        $this->assertCount( 1, $result );
        $this->assertSame( 88, $result[0]->id );
    }

    public function test_get_winners_delegates_to_competition_repository(): void {
        $mock = $this->createMock( Competition_Repository_Interface::class );
        $mock->expects( $this->once() )
            ->method( 'get_winners' )
            ->with( '2026', 15, 'tournament', false )
            ->willReturn( [ (object) [ 'id' => 10, 'winner' => 'Team A' ] ] );

        $this->container->set( 'competition_repository', fn() => $mock );

        $result = $this->facade->get_winners( '2026', 15, 'tournament', false );
        $this->assertIsArray( $result );
        $this->assertCount( 1, $result );
        $this->assertSame( 'Team A', $result[0]->winner );
    }

    public function test_get_result_warnings_delegates_to_results_checker_repository(): void {
        $mock = $this->createMock( Results_Checker_Repository_Interface::class );
        $mock->expects( $this->once() )
            ->method( 'find_all' )
            ->with( [ 'season' => '2026' ] )
            ->willReturn( [ (object) [ 'id' => 1 ] ] );

        $this->container->set( 'results_checker_repository', fn() => $mock );

        $result = $this->facade->get_result_warnings( [ 'season' => '2026' ] );
        $this->assertCount( 1, $result );
        $this->assertSame( 1, $result[0]->id );
    }

    public function test_get_teams_delegates_to_team_repository(): void {
        $mock = $this->createMock( Team_Repository_Interface::class );
        $mock->expects( $this->once() )
            ->method( 'find_all' )
            ->with( [ 'player' => 5 ] )
            ->willReturn( [ (object) [ 'id' => 20 ] ] );

        $this->container->set( 'team_repository', fn() => $mock );

        $result = $this->facade->get_teams( [ 'player' => 5 ] );
        $this->assertCount( 1, $result );
        $this->assertSame( 20, $result[0]->id );
    }

    public function test_validate_player_delegates_to_player_validation_service(): void {
        $mock = $this->createMock( Player_Validation_Service::class );
        $player = new stdClass();
        $player->firstname = 'Jane';
        $input = [ 'firstname' => 'Jane', 'surname' => 'Doe' ];
        $mock->expects( $this->once() )
            ->method( 'validate_player_details' )
            ->with( $input )
            ->willReturn( [ true, $player ] );

        $this->container->set( 'player_validation_service', fn() => $mock );

        $result = $this->facade->validate_player( $input );
        $this->assertTrue( $result[0] );
        $this->assertSame( 'Jane', $result[1]->firstname );
    }

    public function test_validate_player_no_argument_uses_post_data(): void {
        $_POST = [
            'firstname' => 'Alice',
            'surname'   => 'Smith',
            'gender'    => 'F',
            'email'     => 'alice@example.com',
        ];

        $validator_service = new Player_Validation_Service(
            $this->createStub( \Racketmanager\Services\Registration_Service::class ),
            $this->createStub( \Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface::class )
        );
        $this->container->set( 'player_validation_service', fn() => $validator_service );

        $result = $this->facade->validate_player();
        $this->assertTrue( $result[0] );
        $this->assertSame( 'Alice', $result[1]->firstname );
        $this->assertSame( 'Smith', $result[1]->surname );
        $this->assertSame( '', $result[1]->contactno );

        unset( $_POST['firstname'], $_POST['surname'], $_POST['gender'], $_POST['email'] );
    }

    public function test_validate_player_explicit_empty_array_does_not_fall_back_to_post(): void {
        $_POST = [
            'firstname' => 'Alice',
            'surname'   => 'Smith',
            'gender'    => 'F',
            'email'     => 'alice@example.com',
        ];

        $validator_service = new Player_Validation_Service(
            $this->createStub( \Racketmanager\Services\Registration_Service::class ),
            $this->createStub( \Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface::class )
        );
        $this->container->set( 'player_validation_service', fn() => $validator_service );

        $result = $this->facade->validate_player( [] );
        $this->assertFalse( $result[0] );

        unset( $_POST['firstname'], $_POST['surname'], $_POST['gender'], $_POST['email'] );
    }

    public function test_set_page_title_delegates_to_page_title_service(): void {
        $mock = $this->createMock( Page_Title_Service::class );
        $this->facade->site_name = 'My Club Site';
        $mock->expects( $this->once() )
            ->method( 'build_title' )
            ->with( 'Initial Title', 'My Club Site' )
            ->willReturn( 'Dynamic Page Title - My Club Site' );

        $this->container->set( 'page_title_service', fn() => $mock );

        $result = $this->facade->set_page_title( 'Initial Title' );
        $this->assertSame( 'Dynamic Page Title - My Club Site', $result );
    }

    public function test_racketmanager_mail_delegates_to_mail_filter_service(): void {
        $mock = $this->createMock( Mail_Filter_Service::class );
        $payload = [
            'to'      => 'user@example.com',
            'subject' => 'Subject',
            'message' => 'Message',
            'headers' => [],
        ];
        $filtered = array_merge( $payload, [ 'headers' => [ 'Content-Type: text/html; charset=UTF-8' ] ] );

        $mock->expects( $this->once() )
            ->method( 'filter_mail' )
            ->with( $payload )
            ->willReturn( $filtered );

        $this->container->set( 'mail_filter_service', fn() => $mock );

        $result = $this->facade->racketmanager_mail( $payload );
        $this->assertSame( $filtered, $result );
    }

    public function test_chase_pending_results_delegates_to_pending_result_orchestration_service(): void {
        $mock = $this->createMock( \Racketmanager\Services\Fixture\Pending_Result_Orchestrator_Interface::class );
        $mock->expects( $this->once() )
            ->method( 'chase_pending_results' )
            ->with( 'league' );

        $this->container->set( 'pending_result_orchestration_service', fn() => $mock );

        $this->facade->chase_pending_results( 'league' );
    }

    public function test_chase_pending_approvals_delegates_to_pending_result_orchestration_service(): void {
        $mock = $this->createMock( \Racketmanager\Services\Fixture\Pending_Result_Orchestrator_Interface::class );
        $mock->expects( $this->once() )
            ->method( 'chase_pending_approvals' )
            ->with( 'tournament' );

        $this->container->set( 'pending_result_orchestration_service', fn() => $mock );

        $this->facade->chase_pending_approvals( 'tournament' );
    }

    public function test_init_components_delegates_to_component_coordinator(): void {
        $mock = $this->createMock( \Racketmanager\Infrastructure\Wordpress\Component\Component_Coordinator_Interface::class );
        $mock->expects( $this->once() )
            ->method( 'init_components' )
            ->with( $this->facade );

        $this->container->set( 'component_coordinator', fn() => $mock );

        $this->facade->init_components();
    }

    public function test_handle_exports_delegates_to_component_coordinator(): void {
        $mock = $this->createMock( \Racketmanager\Infrastructure\Wordpress\Component\Component_Coordinator_Interface::class );
        $mock->expects( $this->once() )
            ->method( 'handle_exports' )
            ->with( $this->facade );

        $this->container->set( 'component_coordinator', fn() => $mock );

        $this->facade->handle_exports();
    }

    public function test_resolve_service_returns_container_instance_when_registered(): void {
        $stub = $this->createStub( League_Repository_Interface::class );
        $this->container->set( 'league_repository', fn() => $stub );

        $resolved = $this->facade->resolve_service( 'league_repository' );
        $this->assertSame( $stub, $resolved );
    }

    public function test_resolve_service_throws_exception_when_service_not_registered(): void {
        $this->expectException( \RuntimeException::class );
        $this->expectExceptionMessage( 'Service "non_existent_key" is not available in the container.' );
        $this->facade->resolve_service( 'non_existent_key' );
    }

    public function test_resolve_service_throws_exception_when_container_not_set(): void {
        $facade_without_container = ( new \ReflectionClass( RacketManager::class ) )->newInstanceWithoutConstructor();
        $this->expectException( \RuntimeException::class );
        $this->expectExceptionMessage( 'Service "league_repository" is not available in the container.' );
        $facade_without_container->resolve_service( 'league_repository' );
    }

    public function test_get_player_name_delegates_to_player_repository(): void {
        $mock = $this->createMock( Player_Repository_Interface::class );
        $player = new Player( new Player_Hydration_DTO( id: 42, display_name: 'Roger Federer' ) );
        $mock->expects( $this->once() )
            ->method( 'find' )
            ->with( 42 )
            ->willReturn( $player );

        $this->container->set( 'player_repository', fn() => $mock );

        $result = $this->facade->get_player_name( 42 );
        $this->assertSame( 'Roger Federer', $result );
    }

    public function test_get_player_name_returns_false_when_not_found(): void {
        $mock = $this->createMock( Player_Repository_Interface::class );
        $mock->expects( $this->once() )
            ->method( 'find' )
            ->with( 999 )
            ->willReturn( null );

        $this->container->set( 'player_repository', fn() => $mock );

        $this->assertFalse( $this->facade->get_player_name( 999 ) );
    }

    public function test_get_confirmation_email_reads_from_options(): void {
        $mock_option_service = $this->createStub( Option_Service::class );
        $mock_option_service->method( 'get_options' )
            ->willReturn( [
                'league' => [ 'resultConfirmationEmail' => 'league_sec@example.com' ],
            ] );

        $this->facade->option_service = $mock_option_service;

        $email = $this->facade->get_confirmation_email( 'league' );
        $this->assertSame( 'league_sec@example.com', $email );

        $empty_email = $this->facade->get_confirmation_email( 'nonexistent' );
        $this->assertSame( '', $empty_email );
    }

    public function test_options_delegations_to_option_service(): void {
        $mock_option_service = $this->createMock( Option_Service::class );
        $mock_option_service->expects( $this->exactly( 3 ) )
            ->method( 'get_options' )
            ->willReturnCallback( function( $index = false ) {
                if ( 'custom_key' === $index ) {
                    return [ 'data' => 123 ];
                }
                return [ 'synced' => true ];
            } );

        $mock_option_service->expects( $this->once() )
            ->method( 'set_options' )
            ->with( 'custom_key', [ 'data' => 456 ] );

        $mock_option_service->expects( $this->once() )
            ->method( 'update_plugin_options' )
            ->with( [ 'all' => 'options' ] );

        $this->facade->option_service = $mock_option_service;

        $opts = $this->facade->get_options( 'custom_key' );
        $this->assertSame( [ 'data' => 123 ], $opts );

        $this->facade->set_options( 'custom_key', [ 'data' => 456 ] );
        $this->assertSame( [ 'synced' => true ], $this->facade->options );

        $this->facade->update_plugin_options( [ 'all' => 'options' ] );
        $this->assertSame( [ 'synced' => true ], $this->facade->options );
    }

    public function test_calculate_player_ratings_delegates_to_player_service(): void {
        $mock = $this->createMock( Player_Service::class );
        $mock->expects( $this->once() )
            ->method( 'calculate_player_ratings' )
            ->with( 10 );

        $this->container->set( 'player_service', fn() => $mock );

        $this->facade->calculate_player_ratings( 10 );
    }

    public function test_report_fixture_result_delegates_to_reporting_service(): void {
        $mock_fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture = new Fixture( new Fixture_Hydration_DTO( id: 123 ) );
        $mock_fixture_repo->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 123 )
            ->willReturn( $fixture );

        $mock_reporting = $this->createMock( Result_Reporting_Service::class );
        $mock_reporting->expects( $this->once() )
            ->method( 'report_result' )
            ->with( $fixture, 'league' );

        $this->container->set( 'fixture_repository', fn() => $mock_fixture_repo );
        $this->container->set( 'result_reporting_service', fn() => $mock_reporting );

        $this->facade->report_fixture_result( 123, 'league' );
    }

    public function test_set_message_updates_message_and_error_state(): void {
        $this->facade->set_message( 'Operation failed', true );
        $this->assertSame( 'Operation failed', $this->facade->message );
        $this->assertSame( 'error', $this->facade->error );

        $this->facade->set_message( 'Warning note', 'warning' );
        $this->assertSame( 'Warning note', $this->facade->message );
        $this->assertSame( 'warning', $this->facade->error );

        $this->facade->set_message( 'Info note', 'info' );
        $this->assertSame( 'Info note', $this->facade->message );
        $this->assertSame( 'info', $this->facade->error );

        $this->facade->set_message( 'Success note', false );
        $this->assertSame( 'Success note', $this->facade->message );
        $this->assertFalse( $this->facade->error );
    }

    public function test_read_directory_scans_directory_for_php_files(): void {
        $temp_dir = sys_get_temp_dir() . '/rm_test_' . uniqid();
        mkdir( $temp_dir );
        file_put_contents( $temp_dir . '/file1.php', '<?php' );
        file_put_contents( $temp_dir . '/file2.txt', 'text' );

        $files = $this->facade->read_directory( $temp_dir );
        $this->assertArrayHasKey( 'file1.php', $files );
        $this->assertArrayNotHasKey( 'file2.txt', $files );

        unlink( $temp_dir . '/file1.php' );
        unlink( $temp_dir . '/file2.txt' );
        rmdir( $temp_dir );
    }

    public function test_load_shortcodes_delegates_to_shortcode_registrar(): void {
        $mock = $this->createMock( Shortcode_Registrar::class );
        $mock->expects( $this->once() )->method( 'register' );

        $this->container->set( 'shortcode_registrar', fn() => $mock );

        $this->facade->load_shortcodes();
    }

    public function test_racketmanager_register_exporter(): void {
        $initial   = [ 'existing' => [ 'data' ] ];
        $exporters = $this->facade->racketmanager_register_exporter( $initial );

        $this->assertArrayHasKey( 'racketmanager_exporter', $exporters );
        $this->assertSame( 'Racketmanager exporter', $exporters['racketmanager_exporter']['exporter_friendly_name'] );
        $this->assertSame( [ 'Racketmanager\Services\Privacy_Exporters', 'user_data_exporter' ], $exporters['racketmanager_exporter']['callback'] );
    }

    public function test_asset_delegations_to_asset_manager(): void {
        $mock = $this->createMock( Asset_Manager::class );
        $this->facade->currency_code = 'EUR';
        $this->facade->asset_manager = $mock;

        $mock->expects( $this->once() )
            ->method( 'load_frontend_scripts' )
            ->with( 'EUR' );

        $mock->expects( $this->once() )
            ->method( 'load_frontend_styles' );

        $this->facade->load_scripts();
        $this->facade->load_styles();
    }

    public function test_template_delegations_to_template_loader(): void {
        $mock = $this->createMock( Template_Loader::class );
        $this->facade->template_loader = $mock;

        $mock->expects( $this->once() )->method( 'register_hooks' );
        $mock->expects( $this->once() )->method( 'get_templates' )->willReturn( [ 't1.php' => 'Template 1' ] );
        $mock->expects( $this->once() )->method( 'racketmanager_templates_as_option' )->with( [ 't' => 'test' ], null, null )->willReturn( [ 't' => 'test', 't1' => 'Template 1' ] );
        $mock->expects( $this->once() )->method( 'register_racketmanager_templates' )->with( [ 'page' => 1 ] )->willReturn( [ 'page' => 1 ] );
        $mock->expects( $this->once() )->method( 'racketmanager_load_template' )->with( 'page.php' )->willReturn( 'custom-page.php' );
        $mock->expects( $this->once() )->method( 'racketmanager_archive_template' )->with( 'archive.php' )->willReturn( 'custom-archive.php' );

        $this->facade->add_racketmanager_templates();
        $this->facade->racketmanager_templates_as_option( [ 't' => 'test' ] );
        $this->facade->register_racketmanager_templates( [ 'page' => 1 ] );
        $this->assertSame( 'custom-page.php', $this->facade->racketmanager_load_template( 'page.php' ) );
        $this->assertSame( 'custom-archive.php', $this->facade->racketmanager_archive_template( 'archive.php' ) );
    }

    public function test_is_user_favourite_returns_false_when_logged_out(): void {
        $GLOBALS['wp_stubs_is_user_logged_in'] = false;
        $this->assertFalse( $this->facade->is_user_favourite( 'club', 5 ) );
    }

    public function test_is_user_favourite_checks_user_meta_when_logged_in(): void {
        $GLOBALS['wp_stubs_is_user_logged_in'] = true;
        $GLOBALS['wp_stubs_get_current_user_id'] = 42;
        $GLOBALS['wp_stubs_user_meta'] = [
            42 => [
                'favourite-club' => [ '5', '12' ],
            ],
        ];

        $this->assertTrue( $this->facade->is_user_favourite( 'club', 5 ) );
        $this->assertTrue( $this->facade->is_user_favourite( 'club', 12 ) );
        $this->assertFalse( $this->facade->is_user_favourite( 'club', 99 ) );
    }
}
