<?php

namespace Racketmanager\Tests\Unit\Domain\Fixture\Services;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Fixture\DTOs\Fixture_Details_DTO;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Services\Fixture_Detail_Service;

use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Club_Role_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;

class Fixture_Detail_Service_Test extends TestCase {
    private Fixture_Detail_Service $service;
    private $permission_service;
    private $player_repository;
    private $league_team_repository;
    private $club_repository;
    private $team_repository;
    private $club_role_repository;
    private $league_repository;
    private $event_repository;

    protected function setUp(): void {
        parent::setUp();
        
        $this->permission_service = $this->createStub( Fixture_Permission_Service::class );
        $this->player_repository = $this->createStub( Player_Repository_Interface::class );
        $this->league_team_repository = $this->createStub( League_Team_Repository_Interface::class );
        $this->club_repository = $this->createStub( Club_Repository_Interface::class );
        $this->team_repository = $this->createStub( Team_Repository_Interface::class );
        $this->club_role_repository = $this->createStub( Club_Role_Repository_Interface::class );
        $this->league_repository = $this->createStub( League_Repository_Interface::class );
        $this->event_repository = $this->createStub( Event_Repository_Interface::class );

        $this->service = new Fixture_Detail_Service(
            $this->league_repository,
            $this->event_repository,
            $this->createStub( Competition_Repository_Interface::class ),
            $this->team_repository,
            $this->club_repository,
            $this->createStub( Rubber_Repository_Interface::class ),
            $this->league_team_repository,
            $this->player_repository,
            $this->createStub( Fixture_Link_Service::class ),
            $this->permission_service,
            $this->club_role_repository
        );
    }

    public function test_enrich_sets_is_knockout_true_when_final_is_present() {
        $fixture = $this->createStub(Fixture::class);
        $fixture->method('get_id')->willReturn(123);
        $fixture->method('get_final')->willReturn('final');
        $fixture->method('get_league_id')->willReturn(null);
        $fixture->method('get_season')->willReturn('2025');

        $this->permission_service->method('is_update_allowed')->willReturn((object)['user_can_update' => true]);
        
        $dto = new Fixture_Details_DTO($fixture);
        $result = $this->service->enrich($dto);

        $this->assertTrue($result->enriched_data['is_knockout']);
        $this->assertTrue($result->is_update_allowed->user_can_update);
    }

    public function test_enrich_sets_is_knockout_false_when_final_is_empty() {
        $fixture = $this->createStub(Fixture::class);
        $fixture->method('get_id')->willReturn(123);
        $fixture->method('get_final')->willReturn(null);
        $fixture->method('get_league_id')->willReturn(null);
        $fixture->method('get_season')->willReturn('2025');
        
        $dto = new Fixture_Details_DTO($fixture);
        $result = $this->service->enrich($dto);

        $this->assertFalse($result->enriched_data['is_knockout']);
    }

    public function test_enrich_resolves_captain_and_contact_details() {
        $fixture = $this->createStub( Fixture::class );
        $fixture->method('get_id')->willReturn(123);
        $fixture->method('get_league_id')->willReturn(10);
        $fixture->method('get_season')->willReturn('2025');
        $fixture->method('get_home_team')->willReturn('1');
        $fixture->method('get_away_team')->willReturn('2');

        $league_team_home = $this->createStub(\Racketmanager\Domain\Competition\League_Team::class);
        $league_team_home->method('get_captain')->willReturn(50);
        $league_team_home->contactno = '0777111';
        $league_team_home->contactemail = 'home@capt.com';

        $league_team_away = $this->createStub(\Racketmanager\Domain\Competition\League_Team::class);
        $league_team_away->method('get_captain')->willReturn(60);
        $league_team_away->contactno = '0777222';
        $league_team_away->contactemail = 'away@capt.com';

        $this->league_team_repository->method('find_by_team_league_and_season')->willReturnCallback(function($team_id, $league_id, $season) use ($league_team_home, $league_team_away) {
            if ($team_id === 1) return $league_team_home;
            if ($team_id === 2) return $league_team_away;
            return null;
        });

        $home_player = $this->createStub(\Racketmanager\Domain\Player::class);
        $home_player->method('get_fullname')->willReturn('Home Captain');
        $home_player->method('get_contactno')->willReturn('0777111');
        $home_player->method('get_email')->willReturn('home@capt.com');

        $away_player = $this->createStub(\Racketmanager\Domain\Player::class);
        $away_player->method('get_fullname')->willReturn('Away Captain');
        $away_player->method('get_contactno')->willReturn('0777222');
        $away_player->method('get_email')->willReturn('away@capt.com');

        $this->player_repository->method('find')->willReturnCallback(function($id) use ($home_player, $away_player) {
            if ($id === 50) return $home_player;
            if ($id === 60) return $away_player;
            return null;
        });

        $dto = new Fixture_Details_DTO($fixture);
        $result = $this->service->enrich($dto);

        $this->assertEquals('Home Captain', $result->home_captain_name);
        $this->assertEquals('0777111', $result->home_captain_contactno);
        $this->assertEquals('home@capt.com', $result->home_captain_email);
        $this->assertEquals('Away Captain', $result->away_captain_name);
        $this->assertEquals('0777222', $result->away_captain_contactno);
        $this->assertEquals('away@capt.com', $result->away_captain_email);
    }

    public function test_get_team_details_resolves_match_secretary() {
        $fixture = $this->createStub( Fixture::class );
        $fixture->method('get_id')->willReturn(123);
        $fixture->method('get_league_id')->willReturn(10);
        $fixture->method('get_season')->willReturn('2025');
        $fixture->method('get_home_team')->willReturn('1');
        $fixture->method('get_away_team')->willReturn('2');

        $team = $this->createStub(\Racketmanager\Domain\Team::class);
        $team->method('get_club_id')->willReturn(5);
        $team->method('get_name')->willReturn('Home Team');
        $this->team_repository->method('find_by_id')->willReturn($team);

        $club = $this->createStub(\Racketmanager\Domain\Club::class);
        $club->method('get_id')->willReturn(5);
        $this->club_repository->method('find_by_id')->willReturn($club);

        $role = $this->createStub(\Racketmanager\Domain\Club_Role::class);
        $role->method('get_user_id')->willReturn(100);
        $this->club_role_repository->method('search')->willReturn([$role]);

        $secretary = $this->createStub(\Racketmanager\Domain\Player::class);
        $secretary->method('get_fullname')->willReturn('John Sec');
        $this->player_repository->method('find')->willReturnCallback(function($id) use ($secretary) {
            if ($id === 100) return $secretary;
            return null;
        });

        $dto = new Fixture_Details_DTO($fixture);
        $result = $this->service->enrich($dto);

        $this->assertNotNull($result->home_team);
        $this->assertNotNull($result->home_team->match_secretary);
        $this->assertEquals('John Sec', $result->home_team->match_secretary->get_fullname());
    }

    public function test_enrich_resolves_fixture_link() {
        $fixture = $this->createStub( Fixture::class );
        $fixture->method('get_id')->willReturn(123);
        $fixture->method('get_league_id')->willReturn(10);
        $fixture->method('get_season')->willReturn('2025');

        $league = $this->createStub( League::class );
        
        $league_repo = $this->createStub( League_Repository_Interface::class );
        $league_repo->method('find_by_id')->willReturn($league);

        $link_service = $this->createStub( Fixture_Link_Service::class );
        $link_service->method('get_fixture_link')->willReturn('https://example.com/match/123');

        $this->service = new Fixture_Detail_Service(
            $league_repo,
            $this->createStub( Event_Repository_Interface::class ),
            $this->createStub( Competition_Repository_Interface::class ),
            $this->team_repository,
            $this->club_repository,
            $this->createStub( Rubber_Repository_Interface::class ),
            $this->league_team_repository,
            $this->player_repository,
            $link_service,
            $this->permission_service,
            $this->club_role_repository
        );

        $dto = new Fixture_Details_DTO($fixture);
        $result = $this->service->enrich($dto);

        $this->assertEquals('https://example.com/match/123', $result->fixture_link);
        $this->assertEquals('https://example.com/match/123', $result->link);
    }

    public function test_enrich_populates_club_players_filtered_by_gender(): void {
        $fixture_md = $this->createStub( \Racketmanager\Domain\Fixture\Fixture::class );
        $fixture_md->method( 'get_id' )->willReturn( 123 );
        $fixture_md->method( 'get_league_id' )->willReturn( 101 );
        $fixture_md->method( 'get_home_team' )->willReturn( '10' );
        $fixture_md->method( 'get_away_team' )->willReturn( '20' );
        $fixture_md->method( 'get_season' )->willReturn( '2025' );

        $fixture_wd = $this->createStub( \Racketmanager\Domain\Fixture\Fixture::class );
        $fixture_wd->method( 'get_id' )->willReturn( 124 );
        $fixture_wd->method( 'get_league_id' )->willReturn( 102 );
        $fixture_wd->method( 'get_home_team' )->willReturn( '10' );
        $fixture_wd->method( 'get_away_team' )->willReturn( '20' );
        $fixture_wd->method( 'get_season' )->willReturn( '2025' );

        $fixture_xd = $this->createStub( \Racketmanager\Domain\Fixture\Fixture::class );
        $fixture_xd->method( 'get_id' )->willReturn( 125 );
        $fixture_xd->method( 'get_league_id' )->willReturn( 103 );
        $fixture_xd->method( 'get_home_team' )->willReturn( '10' );
        $fixture_xd->method( 'get_away_team' )->willReturn( '20' );
        $fixture_xd->method( 'get_season' )->willReturn( '2025' );

        $league_md = $this->createStub( \Racketmanager\Domain\Competition\League::class );
        $league_md->method( 'get_event_id' )->willReturn( 5 );
        
        $league_wd = $this->createStub( \Racketmanager\Domain\Competition\League::class );
        $league_wd->method( 'get_event_id' )->willReturn( 6 );

        $league_xd = $this->createStub( \Racketmanager\Domain\Competition\League::class );
        $league_xd->method( 'get_event_id' )->willReturn( 7 );

        $this->league_repository = $this->createStub( League_Repository_Interface::class );
        $this->league_repository->method( 'find_by_id' )->willReturnCallback(function($id) use ($league_md, $league_wd, $league_xd) {
            if ($id === 101) return $league_md;
            if ($id === 102) return $league_wd;
            if ($id === 103) return $league_xd;
            return null;
        });

        $event_md = $this->createStub( \Racketmanager\Domain\Competition\Event::class );
        $event_md->method( 'get_type' )->willReturn( 'MD' );

        $event_wd = $this->createStub( \Racketmanager\Domain\Competition\Event::class );
        $event_wd->method( 'get_type' )->willReturn( 'WD' );

        $event_xd = $this->createStub( \Racketmanager\Domain\Competition\Event::class );
        $event_xd->method( 'get_type' )->willReturn( 'XD' );

        $this->event_repository = $this->createStub( Event_Repository_Interface::class );
        $this->event_repository->method( 'find_by_id' )->willReturnMap([
            [5, $event_md],
            [6, $event_wd],
            [7, $event_xd]
        ]);

        $this->service = new Fixture_Detail_Service(
            $this->league_repository,
            $this->event_repository,
            $this->createStub( Competition_Repository_Interface::class ),
            $this->team_repository,
            $this->club_repository,
            $this->createStub( Rubber_Repository_Interface::class ),
            $this->league_team_repository,
            $this->player_repository,
            $this->createStub( Fixture_Link_Service::class ),
            $this->permission_service,
            $this->club_role_repository
        );

        $home_team = $this->createStub( \Racketmanager\Domain\Team::class );
        $home_team->method( 'get_club_id' )->willReturn( 100 );
        $away_team = $this->createStub( \Racketmanager\Domain\Team::class );
        $away_team->method( 'get_club_id' )->willReturn( 200 );
        $this->team_repository->method( 'find_by_id' )->willReturnMap( [
            [ 10, $home_team ],
            [ 20, $away_team ],
        ] );

        $player_m = new \Racketmanager\Domain\Player( (object) [ 'display_name' => 'Male Player', 'ID' => 1001, 'year_of_birth' => null ] );
        $player_f = new \Racketmanager\Domain\Player( (object) [ 'display_name' => 'Female Player', 'ID' => 2001, 'year_of_birth' => null ] );

        $this->player_repository->method( 'find_club_players_with_details' )->willReturnCallback( function( $club_id, $status, $gender ) {
            if ( $gender === 'm' ) {
                return [ (object) [ 'registration_id' => 5001, 'user_id' => 1001 ] ];
            }
            if ( $gender === 'f' ) {
                return [ (object) [ 'registration_id' => 6001, 'user_id' => 2001 ] ];
            }
            return [];
        });
        
        $this->player_repository->method( 'find' )->willReturnCallback( function($id) use ($player_m, $player_f) {
            if ($id === 1001) return $player_m;
            if ($id === 2001) return $player_f;
            return null;
        });

        // Scenario 1: MD
        $dto_md = new Fixture_Details_DTO( $fixture_md );
        $this->service->enrich( $dto_md );

        $this->assertCount( 1, $dto_md->club_players['home']['m'] );
        $this->assertEmpty( $dto_md->club_players['home']['f'] );

        // Scenario 2: WD
        $dto_wd = new Fixture_Details_DTO( $fixture_wd );
        $this->service->enrich( $dto_wd );

        $this->assertEmpty( $dto_wd->club_players['home']['m'], 'Men list should be empty for Ladies event' );
        $this->assertCount( 1, $dto_wd->club_players['home']['f'] );

        // Scenario 3: XD
        $dto_xd = new Fixture_Details_DTO( $fixture_xd );
        $this->service->enrich( $dto_xd );

        $this->assertCount( 1, $dto_xd->club_players['home']['m'] );
        $this->assertCount( 1, $dto_xd->club_players['home']['f'] );
    }
}
