<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Result;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Team;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\Rubber_Repository;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\Competition_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Services\Result\Result_Reporting_Service;
use stdClass;

if ( ! function_exists( 'mysql2date' ) ) {
	function mysql2date( $format, $date ) {
		return date( $format, strtotime( $date ) );
	}
}

class Result_Reporting_Service_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		if ( ! isset( $GLOBALS['racketmanager'] ) ) {
			$GLOBALS['racketmanager']            = new stdClass();
		}
		$GLOBALS['racketmanager']->site_name = 'Test Site';
	}

	public function test_report_result_returns_null_if_no_competition_code(): void {
		$fixture = $this->createMock( Fixture::class );
		$fixture->method('get_league_id')->willReturn(10);
		$fixture->method('get_season')->willReturn('2024');

		$league = $this->createMock( League::class );
		$league->method('get_event_id')->willReturn(20);

		$event = $this->createMock( Event::class );
		$event->method('get_competition_id')->willReturn(30);
		$event->method('get_season_by_name')->willReturn(null);

		$competition = $this->createMock( Competition::class );
		$competition->method('get_season_by_name')->willReturn(null);
		$competition->competition_code = null;

		$league_repo = $this->createMock( League_Repository::class );
		$league_repo->method('find_by_id')->willReturn($league);

		$event_repo = $this->createMock( Event_Repository::class );
		$event_repo->method('find_by_id')->willReturn($event);

		$competition_repo = $this->createMock( Competition_Repository::class );
		$competition_repo->method('find_by_id')->willReturn($competition);

		$repository_provider = $this->createMock( Repository_Provider::class );
		$repository_provider->method('get_league_repository')->willReturn($league_repo);
		$repository_provider->method('get_event_repository')->willReturn($event_repo);
		$repository_provider->method('get_competition_repository')->willReturn($competition_repo);

		$service = new Result_Reporting_Service($repository_provider);
		
		$result = $service->report_result( $fixture, null );
		$this->assertNull( $result );
	}

	public function test_report_result_basic_structure(): void {
		$fixture = $this->createMock( Fixture::class );
		$fixture->method('get_id')->willReturn(1);
		$fixture->method('get_league_id')->willReturn(10);
		$fixture->method('get_season')->willReturn('2024');
		$fixture->method('get_match_day')->willReturn(1);
		$fixture->method('get_home_team')->willReturn('1');
		$fixture->method('get_away_team')->willReturn('2');
		$fixture->method('get_winner_id')->willReturn(1);
		$fixture->method('get_date')->willReturn('2024-05-01 10:00:00');
		$fixture->method('is_walkover')->willReturn(false);
		$fixture->method('is_retired')->willReturn(false);
		$fixture->method('is_shared')->willReturn(false);
		$fixture->method('is_cancelled')->willReturn(false);
		$fixture->method('get_custom')->willReturn([]);

		$competition = $this->createMock( Competition::class );
		$competition->method('get_season_by_name')->willReturn(['competition_code' => 'COMP123']);
		$competition->name = 'Test Competition';
		$competition->date_start = '2024-01-01';
		$competition->date_end = '2024-12-31';
		$competition->type = 'league';
		$competition->settings = ['grade' => 4];

		$event = $this->createMock( Event::class );
		$event->method('get_competition_id')->willReturn(30);
		$event->method('get_season_by_name')->willReturn(['grade' => 4]);
		$event->name = 'Test Event';
		$event->age_limit = 'Open';
		$event->type = 'MS';
		$event->primary_league = 10;

		$league = $this->createMock( League::class );
		$league->method('get_event_id')->willReturn(20);
		$league->title = 'Test League';
		$league->num_teams_total = 8;
		$league->num_rubbers = 0;

		$player1 = new stdClass();
		$player1->display_name = 'Player One';
		$player1->btm = '123456';
		
		$player2 = new stdClass();
		$player2->display_name = 'Player Two';
		$player2->btm = '654321';

		$home_team = $this->createMock( Team::class );
		$home_team->method('get_players')->willReturn(['1' => $player1]);
		
		$away_team = $this->createMock( Team::class );
		$away_team->method('get_players')->willReturn(['1' => $player2]);

		$league_repo = $this->createMock( League_Repository::class );
		$league_repo->method('find_by_id')->willReturn($league);

		$event_repo = $this->createMock( Event_Repository::class );
		$event_repo->method('find_by_id')->willReturn($event);

		$competition_repo = $this->createMock( Competition_Repository::class );
		$competition_repo->method('find_by_id')->willReturn($competition);

		$team_repo = $this->createMock( Team_Repository::class );
		$team_repo->method('find_by_id')->willReturnMap([
			[1, $home_team],
			[2, $away_team],
		]);

		$repository_provider = $this->createMock( Repository_Provider::class );
		$repository_provider->method('get_league_repository')->willReturn($league_repo);
		$repository_provider->method('get_event_repository')->willReturn($event_repo);
		$repository_provider->method('get_competition_repository')->willReturn($competition_repo);
		$repository_provider->method('get_team_repository')->willReturn($team_repo);

		$service = new Result_Reporting_Service($repository_provider);
		
		$result = $service->report_result( $fixture );

		$this->assertNotNull( $result );
		$this->assertEquals( 'COMP123', $result->code );
		$this->assertEquals( 'Test Site Test Competition', $result->tournament );
		$this->assertCount( 1, $result->matches );
		$this->assertEquals( 'Player One', $result->matches[0]->winner_name );
	}

	public function test_report_result_with_rubbers(): void {
		$fixture = $this->createMock( Fixture::class );
		$fixture->method('get_id')->willReturn(1);
		$fixture->method('get_league_id')->willReturn(10);
		$fixture->method('get_season')->willReturn('2024');
		$fixture->method('get_home_team')->willReturn('1');
		$fixture->method('get_away_team')->willReturn('2');
		$fixture->method('get_date')->willReturn('2024-05-01 10:00:00');
		$fixture->method('is_walkover')->willReturn(false);
		$fixture->method('is_retired')->willReturn(false);
		$fixture->method('is_shared')->willReturn(false);
		$fixture->method('is_cancelled')->willReturn(false);
		$fixture->method('is_withdrawn')->willReturn(false);

		$competition = $this->createMock( Competition::class );
		$competition->method('get_season_by_name')->willReturn(['competition_code' => 'COMP123']);
		$competition->name = 'Test Competition';
		$competition->date_start = '2024-01-01';
		$competition->date_end = '2024-12-31';
		$competition->type = 'league';
		$competition->settings = ['grade' => 4];

		$event = $this->createMock( Event::class );
		$event->method('get_competition_id')->willReturn(30);
		$event->method('get_season_by_name')->willReturn(['grade' => 4]);
		$event->name = 'Test Event';
		$event->age_limit = 'Open';
		$event->type = 'MS';

		$league = $this->createMock( League::class );
		$league->method('get_event_id')->willReturn(20);
		$league->title = 'Test League';
		$league->num_teams_total = 8;
		$league->num_rubbers = 1;

		$player1 = new stdClass();
		$player1->display_name = 'Rubber Winner';
		$player1->btm = '111';
		
		$player2 = new stdClass();
		$player2->display_name = 'Rubber Loser';
		$player2->btm = '222';

		$rubber = $this->createMock( Rubber::class );
		$rubber->method('get_id')->willReturn(101);
		$rubber->method('get_winner_id')->willReturn(1);
		$rubber->method('get_loser_id')->willReturn(2);
		$rubber->method('get_status')->willReturn(1);
		$rubber->method('is_walkover')->willReturn(false);
		$rubber->method('is_shared')->willReturn(false);
		$rubber->players = [
			'home' => ['1' => $player1],
			'away' => ['1' => $player2],
		];

		$league_repo = $this->createMock( League_Repository::class );
		$league_repo->method('find_by_id')->willReturn($league);

		$event_repo = $this->createMock( Event_Repository::class );
		$event_repo->method('find_by_id')->willReturn($event);

		$competition_repo = $this->createMock( Competition_Repository::class );
		$competition_repo->method('find_by_id')->willReturn($competition);

		$rubber_repo = $this->createMock( Rubber_Repository::class );
		$rubber_repo->method('find_by_fixture_id')->willReturn([$rubber]);

		$repository_provider = $this->createMock( Repository_Provider::class );
		$repository_provider->method('get_league_repository')->willReturn($league_repo);
		$repository_provider->method('get_event_repository')->willReturn($event_repo);
		$repository_provider->method('get_competition_repository')->willReturn($competition_repo);
		$repository_provider->method('get_rubber_repository')->willReturn($rubber_repo);

		$service = new Result_Reporting_Service($repository_provider);
		
		$result = $service->report_result( $fixture );

		$this->assertNotNull( $result );
		$this->assertCount( 1, $result->matches );
		$this->assertEquals( 'Rubber Winner', $result->matches[0]->winner_name );
	}

	public function test_report_result_with_scores(): void {
		$fixture = $this->createMock( Fixture::class );
		$fixture->method('get_id')->willReturn(1);
		$fixture->method('get_league_id')->willReturn(10);
		$fixture->method('get_season')->willReturn('2024');
		$fixture->method('get_home_team')->willReturn('1');
		$fixture->method('get_away_team')->willReturn('2');
		$fixture->method('get_winner_id')->willReturn(1);
		$fixture->method('get_date')->willReturn('2024-05-01 10:00:00');
		$fixture->method('is_walkover')->willReturn(false);
		$fixture->method('is_retired')->willReturn(false);
		$fixture->method('is_shared')->willReturn(false);
		$fixture->method('is_cancelled')->willReturn(false);
		$fixture->method('get_custom')->willReturn([
			'sets' => [
				1 => ['player1' => 6, 'player2' => 4],
				2 => ['player1' => 7, 'player2' => 6, 'tiebreak' => 5],
			]
		]);

		$competition = $this->createMock( Competition::class );
		$competition->method('get_season_by_name')->willReturn(['competition_code' => 'COMP123']);
		$competition->name = 'Test Competition';
		$competition->date_start = '2024-01-01';
		$competition->date_end = '2024-12-31';
		$competition->type = 'league';
		$competition->settings = ['grade' => 4];

		$event = $this->createMock( Event::class );
		$event->method('get_competition_id')->willReturn(30);
		$event->method('get_season_by_name')->willReturn(['grade' => 4]);
		$event->name = 'Test Event';
		$event->age_limit = 'Open';
		$event->type = 'MS';

		$league = $this->createMock( League::class );
		$league->method('get_event_id')->willReturn(20);
		$league->title = 'Test League';
		$league->num_teams_total = 8;
		$league->num_rubbers = 0;

		$player1 = new stdClass();
		$player1->display_name = 'Winner';
		$player2 = new stdClass();
		$player2->display_name = 'Loser';

		$home_team = $this->createMock( Team::class );
		$home_team->method('get_players')->willReturn(['1' => $player1]);
		$away_team = $this->createMock( Team::class );
		$away_team->method('get_players')->willReturn(['1' => $player2]);

		$league_repo = $this->createMock( League_Repository::class );
		$league_repo->method('find_by_id')->willReturn($league);
		$event_repo = $this->createMock( Event_Repository::class );
		$event_repo->method('find_by_id')->willReturn($event);
		$competition_repo = $this->createMock( Competition_Repository::class );
		$competition_repo->method('find_by_id')->willReturn($competition);
		$team_repo = $this->createMock( Team_Repository::class );
		$team_repo->method('find_by_id')->willReturnMap([ [1, $home_team], [2, $away_team] ]);

		$repository_provider = $this->createMock( Repository_Provider::class );
		$repository_provider->method('get_league_repository')->willReturn($league_repo);
		$repository_provider->method('get_event_repository')->willReturn($event_repo);
		$repository_provider->method('get_competition_repository')->willReturn($competition_repo);
		$repository_provider->method('get_team_repository')->willReturn($team_repo);

		$service = new Result_Reporting_Service($repository_provider);
		$result = $service->report_result( $fixture );

		$this->assertNotNull( $result );
		$this->assertCount( 1, $result->matches );
		$this->assertEquals( '6-4 7-6(5)', $result->matches[0]->score );
		$this->assertEquals( 6, $result->matches[0]->set1team1 );
		$this->assertEquals( 4, $result->matches[0]->set1team2 );
		$this->assertEquals( 7, $result->matches[0]->set2team1 );
		$this->assertEquals( 6, $result->matches[0]->set2team2 );
		$this->assertEquals( 5, $result->matches[0]->tiebreak2 );
	}
}
