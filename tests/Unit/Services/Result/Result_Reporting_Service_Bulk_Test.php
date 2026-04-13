<?php

namespace Racketmanager\Tests\Unit\Services\Result;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Services\Result\Result_Reporting_Service;
use stdClass;

class Result_Reporting_Service_Bulk_Test extends TestCase {
	private MockObject $service;

	protected function setUp(): void {
		parent::setUp();
		// add a Partial mock to avoid mocking all dependencies of report_result
		$this->service = $this->getMockBuilder( Result_Reporting_Service::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'report_result' ) )
			->getMock();
	}

	#[AllowMockObjectsWithoutExpectations]
	public function test_report_fixtures_collects_data_from_all_fixtures() {
		$fixture1 = $this->createStub( Fixture::class );
		$fixture2 = $this->createStub( Fixture::class );

		$report1 = new stdClass();
		$report1->tournament = 'T1';
		$report1->code = 'C1';
		$report1->organiser = 'O1';
		$report1->venue = 'V1';
		$report1->event_name = 'E1';
		$report1->grade = 'G1';
		$report1->event_start_date = 'D1';
		$report1->event_end_date = 'D2';
		$report1->age_group = 'A1';
		$report1->event_type = 'ET1';
		$report1->gender = 'M';
		$report1->draw_name = 'DN1';
		$report1->draw_type = 'DT1';
		$report1->draw_stage = 'DS1';
		$report1->draw_size = '8';
		$report1->round = 'R1';
		$report1->matches = array(
			(object) array(
				'match' => 'M1',
				'winner_name' => 'W1',
				'winner_lta_no' => 'L1',
				'winnerpartner' => 'P1',
				'winnerpartner_lta_no' => 'LP1',
				'loser_name' => 'L1',
				'loser_lta_no' => 'LL1',
				'loserpartner' => 'LP1',
				'loserpartner_lta_no' => 'LLP1',
				'score' => '6-0',
				'score_code' => 'S1',
				'match_date' => 'MD1',
				'set1team1' => '1', 'set1team2' => '2',
				'set2team1' => '3', 'set2team2' => '4',
				'set3team1' => '5', 'set3team2' => '6',
				'set4team1' => '7', 'set4team2' => '8',
				'set5team1' => '9', 'set5team2' => '10',
				'tiebreak1' => 'T1', 'tiebreak2' => 'T2', 'tiebreak3' => 'T3', 'tiebreak4' => 'T4', 'tiebreak5' => 'T5',
			)
		);

		$this->service->method( 'report_result' )
			->willReturnCallback( function( $fixture ) use ( $fixture1, $fixture2, $report1 ) {
				if ( $fixture === $fixture1 ) {
					return $report1;
				}
				return null;
			} );

		$results = $this->service->report_fixtures( array( $fixture1, $fixture2 ) );

		$this->assertCount( 1, $results );
		$this->assertEquals( 'T1', $results[0][0] );
		$this->assertEquals( 'M1', $results[0][16] );
		$this->assertEquals( 'W1', $results[0][17] );
	}
}
