<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Matches;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Matches\Ports\Fixture_Listing_Finder_Interface;
use Racketmanager\Application\Matches\Queries\Get_Daily_Matches_Handler;
use Racketmanager\Application\Matches\Queries\Get_Daily_Matches_Query;
use Racketmanager\Application\Matches\Queries\Get_Latest_Results_Handler;
use Racketmanager\Application\Matches\Queries\Get_Latest_Results_Query;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;

class Matches_Query_Handlers_Test extends TestCase {

    public function test_daily_query_normalises_input(): void {
        $query = new Get_Daily_Matches_Query( '2026-02-30', 'League ', 'daily/../x', '2026-10-12' );

        $this->assertSame( '2026-10-12', $query->match_date, 'Invalid calendar date falls back to today' );
        $this->assertSame( 'league', $query->competition_type );
        $this->assertSame( 'dailyx', $query->template );

        $valid = new Get_Daily_Matches_Query( '2024-06-15', 'cup', '', '2026-10-12' );
        $this->assertSame( '2024-06-15', $valid->match_date );
        $this->assertSame( '', $valid->template );
    }

    public function test_daily_query_rejects_sql_payload_in_date(): void {
        $query = new Get_Daily_Matches_Query( "2024-06-15' OR 1=1 -- ", 'league', 'daily', '2026-10-12' );
        $this->assertSame( '2026-10-12', $query->match_date );
    }

    public function test_daily_handler_passes_date_and_type_to_finder(): void {
        $finder = $this->createMock( Fixture_Listing_Finder_Interface::class );
        $finder->expects( $this->once() )
            ->method( 'find_by_match_date' )
            ->with( '2024-06-15', Competition_Type::CUP )
            ->willReturn( [] );

        $result = ( new Get_Daily_Matches_Handler( $finder ) )->handle( new Get_Daily_Matches_Query( '2024-06-15', 'cup' ) );

        $this->assertSame( [], $result->fixtures );
        $this->assertSame( '2024-06-15', $result->match_date );
    }

    public function test_daily_handler_unknown_competition_type_returns_empty_without_query(): void {
        $finder = $this->createMock( Fixture_Listing_Finder_Interface::class );
        $finder->expects( $this->never() )->method( 'find_by_match_date' );

        $result = ( new Get_Daily_Matches_Handler( $finder ) )->handle( new Get_Daily_Matches_Query( '2024-06-15', "league' OR 1=1" ) );

        $this->assertSame( [], $result->fixtures );
        $this->assertSame( '2024-06-15', $result->match_date );
    }

    public function test_latest_query_normalises_input(): void {
        $query = new Get_Latest_Results_Query( days: -3, club_id: 0, competition_id: 0, age_group: 'O35', header_level: 9, template: 'results' );

        $this->assertSame( 0, $query->days );
        $this->assertNull( $query->club_id );
        $this->assertNull( $query->competition_id );
        $this->assertSame( 'O35', $query->age_group );
        $this->assertFalse( $query->has_invalid_filter );
        $this->assertSame( 1, $query->header_level );

        $invalid = new Get_Latest_Results_Query( age_group: "x' OR '1'='1" );
        $this->assertNull( $invalid->age_group );
        $this->assertTrue( $invalid->has_invalid_filter );
    }

    public function test_latest_handler_passes_criteria_to_finder(): void {
        $finder = $this->createMock( Fixture_Listing_Finder_Interface::class );
        $finder->expects( $this->once() )
            ->method( 'find_latest_results' )
            ->with( 14, Competition_Type::LEAGUE, 5, 9, 'junior' )
            ->willReturn( [] );

        $handler = new Get_Latest_Results_Handler( $finder, $this->createStub( Club_Repository_Interface::class ), $this->createStub( Competition_Repository_Interface::class ) );
        $handler->handle( new Get_Latest_Results_Query( days: 14, club_id: 5, competition_id: 9, age_group: 'junior' ) );
    }

    public function test_latest_handler_resolves_club_shortcode_and_competition_name(): void {
        $club     = $this->createStub( Club::class );
        $club->id = 42;
        $clubs    = $this->createMock( Club_Repository_Interface::class );
        $clubs->expects( $this->once() )->method( 'find_by_id' )->with( 'Leigh Park', 'shortcode' )->willReturn( $club );

        $competition     = $this->createStub( Competition::class );
        $competition->id = 77;
        $competitions    = $this->createMock( Competition_Repository_Interface::class );
        $competitions->expects( $this->once() )->method( 'find_by_name' )->with( 'winter league' )->willReturn( $competition );

        $finder = $this->createMock( Fixture_Listing_Finder_Interface::class );
        $finder->expects( $this->once() )
            ->method( 'find_latest_results' )
            ->with( 7, Competition_Type::LEAGUE, 42, 77, null )
            ->willReturn( [] );

        $handler = new Get_Latest_Results_Handler( $finder, $clubs, $competitions );
        $handler->handle( new Get_Latest_Results_Query( club_shortcode: 'Leigh Park', competition_name: 'winter league' ) );
    }

    public function test_latest_handler_unknown_club_returns_empty_instead_of_all_clubs(): void {
        $clubs = $this->createStub( Club_Repository_Interface::class );
        $clubs->method( 'find_by_id' )->willReturn( null );

        $finder = $this->createMock( Fixture_Listing_Finder_Interface::class );
        $finder->expects( $this->never() )->method( 'find_latest_results' );

        $handler = new Get_Latest_Results_Handler( $finder, $clubs, $this->createStub( Competition_Repository_Interface::class ) );
        $result  = $handler->handle( new Get_Latest_Results_Query( club_shortcode: 'Nowhere' ) );

        $this->assertSame( [], $result->fixtures );
    }

    public function test_latest_handler_invalid_filters_return_empty_without_query(): void {
        $finder = $this->createMock( Fixture_Listing_Finder_Interface::class );
        $finder->expects( $this->never() )->method( 'find_latest_results' );

        $handler = new Get_Latest_Results_Handler( $finder, $this->createStub( Club_Repository_Interface::class ), $this->createStub( Competition_Repository_Interface::class ) );

        $this->assertSame( [], $handler->handle( new Get_Latest_Results_Query( competition_type: 'bogus' ) )->fixtures );
        $this->assertSame( [], $handler->handle( new Get_Latest_Results_Query( age_group: "'; DROP TABLE x; --" ) )->fixtures );
    }
}
