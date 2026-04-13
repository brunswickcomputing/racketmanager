<?php

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Team;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Export\DTO\Export_Criteria;
use Racketmanager\Services\Exporter;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Result\Result_Reporting_Service;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;

class Exporter_Test extends TestCase {
    private Fixture_Repository_Interface|Stub $fixture_repository;
    private Result_Reporting_Service|Stub $result_reporting_service;
    private Fixture_Detail_Service|Stub $fixture_detail_service;
    private Club_Repository_Interface $club_repository;
    private Exporter $exporter;

    protected function setUp(): void {
        $this->fixture_repository = $this->createStub( Fixture_Repository_Interface::class );
        $this->result_reporting_service = $this->createStub( Result_Reporting_Service::class );
        $this->fixture_detail_service = $this->createStub( Fixture_Detail_Service::class );
        $this->club_repository = $this->createMock( Club_Repository_Interface::class );

        $this->exporter = new Exporter(
            $this->fixture_repository,
            $this->result_reporting_service,
            $this->fixture_detail_service,
            $this->club_repository
        );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_calendar_returns_ics_format() {
        $criteria = new Export_Criteria( array( 'league_id' => 1 ) );
        $fixture = $this->createStub( Fixture::class );
        $fixture->id = 123;
        $fixture->date = '2026-04-13 12:00:00';
        $fixture->location = 'Center Court';

        $this->fixture_repository->method( 'find_by_criteria' )->willReturn( array( $fixture ) );
        
        $league = $this->createStub( League::class );
        $event = $this->createStub( Event::class );
        $competition = $this->createStub( Competition::class );
        
        $details = new Fixture_Details_DTO(
            $fixture, $league, $event, $competition,
            null, null, null, null, null, '', '', array(), 'Mock Fixture'
        );
        
        $this->fixture_detail_service->method( 'get_fixture_with_details' )->willReturn( $details );

        $result = $this->exporter->calendar( $criteria );

        $this->assertStringContainsString( 'BEGIN:VCALENDAR', $result );
        $this->assertStringContainsString( 'SUMMARY:Mock Fixture', $result );
        $this->assertStringContainsString( 'LOCATION:Center Court', $result );
        $this->assertStringContainsString( 'END:VCALENDAR', $result );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_calendar_handles_missing_details() {
        $criteria = new Export_Criteria( array( 'league_id' => 1 ) );
        $fixture = $this->createStub( Fixture::class );
        $fixture->id = 123;
        $fixture->date = '2026-04-13 12:00:00';
        $fixture->location = 'Center Court';

        $this->fixture_repository->method( 'find_by_criteria' )->willReturn( array( $fixture ) );
        $this->fixture_detail_service->method( 'get_fixture_with_details' )->willReturn( null );

        $result = $this->exporter->calendar( $criteria );

        $this->assertStringContainsString( 'SUMMARY:Fixture 123', $result );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_results_returns_json_format_by_default() {
        $criteria = new Export_Criteria( array( 'league_id' => 1 ) );
        $fixture = $this->createStub( Fixture::class );
        $fixture->date = '2026-04-13 12:00:00';
        $fixture->start_time = '12:00';
        $fixture->winner_id = 1;

        $this->fixture_repository->method( 'find_by_criteria' )->willReturn( array( $fixture ) );
        
        $league = $this->createStub( League::class );
        $event = $this->createStub( Event::class );
        $competition = $this->createStub( Competition::class );
        
        $home_team_obj = $this->createStub( Team::class );
        $home_team_obj->title = 'Home Team';
        $home_team = new Team_Details_DTO( $home_team_obj, null, null );
        
        $away_team_obj = $this->createStub( Team::class );
        $away_team_obj->title = 'Away Team';
        $away_team = new Team_Details_DTO( $away_team_obj, null, null );

        $details = new Fixture_Details_DTO(
            $fixture, $league, $event, $competition,
            $home_team, $away_team, null, null, null, '', '6-0 6-0', array(), 'Mock Fixture'
        );
        
        $this->fixture_detail_service->method( 'get_fixture_with_details' )->willReturn( $details );

        $result = $this->exporter->results( $criteria );

        $this->assertStringContainsString( '"home_team":"Home Team"', $result );
        $this->assertStringContainsString( '"score":"6-0 6-0"', $result );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_results_returns_csv_format() {
        $criteria = new Export_Criteria( array( 'league_id' => 1, 'format' => 'csv' ) );
        $fixture = $this->createStub( Fixture::class );
        $fixture->date = '2026-04-13 12:00:00';
        $fixture->start_time = '12:00';
        $fixture->winner_id = 1;

        $this->fixture_repository->method( 'find_by_criteria' )->willReturn( array( $fixture ) );
        
        $league = $this->createStub( League::class );
        $event = $this->createStub( Event::class );
        $competition = $this->createStub( Competition::class );
        
        $home_team_obj = $this->createStub( Team::class );
        $home_team_obj->title = 'Home Team';
        $home_team = new Team_Details_DTO( $home_team_obj, null, null );
        
        $away_team_obj = $this->createStub( Team::class );
        $away_team_obj->title = 'Away Team';
        $away_team = new Team_Details_DTO( $away_team_obj, null, null );

        $details = new Fixture_Details_DTO(
            $fixture, $league, $event, $competition,
            $home_team, $away_team, null, null, null, '', '6-0 6-0', array(), 'Mock Fixture'
        );
        
        $this->fixture_detail_service->method( 'get_fixture_with_details' )->willReturn( $details );

        $result = $this->exporter->results( $criteria );

        $this->assertStringContainsString( '"Home Team","Away Team",2026-04-13,12:00,"6-0 6-0"', $result );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_results_handles_missing_details() {
        $criteria = new Export_Criteria( array( 'league_id' => 1 ) );
        $fixture = $this->createStub( Fixture::class );
        $fixture->date = '2026-04-13 12:00:00';
        $fixture->start_time = '12:00';

        $this->fixture_repository->method( 'find_by_criteria' )->willReturn( array( $fixture ) );
        $this->fixture_detail_service->method( 'get_fixture_with_details' )->willReturn( null );

        $result = $this->exporter->results( $criteria );

        $this->assertStringContainsString( '"home_team":""', $result );
        $this->assertStringContainsString( '"away_team":""', $result );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_results_omits_score_without_winner() {
        $criteria = new Export_Criteria( array( 'league_id' => 1 ) );
        $fixture = $this->createStub( Fixture::class );
        $fixture->date = '2026-04-13 12:00:00';
        $fixture->winner_id = null;

        $this->fixture_repository->method( 'find_by_criteria' )->willReturn( array( $fixture ) );
        
        $result = $this->exporter->results( $criteria );

        $this->assertStringNotContainsString( '"score":', $result );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_results_populates_club_shortcode_from_repository() {
        $criteria = new Export_Criteria( array( 'club_id' => 5 ) );
        $fixture = $this->createStub( Fixture::class );
        $fixture->date = '2026-04-13 12:00:00';

        $this->fixture_repository->method( 'find_by_criteria' )->willReturn( array( $fixture ) );

        $club = $this->createStub( Club::class );
        $club->method( 'get_shortcode' )->willReturn( 'TEST_CLUB' );
        $this->club_repository->method( 'find_by_id' )->with( 5 )->willReturn( $club );

        $result = $this->exporter->results( $criteria );

        $this->assertStringContainsString( '"club":"TEST_CLUB"', $result );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_results_handles_missing_club() {
        $criteria = new Export_Criteria( array( 'club_id' => 999 ) );
        $fixture = $this->createStub( Fixture::class );
        $fixture->date = '2026-04-13 12:00:00';

        $this->fixture_repository->method( 'find_by_criteria' )->willReturn( array( $fixture ) );
        $this->club_repository->method( 'find_by_id' )->willReturn( null );

        $result = $this->exporter->results( $criteria );

        $this->assertStringContainsString( '"club":""', $result );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_fixtures_delegates_to_results() {
        $criteria = new Export_Criteria( array( 'league_id' => 1 ) );
        $fixture = $this->createStub( Fixture::class );
        $fixture->date = '2026-04-13 12:00:00';
        $fixture->winner_id = 1;

        $this->fixture_repository->method( 'find_by_criteria' )->willReturn( array( $fixture ) );
        
        $league = $this->createStub( League::class );
        $event = $this->createStub( Event::class );
        $competition = $this->createStub( Competition::class );
        $details = new Fixture_Details_DTO(
            $fixture, $league, $event, $competition,
            null, null, null, null, null, '', '6-0 6-0', array(), 'Mock Fixture'
        );
        $this->fixture_detail_service->method( 'get_fixture_with_details' )->willReturn( $details );

        $result = $this->exporter->fixtures( $criteria );

        $this->assertStringContainsString( '"score":"6-0 6-0"', $result );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_results_handles_missing_teams() {
        $criteria = new Export_Criteria( array( 'league_id' => 1 ) );
        $fixture = $this->createStub( Fixture::class );
        $fixture->date = '2026-04-13 12:00:00';

        $this->fixture_repository->method( 'find_by_criteria' )->willReturn( array( $fixture ) );
        
        $league = $this->createStub( League::class );
        $event = $this->createStub( Event::class );
        $competition = $this->createStub( Competition::class );
        
        // details exist but teams are null
        $details = new Fixture_Details_DTO(
            $fixture, $league, $event, $competition,
            null, null, null, null, null, '', '', array(), 'Mock Fixture'
        );
        
        $this->fixture_detail_service->method( 'get_fixture_with_details' )->willReturn( $details );

        $result = $this->exporter->results( $criteria );

        $this->assertStringContainsString( '"home_team":""', $result );
        $this->assertStringContainsString( '"away_team":""', $result );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_report_results_returns_csv_format() {
        $criteria = new Export_Criteria( array( 'tournament_id' => 1 ) );
        $fixture = $this->createStub( Fixture::class );
        $this->fixture_repository->method( 'find_by_criteria' )->willReturn( array( $fixture ) );

        $report_row = array( 'Tournament Name', 'CODE', 'Organiser', 'Venue', 'Event' );
        $this->result_reporting_service->method( 'report_fixtures' )->willReturn( array( $report_row ) );

        $result = $this->exporter->report_results( $criteria );

        $this->assertStringContainsString( 'Tournament,Code,Organiser,Venue', $result );
        $this->assertStringContainsString( 'Tournament Name', $result );
        $this->assertStringContainsString( 'CODE', $result );
    }
}
