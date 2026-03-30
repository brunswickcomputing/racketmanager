<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Result;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Racketmanager_Match;
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
			$GLOBALS['racketmanager']->site_name = 'Test Site';
		}
	}

	public function test_report_result_returns_null_if_no_competition_code(): void {
		$match         = $this->createMock( Racketmanager_Match::class );
		$competition = $this->getMockBuilder(stdClass::class)->addMethods(['get_season_by_name'])->getMock();
		$competition->method('get_season_by_name')->willReturn(null);
		$competition->competition_code = null;

		$event = $this->getMockBuilder(stdClass::class)->addMethods(['get_season_by_name'])->getMock();
		$event->method('get_season_by_name')->willReturn(null);
		$event->competition = $competition;

		$match->league = new stdClass();
		$match->league->event = $event;
		$match->season = '2024';
		
		$service = new Result_Reporting_Service();
		$result = $service->report_result( $match, null );
		$this->assertNull( $result );
	}

	public function test_report_result_basic_structure(): void {
		$match = $this->createMock( Racketmanager_Match::class );
		$match->id = 1;
		$match->season = '2024';
		$match->match_day = 1;
		$match->league_id = 10;
		
		$competition = $this->getMockBuilder(stdClass::class)
			->addMethods(['get_season_by_name'])
			->getMock();
		$competition->method('get_season_by_name')->willReturn(['competition_code' => 'COMP123']);
		$competition->name = 'Test Competition';
		$competition->competition_code = 'COMP123';
		$competition->date_start = '2024-01-01';
		$competition->date_end = '2024-12-31';
		$competition->type = 'league';
		$competition->settings = ['grade' => 4];

		$event = $this->getMockBuilder(stdClass::class)
			->addMethods(['get_season_by_name', 'get_season'])
			->getMock();
		$event->method('get_season_by_name')->willReturn(['grade' => 4]);
		$event->method('get_season')->willReturn(['grade' => 4]);
		$event->competition = $competition;
		$event->name = 'Test Event';
		$event->age_limit = 0; // Use int
		$event->type = 'MS';
		$event->primary_league = 10;

		$league = new stdClass();
		$league->event = $event;
		$league->title = 'Test League';
		$league->num_teams_total = 8;
		$league->num_rubbers = 0;

		$player1 = new stdClass();
		$player1->display_name = 'Player One';
		$player1->btm = '123456';
		
		$player2 = new stdClass();
		$player2->display_name = 'Player Two';
		$player2->btm = '654321';

		$team_home = new stdClass();
		$team_home->players = ['1' => $player1];
		$team_away = new stdClass();
		$team_away->players = ['1' => $player2];

		$match->home_team = '1';
		$match->away_team = '2';
		$match->winner_id = 1;
		$match->match_date = '2024-05-01 10:00:00';
		$match->is_walkover = false;
		$match->is_retired = false;
		$match->is_shared = false;
		$match->is_cancelled = false;
		$match->sets = [];
		$match->league = $league;
		$match->teams = ['home' => $team_home, 'away' => $team_away];

		$service = new Result_Reporting_Service();
		$result = $service->report_result( $match );

		$this->assertNotNull( $result );
		$this->assertEquals( 'COMP123', $result->code );
		$this->assertEquals( 'Test Site Test Competition', $result->tournament );
		$this->assertCount( 1, $result->matches );
		$this->assertEquals( 'Player One', $result->matches[0]->winner_name );
	}
}
