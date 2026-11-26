<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters {
    if ( ! function_exists( 'Racketmanager\Presentation\Presenters\get_userdata' ) ) {
        function get_userdata( $id ) {
            return (object) [ 'display_name' => 'Admin User' ];
        }
    }
}

namespace Racketmanager\Tests\Unit\Presentation\Presenters {

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Result\Results_Checker_Data;
use Racketmanager\Domain\Result\Results_Checker;
use Racketmanager\Presentation\Presenters\Results_Checker_Presenter;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\Enums\Results_Checker_Status;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Player\Player_Factory;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Tests\Helpers\League_Test_Factory;
use stdClass;

class Results_Checker_Presenter_Test extends TestCase {
    private Results_Checker_Presenter $presenter;
    private $fixture_repository;
    private $player_repository;
    private $team_repository;
    private $league_repository;
    private $fixture_detail_service;

    protected function setUp(): void {
        parent::setUp();
        $this->fixture_repository = $this->createStub( Fixture_Repository_Interface::class );
        $this->player_repository  = $this->createStub( Player_Repository_Interface::class );
        $this->team_repository    = $this->createStub( Team_Repository_Interface::class );
        $this->league_repository  = $this->createStub( League_Repository_Interface::class );
        $this->fixture_detail_service = $this->createStub( Fixture_Detail_Service::class );
        
        $this->presenter = new Results_Checker_Presenter(
            $this->fixture_repository,
            $this->player_repository,
            $this->team_repository,
            $this->league_repository,
            $this->fixture_detail_service
        );
    }

    public function test_present_maps_all_fields_correctly(): void {
        $checker = new Results_Checker( new Results_Checker_Data(
            id: 123,
            league_id: 1,
            match_id: 456,
            team_id: 789,
            player_id: 101,
            description: 'Test Description',
            status: Results_Checker_Status::APPROVED->value,
            updated_date: '2026-04-10 12:00:00'
        ) );

        $match = new \Racketmanager\Domain\Fixture\Fixture();
        $match->set_date( '2026-04-01' );
        $match->set_league_id( 1 );
        $match->set_home_team( '10' );
        $match->set_away_team( '20' );
        $match->set_season( '2026' );

        $team_obj = new stdClass();
        $team_obj->title = 'Team A';
        $team_obj->club = new stdClass();
        $team_obj->club->shortcode = 'CLUB-A';
        
        // Mock global racketmanager for Team constructor
        global $racketmanager;
        $racketmanager = new stdClass();
        $racketmanager->container = $this->createStub(\Racketmanager\Services\Container\Simple_Container::class);
        $racketmanager->container->method('get')->willReturn($this->createStub(\Racketmanager\Services\Player_Service::class));
        
        $team = Team::from_object( $team_obj );

        $player_obj = new stdClass();
        $player_obj->display_name = 'John Doe';
        $player_obj->ID = 101;
        $player_obj->user_email = 'john@example.com';
        $player_obj->year_of_birth = '1990';
        $player = Player_Factory::from_object( $player_obj );

        $league = $this->createStub(\Racketmanager\Domain\Competition\League::class);
        League_Test_Factory::set_property( $league, 'title', 'League A' );
        League_Test_Factory::set_property( $league, 'is_championship', false );
        League_Test_Factory::set_property(
            $league,
            'event',
            $this->createStub( \Racketmanager\Domain\Competition\Event::class )
        );

        $this->fixture_repository->method( 'find_by_id' )->willReturn( $match );
        $this->team_repository->method( 'find_by_id' )->willReturn( $team );
        $this->player_repository->method( 'find' )->willReturn( $player );
        $this->league_repository->method( 'find_by_id' )->willReturn( $league );
        
        $details = new Fixture_Details_DTO(
            $match,
            $league,
            $league->event,
            $league->event->competition ?? $this->createStub(\Racketmanager\Domain\Competition\Competition::class),
            null,
            null,
            null,
            null,
            null,
            '/match/league-a/2026/day/team-a-vs-team-a/'
        );
        $this->fixture_detail_service->method( 'get_fixture_with_details' )->willReturn( $details );

        $this->fixture_detail_service->method('get_team_name_or_placeholder')
            ->willReturnMap([
                ['10', '2026', $league, null, 'Team 10'],
                ['20', '2026', $league, null, 'Team 20'],
            ]);

        $view_model = $this->presenter->present( $checker, 'all' );

        $this->assertEquals( 123, $view_model->id );
        $this->assertEquals( '2026-04-01', $view_model->formatted_date );
        $this->assertEquals( '/match/league-a/2026/day/team-a-vs-team-a/result/', $view_model->match_link );
        $this->assertEquals( 'Team 10 vs Team 20', $view_model->match_title );
        $this->assertEquals( 'Team A', $view_model->team_title );
        $this->assertEquals( 'John Doe', $view_model->player_name );
        $this->assertSame( '/clubs/club_a/players/john-doe/', $view_model->player_link );
        $this->assertEquals( 'Test Description', $view_model->description );
        $this->assertEquals( 'Approved', $view_model->status_desc );
        $this->assertTrue( $view_model->show_status );
    }

    public function test_get_match_link_for_standard_league(): void {
        $match = new \Racketmanager\Domain\Fixture\Fixture();
        $match->set_id( 100 );
        $match->set_league_id( 1 );
        $match->set_home_team( '10' );
        $match->set_away_team( '20' );
        $match->set_season( '2026' );
        $match->set_match_day( 5 );

        $competition = $this->createStub(\Racketmanager\Domain\Competition\Competition::class);
        $event = $this->createStub(\Racketmanager\Domain\Competition\Event::class);
        $event->method('get_competition_id')->willReturn(1); // competition->id
        
        $league = $this->createStub(\Racketmanager\Domain\Competition\League::class);
        League_Test_Factory::set_property( $league, 'title', 'League A' );
        League_Test_Factory::set_property( $league, 'event', $event );
        League_Test_Factory::set_property( $league, 'is_championship', false );

        $home_team = $this->createStub(Team::class);
        $home_team->method('get_name')->willReturn('Home Team');
        $away_team = $this->createStub(Team::class);
        $away_team->method('get_name')->willReturn('Away Team');

        $this->league_repository->method('find_by_id')->willReturn($league);
        $this->team_repository->method('find_by_id')->willReturnMap([
            ['10', $home_team],
            ['20', $away_team],
        ]);

        $checker = new Results_Checker( new Results_Checker_Data( match_id: 100 ) );
        $this->fixture_repository->method('find_by_id')->willReturn($match);

        $details = new Fixture_Details_DTO(
            $match,
            $league,
            $event,
            $competition,
            null,
            null,
            null,
            null,
            null,
            '/match/league-a/2026/day5/home-team-vs-away-team/'
        );
        $this->fixture_detail_service->method('get_fixture_with_details')->willReturn($details);

        $view_model = $this->presenter->present($checker, 'all');

        // Expected: /match/league-a/2026/day5/home-team-vs-away-team/result/
        $this->assertEquals('/match/league-a/2026/day5/home-team-vs-away-team/result/', $view_model->match_link);
    }

    public function test_get_match_link_for_box_league(): void {
        $match = new \Racketmanager\Domain\Fixture\Fixture();
        $match->set_id( 100 );
        $match->set_league_id( 1 );

        $event = $this->createStub(\Racketmanager\Domain\Competition\Event::class);
        $event->set_is_box( true );

        $league = $this->createStub(\Racketmanager\Domain\Competition\League::class);
        League_Test_Factory::set_property( $league, 'title', 'Box League' );
        League_Test_Factory::set_property( $league, 'event', $event );

        $this->league_repository->method('find_by_id')->willReturn($league);
        
        $checker = new Results_Checker( new Results_Checker_Data( match_id: 100 ) );
        $this->fixture_repository->method('find_by_id')->willReturn($match);

        $details = new Fixture_Details_DTO(
            $match,
            $league,
            $event,
            $this->createStub(\Racketmanager\Domain\Competition\Competition::class),
            null,
            null,
            null,
            null,
            null,
            '/league/box-league/match/100/'
        );
        $this->fixture_detail_service->method('get_fixture_with_details')->willReturn($details);

        $view_model = $this->presenter->present($checker, 'all');

        $this->assertEquals('/league/box-league/match/100/result/', $view_model->match_link);
    }

    public function test_show_status_is_false_for_outstanding_filter(): void {
        $checker = new Results_Checker( new Results_Checker_Data( match_id: 456 ) );
        $this->fixture_repository->method( 'find_by_id' )->willReturn( new \Racketmanager\Domain\Fixture\Fixture() );
        $view_model = $this->presenter->present( $checker, 'outstanding' );
        $this->assertFalse( $view_model->show_status );
    }

    public function test_present_handles_missing_match_date(): void {
        $checker = new Results_Checker( new Results_Checker_Data( match_id: 456 ) );
        $this->fixture_repository->method( 'find_by_id' )->willReturn( new \Racketmanager\Domain\Fixture\Fixture() );
        // date is null/unset

        $view_model = $this->presenter->present( $checker, 'all' );

        $this->assertEquals( '', $view_model->formatted_date );
    }
}
}
