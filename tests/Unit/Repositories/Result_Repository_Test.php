<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Communication\Results_Report;
use Racketmanager\Repositories\Interfaces\Results_Report_Repository_Interface;
use Racketmanager\Repositories\Result_Repository;
use wpdb;

#[AllowMockObjectsWithoutExpectations]
class Result_Repository_Test extends TestCase {
    private $wpdb;
    private $report_repository;
    private Result_Repository $repository;

    protected function setUp(): void {
        parent::setUp();
        $this->wpdb = $this->createMock( wpdb::class );
        $this->wpdb->prefix = 'wp_';
        $this->report_repository = $this->createMock( Results_Report_Repository_Interface::class );

        $this->repository = new Result_Repository( $this->wpdb, $this->report_repository );
    }

    public function test_update_result_success(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->with(
                $this->stringContains( '`home_points` = %f' ),
                3, 1, 10, 20, $this->anything(), $this->anything(), 'Y', 0, 123
            )
            ->willReturn( 'UPDATE query' );

        $this->wpdb->expects( $this->once() )
            ->method( 'query' )
            ->with( 'UPDATE query' )
            ->willReturn( 1 );

        $data = [
            'home_points' => 3,
            'away_points' => 1,
            'winner_id'   => 10,
            'loser_id'    => 20,
            'custom'      => [ 'sets' => [ [ 6, 4 ] ] ],
            'confirmed'   => 'Y',
            'status'      => 0,
        ];

        $result = $this->repository->update_result( 123, $data );
        $this->assertTrue( $result );
    }

    public function test_update_result_with_user_team(): void {
        $captured = array();
        $this->wpdb->expects( $this->exactly( 2 ) )
            ->method( 'prepare' )
            ->willReturnCallback( function ( ...$args ) use ( &$captured ) {
                $captured[] = $args;
                return 'QUERY';
            } );

        $this->wpdb->expects( $this->exactly( 2 ) )
            ->method( 'query' )
            ->willReturn( 1 );

        $data = [
            'home_points' => 3,
            'away_points' => 1,
            'winner_id'   => 10,
            'loser_id'    => 20,
        ];

        $result = $this->repository->update_result( 123, $data, 'home', 99 );
        $this->assertTrue( $result );
        $this->assertStringContainsString( '`home_captain` = %d', $captured[1][0] );
        $this->assertSame( 99, $captured[1][1] );
        $this->assertSame( 123, $captured[1][2] );
    }

    public function test_update_result_with_away_user_team(): void {
        $captured = array();
        $this->wpdb->method( 'prepare' )
            ->willReturnCallback( function ( ...$args ) use ( &$captured ) {
                $captured[] = $args;
                return 'QUERY';
            } );
        $this->wpdb->method( 'query' )->willReturn( 1 );

        $this->assertTrue( $this->repository->update_result( 123, [ 'home_points' => 1 ], 'away', 77 ) );
        $this->assertStringContainsString( '`away_captain` = %d', $captured[1][0] );
        $this->assertSame( 77, $captured[1][1] );
        $this->assertSame( 123, $captured[1][2] );
    }

    public function test_update_result_tie(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->with(
                $this->stringContains( 'home_points_tie' ),
                4.0, 4.0, -1, -1, 123
            )
            ->willReturn( 'UPDATE TIE' );

        $this->wpdb->expects( $this->once() )
            ->method( 'query' )
            ->with( 'UPDATE TIE' )
            ->willReturn( 1 );

        $result = $this->repository->update_result_tie( 123, 4.0, 4.0, -1, -1 );
        $this->assertTrue( $result );
    }

    public function test_delete_results_report(): void {
        $this->report_repository->expects( $this->once() )
            ->method( 'delete_by_fixture_id' )
            ->with( 123 )
            ->willReturn( true );

        $this->assertTrue( $this->repository->delete_results_report( 123 ) );
    }

    public function test_save_results_report_encodes_json(): void {
        $this->report_repository->expects( $this->once() )
            ->method( 'delete_by_fixture_id' )
            ->with( 123 )
            ->willReturn( true );

        $this->report_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $this->callback( function( $report ) {
                return $report instanceof Results_Report
                    && 123 === $report->match_id
                    && '{"data":"sample"}' === $report->result_object
                    && isset( $report->data )
                    && 'sample' === $report->data->data;
            } ) )
            ->willReturn( 456 );

        $result = $this->repository->save_results_report( 123, [ 'data' => 'sample' ] );
        $this->assertEquals( 456, $result );
    }

    public function test_save_results_report_with_raw_json_string(): void {
        $this->report_repository->expects( $this->once() )
            ->method( 'delete_by_fixture_id' )
            ->with( 123 )
            ->willReturn( true );

        $raw_json = '{"key":"value"}';
        $this->report_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $this->callback( function( $report ) use ( $raw_json ) {
                return $report instanceof Results_Report
                    && 123 === $report->match_id
                    && $raw_json === $report->result_object
                    && isset( $report->data )
                    && 'value' === $report->data->key;
            } ) )
            ->willReturn( 789 );

        $result = $this->repository->save_results_report( 123, $raw_json );
        $this->assertEquals( 789, $result );
    }
}
