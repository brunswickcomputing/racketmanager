<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Integration\Services\Result;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Team;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Result\Result_Reporting_Service;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class Result_Reporting_Service_Integration_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['racketmanager']            = new stdClass();
		$GLOBALS['racketmanager']->site_name = 'Integration Site';
	}

	public function test_report_result_with_actual_match_object(): void {
		$fixture_data = (object) [
			'id'                  => 1,
			'league_id'           => 101,
			'season'              => '2024',
			'date'                => '2024-06-01 14:00:00',
			'home_team'           => '10',
			'away_team'           => '20',
			'winner_id'           => 10,
			'status'              => 0, // Not walkover
			'match_day'           => 5,
			'is_walkover'         => 0,
			'is_retired'          => 0,
			'is_shared'           => 0,
			'is_cancelled'        => 0,
			'final'               => '',
		];

		$fixture = new Fixture( $fixture_data );

		$league = $this->getMockBuilder( League::class )
			->disableOriginalConstructor()
			->getMock();
		$league->method( 'get_id' )->willReturn( 101 );
		$league->method( 'get_event_id' )->willReturn( 201 );
		$league->title = 'Int League';
		$league->num_teams_total = 10;
		$league->num_rubbers = 0;

		$event = $this->getMockBuilder( Event::class )
			->disableOriginalConstructor()
			->getMock();
		$event->method( 'get_id' )->willReturn( 201 );
		$event->method( 'get_competition_id' )->willReturn( 301 );
		$event->method( 'get_season_by_name' )->willReturn( [ 'grade' => 3 ] );
		$event->name = 'Int Event';
		$event->age_limit = 'Open';
		$event->type = 'MS';

		$competition = $this->getMockBuilder( Competition::class )
			->disableOriginalConstructor()
			->getMock();
		$competition->method( 'get_id' )->willReturn( 301 );
		$competition->method( 'get_season_by_name' )->willReturn( [
			'competition_code' => 'INT-COMP',
			'date_start'       => '2024-01-01',
			'date_end'         => '2024-12-31',
		] );
		$competition->name = 'Int Competition';
		$competition->type = 'league';
		$competition->competition_code = 'COMP-CODE';
		$competition->date_start = '2024-01-01';
		$competition->date_end = '2024-12-31';
		$competition->settings = [ 'grade' => 3 ];

		$home_team = $this->getMockBuilder( Team::class )
			->disableOriginalConstructor()
			->getMock();
		$home_team->method( 'get_players' )->willReturn( [ (object)[ 'display_name' => 'Int Player 1', 'btm' => 'BTM1' ] ] );

		$away_team = $this->getMockBuilder( Team::class )
			->disableOriginalConstructor()
			->getMock();
		$away_team->method( 'get_players' )->willReturn( [ (object)[ 'display_name' => 'Int Player 2', 'btm' => 'BTM2' ] ] );

		$league_repo = $this->createMock( League_Repository_Interface::class );
		$league_repo->method( 'find_by_id' )->with( 101 )->willReturn( $league );

		$event_repo = $this->createMock( Event_Repository_Interface::class );
		$event_repo->method( 'find_by_id' )->with( 201 )->willReturn( $event );

		$competition_repo = $this->createMock( Competition_Repository_Interface::class );
		$competition_repo->method( 'find_by_id' )->with( 301 )->willReturn( $competition );

		$rubber_repo = $this->createStub( Rubber_Repository_Interface::class );
		$rubber_repo->method( 'find_by_fixture_id' )->willReturn( [] );

		$team_repo = $this->createStub( Team_Repository_Interface::class );
		$team_repo->method( 'find_by_id' )->willReturnMap( [
			[ 10, $home_team ],
			[ 20, $away_team ],
		] );

		$repository_provider = new Repository_Provider(
			league_repository: $league_repo,
			event_repository: $event_repo,
			competition_repository: $competition_repo,
			team_repository: $team_repo,
			rubber_repository: $rubber_repo
		);

		$service = new Result_Reporting_Service( $repository_provider );
		$result = $service->report_result( $fixture );

		$this->assertEquals( 'INT-COMP', $result->code );
		$this->assertEquals( 'Integration Site Int Competition', $result->tournament );
		$this->assertEquals( 'Int Player 1', $result->matches[0]->winner_name );
	}
}
