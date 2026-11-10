<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Matches;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Fixture\Ports\Fixture_Detail_Service_Interface;
use Racketmanager\Application\Matches\Ports\Fixture_Listing_Finder_Interface;
use Racketmanager\Application\Matches\Queries\Get_Daily_Matches_Handler;
use Racketmanager\Application\Matches\Queries\Get_Daily_Matches_Query;
use Racketmanager\Application\Matches\Queries\Get_Latest_Results_Handler;
use Racketmanager\Application\Matches\Queries\Get_Latest_Results_Query;
use Racketmanager\Application\Matches\Queries\Get_Match_Detail_Handler;
use Racketmanager\Application\Matches\Queries\Get_Match_Detail_Query;
use Racketmanager\Application\Matches\Queries\Get_Match_Header_Handler;
use Racketmanager\Application\Matches\Queries\Get_Match_Header_Query;
use Racketmanager\Application\Matches\Queries\Get_Match_Score_Handler;
use Racketmanager\Application\Matches\Queries\Get_Match_Score_Query;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Services\Player_Service;
use Racketmanager\Tests\Helpers\Fixture_Test_Factory;

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

    private function create_fixture_details( Fixture $fixture, ?League $league = null ): Fixture_Details_DTO {
        return new Fixture_Details_DTO(
            fixture: $fixture,
            league: $league ?? $this->createStub( League::class ),
            event: $this->createStub( Event::class ),
            competition: $this->createStub( Competition::class ),
            link: 'https://example.test/match/' . $fixture->get_id() . '/'
        );
    }

    // -------------------------------------------------------------------------
    // Get_Match_Header_Handler
    // -------------------------------------------------------------------------

    public function test_match_header_handler_missing_match_id_returns_error(): void {
        $detail_service = $this->createMock( Fixture_Detail_Service_Interface::class );
        $detail_service->expects( $this->never() )->method( 'get_fixture_with_details' );

        $handler = new Get_Match_Header_Handler( $detail_service );
        $result  = $handler->handle( new Get_Match_Header_Query( match_id: 0 ) );

        $this->assertSame( 'Match id not found', $result->error_message );
        $this->assertNull( $result->details );
    }

    public function test_match_header_handler_fixture_not_found_returns_error(): void {
        $detail_service = $this->createMock( Fixture_Detail_Service_Interface::class );
        $detail_service->expects( $this->once() )->method( 'get_fixture_with_details' )->with( 42 )->willReturn( null );

        $handler = new Get_Match_Header_Handler( $detail_service );
        $result  = $handler->handle( new Get_Match_Header_Query( match_id: 42 ) );

        $this->assertSame( 'Match not found', $result->error_message );
        $this->assertNull( $result->details );
    }

    public function test_match_header_handler_success(): void {
        $fixture = Fixture_Test_Factory::create( id: 42 );
        $details = $this->create_fixture_details( $fixture );

        $detail_service = $this->createMock( Fixture_Detail_Service_Interface::class );
        $detail_service->expects( $this->once() )->method( 'get_fixture_with_details' )->with( 42 )->willReturn( $details );

        $handler = new Get_Match_Header_Handler( $detail_service );
        $result  = $handler->handle( new Get_Match_Header_Query( match_id: 42, edit: true, template: 'custom' ) );

        $this->assertNull( $result->error_message );
        $this->assertSame( $details, $result->details );
        $this->assertTrue( $result->edit_mode );
        $this->assertSame( 'custom', $result->template );
    }

    // -------------------------------------------------------------------------
    // Get_Match_Score_Handler
    // -------------------------------------------------------------------------

    public function test_match_score_handler_missing_match_id_returns_error(): void {
        $detail_service = $this->createMock( Fixture_Detail_Service_Interface::class );
        $detail_service->expects( $this->never() )->method( 'get_fixture_with_details' );

        $handler = new Get_Match_Score_Handler( $detail_service );
        $result  = $handler->handle( new Get_Match_Score_Query( match_id: 0 ) );

        $this->assertSame( 'Match id not found', $result->error_message );
        $this->assertNull( $result->details );
    }

    public function test_match_score_handler_fixture_not_found_returns_error(): void {
        $detail_service = $this->createMock( Fixture_Detail_Service_Interface::class );
        $detail_service->expects( $this->once() )->method( 'get_fixture_with_details' )->with( 42 )->willReturn( null );

        $handler = new Get_Match_Score_Handler( $detail_service );
        $result  = $handler->handle( new Get_Match_Score_Query( match_id: 42 ) );

        $this->assertSame( 'Match not found', $result->error_message );
        $this->assertNull( $result->details );
    }

    public function test_match_score_handler_unplayed_fixture(): void {
        $fixture = Fixture_Test_Factory::create( id: 42, home_points: null, away_points: null );
        $details = $this->create_fixture_details( $fixture );

        $detail_service = $this->createMock( Fixture_Detail_Service_Interface::class );
        $detail_service->expects( $this->once() )->method( 'get_fixture_with_details' )->with( 42 )->willReturn( $details );

        $handler = new Get_Match_Score_Handler( $detail_service );
        $result  = $handler->handle( new Get_Match_Score_Query( match_id: 42, home_away: true ) );

        $this->assertNull( $result->error_message );
        $this->assertFalse( $result->played );
        $this->assertTrue( $result->home_away );
    }

    public function test_match_score_handler_played_fixture_computes_winner_and_scores(): void {
        $fixture = Fixture_Test_Factory::create(
            id: 42,
            home_team: 1,
            away_team: 2,
            winner_id: 1,
            loser_id: 2,
            home_points: '6.0',
            away_points: '2.0'
        );
        $details = $this->create_fixture_details( $fixture );

        $detail_service = $this->createMock( Fixture_Detail_Service_Interface::class );
        $detail_service->expects( $this->once() )->method( 'get_fixture_with_details' )->with( 42 )->willReturn( $details );

        $handler = new Get_Match_Score_Handler( $detail_service );
        $result  = $handler->handle( new Get_Match_Score_Query( match_id: 42, team_id: '1', opponent_id: '2' ) );

        $this->assertNull( $result->error_message );
        $this->assertTrue( $result->played );
        $this->assertSame( 'winner', $result->score_class );
        $this->assertSame( '6.0', $result->score_team_1 );
        $this->assertSame( '2.0', $result->score_team_2 );
    }

    // -------------------------------------------------------------------------
    // Get_Match_Detail_Handler
    // -------------------------------------------------------------------------

    public function test_match_detail_handler_missing_match_id_returns_error(): void {
        $detail_service = $this->createMock( Fixture_Detail_Service_Interface::class );
        $detail_service->expects( $this->never() )->method( 'get_fixture_with_details' );

        $handler = new Get_Match_Detail_Handler( $detail_service, $this->createStub( Player_Service::class ) );
        $result  = $handler->handle( new Get_Match_Detail_Query( match_id: 0 ) );

        $this->assertSame( 'Match id not found', $result->error_message );
        $this->assertNull( $result->details );
    }

    public function test_match_detail_handler_fixture_not_found_returns_error(): void {
        $detail_service = $this->createMock( Fixture_Detail_Service_Interface::class );
        $detail_service->expects( $this->once() )->method( 'get_fixture_with_details' )->with( 42, false, null )->willReturn( null );

        $handler = new Get_Match_Detail_Handler( $detail_service, $this->createStub( Player_Service::class ) );
        $result  = $handler->handle( new Get_Match_Detail_Query( match_id: 42 ) );

        $this->assertSame( 'Match not found', $result->error_message );
        $this->assertNull( $result->details );
    }

    public function test_match_detail_handler_success_league_fixture(): void {
        $fixture = Fixture_Test_Factory::create( id: 42, final: null );
        $details = $this->create_fixture_details( $fixture );

        $detail_service = $this->createMock( Fixture_Detail_Service_Interface::class );
        $detail_service->expects( $this->once() )->method( 'get_fixture_with_details' )->with( 42, false, null )->willReturn( $details );

        $handler = new Get_Match_Detail_Handler( $detail_service, $this->createStub( Player_Service::class ) );
        $result  = $handler->handle( new Get_Match_Detail_Query( match_id: 42, template: 'default' ) );

        $this->assertNull( $result->error_message );
        $this->assertSame( 'league', $result->type );
        $this->assertSame( '', $result->round );
        $this->assertSame( 'default', $result->template );
    }

    public function test_match_detail_handler_forwards_rubber_player_id(): void {
        $fixture = Fixture_Test_Factory::create( id: 42, final: 'semi' );
        $details = $this->create_fixture_details( $fixture );

        $player     = $this->createStub( Player::class );
        $player->id = 99;

        $player_service = $this->createMock( Player_Service::class );
        $player_service->expects( $this->once() )->method( 'get_player' )->with( 99 )->willReturn( $player );

        $detail_service = $this->createMock( Fixture_Detail_Service_Interface::class );
        $detail_service->expects( $this->once() )->method( 'get_fixture_with_details' )->with( 42, false, 99 )->willReturn( $details );

        $handler = new Get_Match_Detail_Handler( $detail_service, $player_service );
        $result  = $handler->handle( new Get_Match_Detail_Query( match_id: 42, player_id: 99 ) );

        $this->assertNull( $result->error_message );
        $this->assertSame( 'tournament', $result->type );
        $this->assertSame( 'semi', $result->round );
        $this->assertSame( $player, $result->match_player );
    }

    public function test_match_detail_handler_gracefully_handles_unknown_player(): void {
        $fixture = Fixture_Test_Factory::create( id: 42, final: null );
        $details = $this->create_fixture_details( $fixture );

        $player_service = $this->createMock( Player_Service::class );
        $player_service->expects( $this->once() )->method( 'get_player' )->with( 99 )->willThrowException( new Player_Not_Found_Exception( 'not found' ) );

        $detail_service = $this->createMock( Fixture_Detail_Service_Interface::class );
        $detail_service->expects( $this->once() )->method( 'get_fixture_with_details' )->with( 42, false, null )->willReturn( $details );

        $handler = new Get_Match_Detail_Handler( $detail_service, $player_service );
        $result  = $handler->handle( new Get_Match_Detail_Query( match_id: 42, player_id: 99 ) );

        $this->assertNull( $result->error_message );
        $this->assertNull( $result->match_player );
    }
}
