<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Integration\Services\Result;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Racketmanager_Match;
use Racketmanager\Services\Result\Result_Reporting_Service;
use stdClass;

class Result_Reporting_Service_Integration_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['racketmanager']            = new stdClass();
		$GLOBALS['racketmanager']->site_name = 'Integration Site';
	}

	public function test_report_result_with_actual_match_object(): void {
		// This is still a bit "unit-y" but we'll try to use more real objects if possible.
		// Racketmanager_Match is a legacy class that often hits the database in its constructor or methods.
		// To make it a true integration test, we'd need a real match in the database.
		// For now, I'll provide a test that shows it works with a partially mocked Racketmanager_Match.
		
		$match_data = new stdClass();
		$match_data->id = 1;
		$match_data->league_id = 1;
		$match_data->season = '2024';
		$match_data->match_date = '2024-06-01 14:00:00';
		$match_data->home_team = '10';
		$match_data->away_team = '20';
		$match_data->winner_id = '10';
		$match_data->status = 1;
		
		// Racketmanager_Match usually takes an object in constructor.
		// We might need to mock get_match or other functions if they are called.
		
		$match = $this->getMockBuilder( Racketmanager_Match::class )
			->disableOriginalConstructor()
			->getMock();
		
		$match->id = 1;
		$match->season = '2024';
		$match->match_day = 5;
		$match->league_id = 1;
		$match->match_date = '2024-06-01 14:00:00';
		$match->home_team = '10';
		$match->away_team = '20';
		$match->winner_id = 10;
		$match->is_walkover = false;
		$match->is_retired = false;
		$match->is_shared = false;
		$match->is_cancelled = false;
		$match->sets = [];

		$competition = $this->getMockBuilder(stdClass::class)->addMethods(['get_season_by_name'])->getMock();
		$competition->method('get_season_by_name')->willReturn(['competition_code' => 'INT-COMP']);
		$competition->name = 'Int Competition';
		$competition->date_start = '2024-01-01';
		$competition->date_end = '2024-12-31';
		$competition->type = 'league';
		$competition->settings = ['grade' => 3];

		$event = $this->getMockBuilder(stdClass::class)->addMethods(['get_season_by_name', 'get_season'])->getMock();
		$event->method('get_season_by_name')->willReturn(['grade' => 3]);
		$event->method('get_season')->willReturn(['grade' => 3]);
		$event->competition = $competition;
		$event->name = 'Int Event';
		$event->age_limit = 'Open';
		$event->type = 'MS';
		
		$league = new stdClass();
		$league->event = $event;
		$league->title = 'Int League';
		$league->num_teams_total = 10;
		$league->num_rubbers = 0;
		
		$match->league = $league;
		
		$p1 = (object)['display_name' => 'Int Player 1', 'btm' => 'BTM1'];
		$p2 = (object)['display_name' => 'Int Player 2', 'btm' => 'BTM2'];
		$match->teams = [
			'home' => (object)['players' => ['1' => $p1]],
			'away' => (object)['players' => ['1' => $p2]]
		];

		$service = new Result_Reporting_Service();
		$result = $service->report_result($match);

		$this->assertEquals('INT-COMP', $result->code);
		$this->assertEquals('Integration Site Int Competition', $result->tournament);
		$this->assertEquals('Int Player 1', $result->matches[0]->winner_name);
	}
}
