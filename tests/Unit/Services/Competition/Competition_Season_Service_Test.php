<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Competition;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Services\Competition\Competition_Season_Service;

class Competition_Season_Service_Test extends TestCase {
	private $service;

	protected function setUp(): void {
		parent::setUp();
		$this->service = new Competition_Season_Service();
	}

	public function test_calculate_phase_end() {
		$data = [
			'date_end' => '2020-01-01',
		];
		$this->assertEquals( 'end', $this->service->calculate_phase( $data ) );
	}

	public function test_calculate_phase_start() {
		$data = [
			'date_start' => gmdate( 'Y-m-d', strtotime( '-1 day' ) ),
			'date_end'   => gmdate( 'Y-m-d', strtotime( '+1 day' ) ),
		];
		$this->assertEquals( 'start', $this->service->calculate_phase( $data ) );
	}

	public function test_calculate_phase_open() {
		$data = [
			'date_open'  => gmdate( 'Y-m-d', strtotime( '-1 day' ) ),
			'date_start' => gmdate( 'Y-m-d', strtotime( '+5 days' ) ),
		];
		$this->assertEquals( 'open', $this->service->calculate_phase( $data ) );
	}

	public function test_calculate_phase_pending() {
		$data = [
			'date_open' => gmdate( 'Y-m-d', strtotime( '+1 day' ) ),
		];
		$this->assertEquals( 'pending', $this->service->calculate_phase( $data ) );
	}
}
