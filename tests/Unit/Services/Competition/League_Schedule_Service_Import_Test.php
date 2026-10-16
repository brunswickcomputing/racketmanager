<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Competition;

use PHPUnit\Framework\TestCase;
use Racketmanager\Services\Competition\League_Schedule_Service;
use Racketmanager\Services\Schedule_Round_Robin;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;

class League_Schedule_Service_Import_Test extends TestCase {
	public function test_schedule_service_imports_resolve(): void {
		$this->assertTrue( class_exists( Util::class ) );
		$this->assertTrue( class_exists( Util_Lookup::class ) );

		$service = new League_Schedule_Service( null, new Schedule_Round_Robin() );
		$this->assertInstanceOf( League_Schedule_Service::class, $service );
	}
}
