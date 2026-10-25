<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Fixture;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Services\Fixture\Pending_Result_Orchestration_Service;

require_once dirname( __DIR__, 3 ) . '/wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Pending_Result_Orchestration_Service_Test extends TestCase {

    private Option_Service $option_service;
    private Fixture_Repository_Interface $fixture_repository;
    private Fixture_Maintenance_Service $fixture_maintenance_service;
    private Pending_Result_Orchestration_Service $service;

    protected function setUp(): void {
        parent::setUp();

        $this->option_service              = $this->createMock( Option_Service::class );
        $this->fixture_repository          = $this->createMock( Fixture_Repository_Interface::class );
        $this->fixture_maintenance_service = $this->createMock( Fixture_Maintenance_Service::class );

        $this->service = new Pending_Result_Orchestration_Service(
            $this->option_service,
            $this->fixture_repository,
            $this->fixture_maintenance_service
        );
    }

    public function test_chase_pending_results_queries_matches_and_chases_each_fixture(): void {
        $this->option_service->expects( self::once() )->method( 'get_options' )->with( 'league' )->willReturn( [
            'resultPending' => '3',
            'resultPenalty' => '2',
            'resultTimeout' => '10',
        ] );

        $match1 = (object) [ 'id' => 101 ];
        $match2 = (object) [ 'id' => 102 ];

        $this->fixture_repository->expects( self::once() )
            ->method( 'find_all' )
            ->with( [
                'time'             => 'outstanding',
                'competition_type' => 'league',
                'orderby'          => [
                    'date' => 'ASC',
                    'id'   => 'ASC',
                ],
                'timeOffset'       => '3',
            ] )
            ->willReturn( [ $match1, $match2 ] );

        $fixture1 = $this->createStub( Fixture::class );
        $fixture2 = $this->createStub( Fixture::class );

        $this->fixture_repository->expects( self::exactly( 2 ) )
            ->method( 'find_by_id' )
            ->willReturnMap( [
                [ 101, $fixture1 ],
                [ 102, $fixture2 ],
            ] );

        $this->fixture_maintenance_service->expects( self::exactly( 2 ) )
            ->method( 'chase_match_result' )
            ->willReturnCallback( function ( Fixture $fixture, string $pending, string $timeout, string $penalty ) use ( $fixture1, $fixture2 ) {
                self::assertContains( $fixture, [ $fixture1, $fixture2 ] );
                self::assertSame( '3', $pending );
                self::assertSame( '10', $timeout );
                self::assertSame( '2', $penalty );
                return true;
            } );

        $this->service->chase_pending_results( 'league' );
    }

    public function test_chase_pending_results_handles_empty_matches(): void {
        $this->option_service->expects( self::once() )->method( 'get_options' )->with( 'tournament' )->willReturn( [
            'resultPending' => '5',
            'resultPenalty' => null,
            'resultTimeout' => '14',
        ] );

        $this->fixture_repository->expects( self::once() )
            ->method( 'find_all' )
            ->willReturn( [] );

        $this->fixture_repository->expects( self::never() )->method( 'find_by_id' );
        $this->fixture_maintenance_service->expects( self::never() )->method( 'chase_match_result' );

        $this->service->chase_pending_results( 'tournament' );
    }

    public function test_chase_pending_approvals_completes_results_and_chases_when_required(): void {
        $this->option_service->expects( self::once() )->method( 'get_options' )->with( 'league' )->willReturn( [
            'confirmationTimeout'  => '7',
            'confirmationRequired' => true,
            'confirmationPending'  => '2',
            'confirmationPenalty'  => '1',
        ] );

        $match_timeout = (object) [ 'id' => 201 ];
        $match_pending = (object) [ 'id' => 202 ];

        $this->fixture_repository->expects( self::exactly( 2 ) )
            ->method( 'find_all' )
            ->willReturnCallback( function ( array $args ) use ( $match_timeout, $match_pending ) {
                if ( isset( $args['orderby']['date'] ) ) {
                    self::assertSame( '7', $args['timeOffset'] );
                    return [ $match_timeout ];
                }
                self::assertSame( '2', $args['timeOffset'] );
                self::assertSame( [ 'updated' => 'ASC', 'id' => 'ASC' ], $args['orderby'] );
                return [ $match_pending ];
            } );

        $fixture_timeout = $this->createStub( Fixture::class );
        $fixture_pending = $this->createStub( Fixture::class );

        $this->fixture_repository->expects( self::exactly( 2 ) )
            ->method( 'find_by_id' )
            ->willReturnMap( [
                [ 201, $fixture_timeout ],
                [ 202, $fixture_pending ],
            ] );

        $this->fixture_maintenance_service->expects( self::once() )
            ->method( 'complete_result' )
            ->with( $fixture_timeout, 7 );

        $this->fixture_maintenance_service->expects( self::once() )
            ->method( 'chase_match_approval' )
            ->with( $fixture_pending, '2', false, '7', '1' );

        $this->service->chase_pending_approvals( 'league' );
    }

    public function test_chase_pending_approvals_skips_chasing_when_confirmation_not_required(): void {
        $this->option_service->expects( self::once() )->method( 'get_options' )->with( 'league' )->willReturn( [
            'confirmationTimeout'  => '10',
            'confirmationRequired' => false,
        ] );

        $match = (object) [ 'id' => 301 ];
        $this->fixture_repository->expects( self::once() )
            ->method( 'find_all' )
            ->willReturn( [ $match ] );

        $fixture = $this->createStub( Fixture::class );
        $this->fixture_repository->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 301 )
            ->willReturn( $fixture );

        $this->fixture_maintenance_service->expects( self::once() )
            ->method( 'complete_result' )
            ->with( $fixture, 10 );

        $this->fixture_maintenance_service->expects( self::never() )
            ->method( 'chase_match_approval' );

        $this->service->chase_pending_approvals( 'league' );
    }
}
