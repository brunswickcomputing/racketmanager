<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Tournament\Queries;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Ports\Query_Vars_Interface;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Query;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\DTO\Tournament\Tournament_Details_DTO;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Services\Tournament_Service;

#[AllowMockObjectsWithoutExpectations]
final class Get_Tournament_Handler_Test extends TestCase {
    public function test_handle_sets_season_query_var_via_interface(): void {
        $tournament_service = $this->createMock( Tournament_Service::class );
        $query_vars         = $this->createMock( Query_Vars_Interface::class );

        $tournament = $this->createMock( Tournament::class );
        $tournament->season = '2026';
        $tournament->method( 'get_end_date' )->willReturn( '2026-08-01' );

        $competition = $this->createMock( Competition::class );
        $competition->age_group = 'Open';

        $details = $this->createMock( Tournament_Details_DTO::class );
        $details->tournament  = $tournament;
        $details->competition = $competition;

        $tournament_service->expects( $this->once() )
            ->method( 'get_tournament_with_details' )
            ->with( 42 )
            ->willReturn( $details );

        $tournament_service->method( 'get_tournaments' )
            ->willReturn( [] );

        $query_vars->expects( $this->once() )
            ->method( 'set' )
            ->with( 'season', '2026' );

        $handler = new Get_Tournament_Handler(
            tournament_service: $tournament_service,
            query_vars: $query_vars
        );

        $query = new Get_Tournament_Query( tournament: 42 );
        $result = $handler->handle( $query );

        $this->assertFalse( $result->is_error() );
        $this->assertSame( $tournament, $result->tournament );
    }

    public function test_handle_gracefully_handles_null_query_vars(): void {
        $tournament_service = $this->createMock( Tournament_Service::class );

        $tournament = $this->createMock( Tournament::class );
        $tournament->season = '2026';
        $tournament->method( 'get_end_date' )->willReturn( '2026-08-01' );

        $competition = $this->createMock( Competition::class );
        $competition->age_group = 'Open';

        $details = $this->createMock( Tournament_Details_DTO::class );
        $details->tournament  = $tournament;
        $details->competition = $competition;

        $tournament_service->method( 'get_tournament_with_details' )
            ->willReturn( $details );
        $tournament_service->method( 'get_tournaments' )
            ->willReturn( [] );

        $handler = new Get_Tournament_Handler(
            tournament_service: $tournament_service,
            query_vars: null
        );

        $query = new Get_Tournament_Query( tournament: 42 );
        $result = $handler->handle( $query );

        $this->assertFalse( $result->is_error() );
    }
}
