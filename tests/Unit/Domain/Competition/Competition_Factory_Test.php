<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition_Factory;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\DTO\Competition\Competition_Hydration_DTO;
use Racketmanager\Domain\Competition\Competition;

class Competition_Factory_Test extends TestCase {
	public function test_create_from_dto() {
		$dto = new Competition_Hydration_DTO(
			id: 123,
			name: 'Test Competition',
			type: Competition_Type::LEAGUE,
			age_group: 'Adults',
			seasons: ['2026' => ['name' => '2026', 'num_match_days' => 10]],
			settings: ['mode' => 'default']
		);

		$competition = Competition_Factory::create_from_dto( $dto );

		$this->assertInstanceOf( Competition::class, $competition );
		$this->assertEquals( 123, $competition->get_id() );
		$this->assertEquals( 'Test Competition', $competition->get_name() );
		$this->assertEquals( 'league', $competition->get_type() );
		$this->assertEquals( 'Adults', $competition->get_age_group() );
		$this->assertEquals( ['2026' => ['name' => '2026', 'num_match_days' => 10]], $competition->get_seasons() );
		$this->assertEquals( 10, $competition->num_match_days );
	}

	public function test_from_object_with_json_strings() {
		$row = (object) [
			'id'        => '456',
			'name'      => 'JSON Comp',
			'type'      => 'cup',
			'age_group' => 'Juniors',
			'seasons'   => json_encode( ['2027' => ['name' => '2027', 'num_match_days' => 5]] ),
			'settings'  => json_encode( ['mode' => 'championship'] ),
		];

		$competition = Competition_Factory::from_object( $row );

		$this->assertInstanceOf( Competition::class, $competition );
		$this->assertEquals( 456, $competition->get_id() );
		$this->assertEquals( ['2027' => ['name' => '2027', 'num_match_days' => 5]], $competition->get_seasons() );
		$this->assertEquals( 5, $competition->num_match_days );
		$this->assertTrue( $competition->is_championship );
	}
}
