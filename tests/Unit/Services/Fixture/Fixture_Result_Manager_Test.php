<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Fixture;

use LogicException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Domain\Enums\Fixture\Fixture_Update_Status;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Domain\Result\Result;
use Racketmanager\Presentation\Presenters\Notification_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Report_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition\Knockout_Progression_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Fixture\Fixture_Finalization_Service;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\Fixture\Service_Provider;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Result\Result_Reporting_Service;
use Racketmanager\Services\Result\Rubber_Result_Manager;
use Racketmanager\Services\Result_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\Standings\Standings_Service;
use Racketmanager\Services\Validator\Player_Validation_Service;
use Racketmanager\Services\Validator\Score_Validation_Service;
use Racketmanager\Services\View\View_Renderer_Interface;
use Throwable;

#[AllowMockObjectsWithoutExpectations]
class Fixture_Result_Manager_Test extends TestCase {

    private Fixture_Maintenance_Service|MockObject $fixture_maintenance_service;
    private Fixture_Finalization_Service|MockObject $finalization_service;
    private League_Repository_Interface|MockObject $league_repository;
    private League_Service|MockObject $league_service;
    private Result_Reporting_Service|MockObject $result_reporting_service;
    private Fixture_Permission_Service|MockObject $permission_service;
    private Score_Validation_Service|MockObject $score_validator;
    private Result_Service|MockObject $result_service;
    private Knockout_Progression_Service|MockObject $progression_service;
    private Rubber_Repository_Interface|MockObject $rubber_repository;
    private Fixture_Result_Manager $manager;

    public function test_reset_result_clears_rubbers(): void {
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( 123 );

        $rubber1 = $this->createMock( Rubber::class );
        $rubber1->method( 'get_id' )->willReturn( 10 );
        $rubber2 = $this->createMock( Rubber::class );
        $rubber2->method( 'get_id' )->willReturn( 20 );

        $this->rubber_repository->expects( $this->once() )
            ->method( 'find_by_fixture_id' )
            ->with( 123 )
            ->willReturn( [ $rubber1, $rubber2 ] );

        $rubber1->expects( $this->once() )->method( 'reset_result' );
        $rubber2->expects( $this->once() )->method( 'reset_result' );

        $this->rubber_repository->expects( $this->exactly( 2 ) )
            ->method( 'reset_result' )
            ->with( $this->logicalOr( 10, 20 ) );

        // Expect reset to be applied to fixture
        $this->result_service->expects( $this->once() )
            ->method( 'apply_to_fixture' )
            ->with( $fixture, $this->callback( function( $result ) {
                return $result instanceof Result && $result->is_reset();
            }));

        $this->manager->reset_result( $fixture );
    }

    public function test_update_result_triggers_league_standings_update(): void {
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_league_id' )->willReturn( 1 );
        $fixture->method( 'get_season' )->willReturn( '2026' );

        $result = $this->createMock( Result::class );

        $league = $this->createMock( League::class );
        $league->method( 'get_id' )->willReturn( 1 );
        $league->method( 'get_name' )->willReturn( 'Test League' );
        $league->method( 'get_event_id' )->willReturn( 10 );
        $league->is_championship = false;

        $this->league_repository->expects( $this->once() )->method( 'find_by_id' )->with( 1 )->willReturn( $league );

        $this->result_service->expects( $this->once() )->method( 'apply_to_fixture' )->with( $fixture, $result, 'Y' );

        $this->finalization_service->expects( $this->once() )->method( 'finalize' )
            ->with( $fixture, $league, $result, true, $this->league_repository )
            ->willReturn( [ Fixture_Update_Status::TABLE_UPDATED ] );

        $response = $this->manager->update_result( $fixture, $result, 'Y' );

        $this->assertContains( Fixture_Update_Status::SAVED, $response->outcomes );
        $this->assertContains( Fixture_Update_Status::TABLE_UPDATED, $response->outcomes );
    }

    public function test_update_result_triggers_championship_progression(): void {
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_league_id' )->willReturn( 1 );

        $result = $this->createMock( Result::class );

        $league = $this->createMock( League::class );
        $league->method( 'get_id' )->willReturn( 1 );
        $league->method( 'get_name' )->willReturn( 'Test Championship' );
        $league->method( 'get_event_id' )->willReturn( 10 );
        $league->is_championship = true;

        $this->league_repository->expects( $this->once() )->method( 'find_by_id' )->with( 1 )->willReturn( $league );

        $this->result_service->expects( $this->once() )->method( 'apply_to_fixture' )->with( $fixture, $result, 'Y' );

        $this->finalization_service->expects( $this->once() )->method( 'finalize' )
            ->with( $fixture, $league, $result, true, $this->league_repository )
            ->willReturn( [ Fixture_Update_Status::PROGRESSED ] );

        $response = $this->manager->update_result( $fixture, $result, 'Y' );

        $this->assertContains( Fixture_Update_Status::SAVED, $response->outcomes );
        $this->assertContains( Fixture_Update_Status::PROGRESSED, $response->outcomes );
    }

    public function test_handle_fixture_result_update_assigns_home_captain(): void {
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_league_id' )->willReturn( 1 );
        $fixture->method( 'get_home_approver' )->willReturn( null );

        $league = $this->createStub( League::class );
        $this->league_service->method( 'get_league' )->willReturn( $league );

        $this->permission_service->method( 'is_update_allowed' )->willReturn( (object) [
            'user_can_update' => true,
            'user_team'       => 'home',
            'user_type'       => 'admin'
        ] );

        // The global function get_current_user_id from wp-stubs.php returns 1
        $fixture->expects( $this->once() )->method( 'set_home_approver' )->with( '1' );

        $request = new Fixture_Result_Update_Request( fixture_id: 1, sets: [], match_status: 'played', confirmed: 'N' );

        $this->score_validator->method( 'get_err_msgs' )->willReturn( [] );

        $this->manager->handle_fixture_result_update( $fixture, $request );
    }

    public function test_confirm_result_manages_results_report(): void {
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( 100 );
        $fixture->method( 'get_confirmed' )->willReturn( 'Y' );

        $this->fixture_maintenance_service->expects( $this->once() )->method( 'delete_result_report' )->with( 100 );

        $this->result_reporting_service->expects( $this->once() )->method( 'report_result' )->with( $fixture )->willReturn( (object) [ 'some' => 'data' ] );

        $this->fixture_maintenance_service->expects( $this->once() )->method( 'save_result_report' )->with( 100, (object) [ 'some' => 'data' ] );

        $this->manager->confirm_result( $fixture, 'admin' );
    }

    protected function setUp(): void {
        $racketmanager = $this->createMock( RacketManager::class );
        $container     = new Simple_Container();
        $container->set( 'competition_service', $this->createMock( Competition_Service::class ) );
        $container->set( 'club_service', $this->createMock( Club_Service::class ) );
        $container->set( 'player_service', $this->createMock( Player_Service::class ) );
        $container->set( 'registration_service', $this->createMock( Registration_Service::class ) );
        $container->set( 'notification_presenter', $this->createMock( Notification_Presenter::class ) );
        $container->set( 'view_renderer', $this->createMock( View_Renderer_Interface::class ) );

        $racketmanager->container = $container;
        $GLOBALS['racketmanager'] = $racketmanager;

        $fixture_repository        = $this->createMock( Fixture_Repository_Interface::class );
        $results_report_repository = $this->createMock( Results_Report_Repository_Interface::class );
        $this->rubber_repository   = $this->createMock( Rubber_Repository_Interface::class );
        $this->league_repository   = $this->createMock( League_Repository_Interface::class );

        $repository_provider = $this->createStub( Repository_Provider::class );
        $repository_provider->method( 'get_fixture_repository' )->willReturn( $fixture_repository );
        $repository_provider->method( 'get_results_report_repository' )->willReturn( $results_report_repository );
        $repository_provider->method( 'get_rubber_repository' )->willReturn( $this->rubber_repository );
        $repository_provider->method( 'get_league_repository' )->willReturn( $this->league_repository );

        $this->league_service           = $this->createMock( League_Service::class );
        $this->result_reporting_service = $this->createMock( Result_Reporting_Service::class );
        $this->permission_service       = $this->createMock( Fixture_Permission_Service::class );
        $this->score_validator          = $this->createMock( Score_Validation_Service::class );
        $this->result_service           = $this->createMock( Result_Service::class );
        $this->progression_service      = $this->createMock( Knockout_Progression_Service::class );
        $this->finalization_service     = $this->createMock( Fixture_Finalization_Service::class );
        $this->fixture_maintenance_service = $this->createMock( Fixture_Maintenance_Service::class );

        $service_provider = $this->create_service_provider_with_test_doubles();

        $this->manager = new Fixture_Result_Manager( $service_provider, $repository_provider );
    }

    public function test_construct_happy_path_with_all_injected_services(): void {
        $service_provider = $this->create_stub_service_provider();

        $manager = new Fixture_Result_Manager( $service_provider, $this->createStub( Repository_Provider::class ) );
        $this->assertSame( $service_provider, $manager->get_service_provider() );
    }

    #[DataProvider( 'provide_missing_dependency_cases' )]
    public function test_construct_throws_when_required_service_is_missing( string $service_getter, string $expected_message ): void {
        $override = 'get_result_reporting_service' === $service_getter
            ? new LogicException( 'Result_Reporting_Service has not been configured.' )
            : null;

        $service_provider = $this->create_stub_service_provider( [
            $service_getter => $override,
        ] );

        $this->expectException( LogicException::class );
        $this->expectExceptionMessageIsOrContains( $expected_message );
        $manager = new Fixture_Result_Manager( $service_provider, $this->createStub( Repository_Provider::class ) );
        $this->fail( sprintf( 'Expected LogicException "%s" for %s, but %s was constructed.', $expected_message, $service_getter, $manager::class ) );
    }

    public static function provide_missing_dependency_cases(): array {
        return [
            'missing result reporting service' => [
                'get_result_reporting_service',
                'Result_Reporting_Service has not been configured',
            ],
            'missing result service'           => [
                'get_result_service',
                'Result_Service has not been configured',
            ],
            'missing progression service'      => [
                'get_progression_service',
                'Knockout_Progression_Service has not been configured',
            ],
            'missing finalization service'     => [
                'get_fixture_finalization_service',
                'Fixture_Finalization_Service has not been configured',
            ],
            'missing league service'           => [
                'get_league_service',
                'League_Service has not been configured',
            ],
            'missing score validator'          => [
                'get_score_validator',
                'Score_Validation_Service has not been configured',
            ],
            'missing settings service'         => [
                'get_settings_service',
                'Settings_Service has not been configured',
            ],
            'missing registration service'     => [
                'get_registration_service',
                'Registration_Service has not been configured',
            ],
            'missing player validator'         => [
                'get_player_validator',
                'Player_Validation_Service has not been configured',
            ],
            'missing notification service'     => [
                'get_notification_service',
                'Notification_Service has not been configured',
            ],
            'missing rubber manager'           => [
                'get_rubber_manager',
                'Rubber_Result_Manager has not been configured',
            ],
            'missing permission service'       => [
                'get_fixture_permission_service',
                'Fixture_Permission_Service has not been configured',
            ],
        ];
    }

    public function test_set_fixture_maintenance_service_sets_instance(): void {
        $service_provider = $this->create_service_provider_with_test_doubles( [
            'get_fixture_maintenance_service' => null,
        ] );

        $manager = new Fixture_Result_Manager( $service_provider, $this->createStub( Repository_Provider::class ) );
        $this->assertNull( $manager->get_fixture_maintenance_service() );

        $maintenance_service = $this->createMock( Fixture_Maintenance_Service::class );
        $manager->set_fixture_maintenance_service( $maintenance_service );

        $this->assertSame( $maintenance_service, $manager->get_fixture_maintenance_service() );

        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( 123 );
        $fixture->method( 'get_confirmed' )->willReturn( 'Y' );

        $report_data = (object) [ 'data' => true ];
        $this->result_reporting_service->expects( $this->once() )->method( 'report_result' )->with( $fixture )->willReturn( $report_data );

        $maintenance_service->expects( $this->once() )->method( 'delete_result_report' )->with( 123 );
        $maintenance_service->expects( $this->once() )->method( 'save_result_report' )->with( 123, $report_data );

        $manager->confirm_result( $fixture, 'admin' );
    }

    public function test_confirm_result_throws_when_maintenance_service_not_set(): void {
        $service_provider = $this->create_service_provider_with_test_doubles( [
            'get_fixture_maintenance_service' => null,
        ] );

        $manager = new Fixture_Result_Manager( $service_provider, $this->createStub( Repository_Provider::class ) );

        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( 123 );
        $fixture->method( 'get_confirmed' )->willReturn( 'Y' );

        $this->expectException( LogicException::class );
        $this->expectExceptionMessageIsOrContains( 'Fixture_Maintenance_Service has not been set.' );

        $manager->confirm_result( $fixture, 'admin' );
    }

    public static function batch_result_cases(): array {
        return [
            'confirmed league' => [ 'Y', false ],
            'pending league' => [ 'N', false ],
            'confirmed final' => [ 'Y', 'Final' ],
            'pending final' => [ 'N', 'Final' ],
        ];
    }

    #[DataProvider( 'batch_result_cases' )]
    public function test_batch_results_refresh_standings_only_for_non_finals( string $confirmed, false|string $final ): void {
        $fixture = Fixture_Factory::from_array( [ 'id' => 70, 'home_team' => '10', 'away_team' => '20', 'status' => 1 ] );
        $repository = $this->createMock( Fixture_Repository_Interface::class );
        $repository->expects( $this->exactly( 2 ) )->method( 'find_by_id' )->willReturnMap( [ [ 70, $fixture ], [ 71, null ] ] );
        $provider = $this->createStub( Repository_Provider::class );
        $provider->method( 'get_fixture_repository' )->willReturn( $repository );
        $standings = $this->createMock( Standings_Service::class );
        $league = League::from_object( (object) [ 'title' => 'Division 1' ] );
        $standings->expects( $final ? $this->never() : $this->once() )->method( 'update_standings' )->with( $league, '2026' );
        $manager = $this->getMockBuilder( Fixture_Result_Manager::class )
            ->setConstructorArgs( [ $this->create_stub_service_provider(), $provider, $standings ] )
            ->onlyMethods( [ 'confirm_result', 'update_result' ] )->getMock();
        $expected_result = $this->callback( static fn( Result $result ): bool =>
            3.0 === $result->get_home_points() && 1.0 === $result->get_away_points()
            && 'score entered' === $result->get_custom()['notes']
        );
        if ( 'Y' === $confirmed ) {
            $manager->expects( $this->once() )->method( 'confirm_result' )->with( $fixture, '', null, $expected_result );
            $manager->expects( $this->never() )->method( 'update_result' );
        } else {
            $manager->expects( $this->once() )->method( 'update_result' )->with( $fixture, $expected_result, 'N' );
            $manager->expects( $this->never() )->method( 'confirm_result' );
        }

        $this->assertSame( 1, $manager->update_match_results( $league, [ 70, 71 ], [ 70 => '3' ], [ 70 => '1' ], [ 70 => [ 'notes' => 'score entered' ] ], '2026', $final, $confirmed ) );
    }

    public function test_empty_batch_does_not_require_standings_or_query_repository(): void {
        $repository = $this->createMock( Fixture_Repository_Interface::class );
        $repository->expects( $this->never() )->method( 'find_by_id' );
        $league = League::from_object( (object) [ 'title' => 'Division 1' ] );

        $this->assertSame( 0, $this->manager->update_match_results( $league, [], [], [], [], '2026', fixture_repository: $repository ) );
    }

    public function test_batch_rejects_missing_standings_before_any_fixture_mutation(): void {
        $repository = $this->createMock( Fixture_Repository_Interface::class );
        $repository->expects( $this->never() )->method( 'find_by_id' );
        $this->result_service->expects( $this->never() )->method( 'apply_to_fixture' );
        $league = League::from_object( (object) [ 'title' => 'Division 1' ] );

        $this->expectException( LogicException::class );
        $this->expectExceptionMessage( 'Standings_Service has not been configured.' );
        $this->manager->update_match_results( $league, [ 70 ], [], [], [], '2026', fixture_repository: $repository );
    }

    public function test_reset_rejects_missing_standings_before_any_fixture_mutation(): void {
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_league_id' )->willReturn( 12 );
        $fixture->expects( $this->never() )->method( 'reset_result' );
        $this->result_service->expects( $this->never() )->method( 'apply_to_fixture' );
        $this->league_service->method( 'get_league' )->willReturn( League::from_object( (object) [ 'title' => 'Division 1' ] ) );

        $this->expectException( LogicException::class );
        $this->expectExceptionMessage( 'Standings_Service has not been configured.' );
        $this->manager->reset_result( $fixture );
    }

    private function create_service_provider_with_test_doubles( array $overrides = [] ): Service_Provider {
        $test_doubles = [
            'get_fixture_finalization_service' => $this->finalization_service,
            'get_fixture_maintenance_service'  => $this->fixture_maintenance_service,
            'get_league_service'               => $this->league_service,
            'get_result_reporting_service'     => $this->result_reporting_service,
            'get_fixture_permission_service'   => $this->permission_service,
            'get_score_validator'              => $this->score_validator,
            'get_result_service'               => $this->result_service,
            'get_progression_service'          => $this->progression_service,
        ];

        return $this->create_stub_service_provider( array_merge( $test_doubles, $overrides ) );
    }

    private function create_stub_service_provider( array $overrides = [] ): Service_Provider {
        $defaults = [
            'get_result_reporting_service'     => $this->createStub( Result_Reporting_Service::class ),
            'get_result_service'               => $this->createStub( Result_Service::class ),
            'get_progression_service'          => $this->createStub( Knockout_Progression_Service::class ),
            'get_fixture_finalization_service' => $this->createStub( Fixture_Finalization_Service::class ),
            'get_league_service'               => $this->createStub( League_Service::class ),
            'get_score_validator'              => $this->createStub( Score_Validation_Service::class ),
            'get_settings_service'             => $this->createStub( Settings_Service::class ),
            'get_registration_service'         => $this->createStub( Registration_Service::class ),
            'get_player_validator'             => $this->createStub( Player_Validation_Service::class ),
            'get_notification_service'         => $this->createStub( Notification_Service::class ),
            'get_rubber_manager'               => $this->createStub( Rubber_Result_Manager::class ),
            'get_fixture_permission_service'   => $this->createStub( Fixture_Permission_Service::class ),
            'get_fixture_maintenance_service'  => $this->createStub( Fixture_Maintenance_Service::class ),
        ];

        $configured       = array_merge( $defaults, $overrides );
        $service_provider = $this->createStub( Service_Provider::class );

        foreach ( $configured as $method => $return_value ) {
            if ( $return_value instanceof Throwable ) {
                $service_provider->method( $method )->willThrowException( $return_value );
            } else {
                $service_provider->method( $method )->willReturn( $return_value );
            }
        }

        return $service_provider;
    }
}
