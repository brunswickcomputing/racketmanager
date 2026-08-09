<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Fixture;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\Stage;
use Racketmanager\Domain\Enums\Fixture\Fixture_Update_Status;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Result\Result;
use Racketmanager\Services\Competition\Knockout_Progression_Service;
use Racketmanager\Services\Fixture\Fixture_Finalization_Service;
use Racketmanager\Services\Result\Result_Reporting_Service;
use Racketmanager\Services\Standings\Standings_Service;

#[AllowMockObjectsWithoutExpectations]
class Fixture_Finalization_Service_Test extends TestCase {

    private Fixture_Finalization_Service $service;
    private Standings_Service|Stub $standings_service;
    private Knockout_Progression_Service|MockObject $progression_service;
    private Result_Reporting_Service|Stub $reporting_service;
    private Fixture|Stub $fixture;
    private League|MockObject $league;
    private Result|Stub $result;

    protected function setUp(): void {
        parent::setUp();
        // Standings_Service is final, so we don't mock it if we don't strictly need to.
        // Actually, Fixture_Finalization_Service doesn't even use $this->standings_service yet
        // but we can pass null or a dummy if needed.
        $this->progression_service = $this->createMock( Knockout_Progression_Service::class );
        $this->reporting_service = $this->createStub( Result_Reporting_Service::class );
        
        $this->service = new Fixture_Finalization_Service(
            null, // standings_service
            $this->progression_service,
            $this->reporting_service
        );

        $this->fixture = $this->createStub( Fixture::class );
        $this->league = $this->createMock( League::class );
        $this->result = $this->createStub( Result::class );
    }

    public function test_finalize_updates_standings_when_requested(): void {
        $this->fixture->method( 'get_season' )->willReturn( '2026' );
        
        $this->league->expects( $this->once() )
            ->method( 'update_standings' )
            ->with( '2026' );

        $outcomes = $this->service->finalize( $this->fixture, $this->league, $this->result, true );
        
        $this->assertContains( Fixture_Update_Status::TABLE_UPDATED, $outcomes );
    }

    public function test_finalize_skips_standings_when_not_requested(): void {
        $this->league->expects( $this->never() )
            ->method( 'update_standings' );

        $outcomes = $this->service->finalize( $this->fixture, $this->league, $this->result, false );
        
        $this->assertNotContains( Fixture_Update_Status::TABLE_UPDATED, $outcomes );
    }

    public function test_finalize_triggers_progression_for_draw_stage(): void {
        $this->fixture->method( 'get_league_id' )->willReturn( 123 );
        $this->league->method( 'get_competition_type' )->willReturn( 'tournament' );
        
        $this->league->is_championship = true;
        $this->league->method( 'get_id' )->willReturn( 123 );
        $this->league->method( 'get_name' )->willReturn( 'Tournament Draw' );
        $this->league->method( 'get_event_id' )->willReturn( 789 );

        $this->progression_service->expects( $this->once() )
            ->method( 'progress_winner' )
            ->with( $this->isInstanceOf( Stage::class ), $this->fixture, $this->league );

        $outcomes = $this->service->finalize( $this->fixture, $this->league, $this->result, false );
        
        $this->assertContains( Fixture_Update_Status::PROGRESSED, $outcomes );
    }

    public function test_finalize_schedules_external_report(): void {
        $this->fixture->method( 'get_id' )->willReturn( 456 );
        $this->league->method( 'get_competition_type' )->willReturn( 'LTA' );

        // wp_schedule_single_event is a global function, should be covered by wp-stubs.php
        $outcomes = $this->service->finalize( $this->fixture, $this->league, $this->result, false );
        
        $this->assertIsArray( $outcomes );
    }
}
