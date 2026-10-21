<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Competition;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\DTO\Competition\Competition_Summary_DTO;
use stdClass;

class Competition_Summary_DTO_Test extends TestCase {
	public function test_instantiation_from_stdclass_with_all_fields(): void {
		$raw               = new stdClass();
		$raw->id           = 42;
		$raw->name         = 'Summer League';
		$raw->age_group    = 'adults';
		$raw->type         = 'league';
		$raw->season_count = 5;
		$raw->event_count  = 12;

		$dto = new Competition_Summary_DTO( $raw );

		$this->assertSame( 42, $dto->id );
		$this->assertSame( 'Summer League', $dto->name );
		$this->assertSame( 'adults', $dto->age_group );
		$this->assertSame( 'league', $dto->type );
		$this->assertSame( 5, $dto->season_count );
		$this->assertSame( 12, $dto->event_count );

		$this->assertSame( 42, $dto->get_id() );
		$this->assertSame( 'Summer League', $dto->get_name() );
		$this->assertSame( 'adults', $dto->get_age_group() );
		$this->assertSame( 'league', $dto->get_type() );
		$this->assertSame( 5, $dto->get_season_count() );
		$this->assertSame( 12, $dto->get_event_count() );
	}

	public function test_instantiation_from_associative_array(): void {
		$data = array(
			'id'           => '10',
			'name'         => 'Winter Cup',
			'age_group'    => 'juniors',
			'type'         => 'cup',
			'season_count' => '3',
			'event_count'  => '7',
		);

		$dto = new Competition_Summary_DTO( $data );

		$this->assertSame( 10, $dto->get_id() );
		$this->assertSame( 'Winter Cup', $dto->get_name() );
		$this->assertSame( 'juniors', $dto->get_age_group() );
		$this->assertSame( 'cup', $dto->get_type() );
		$this->assertSame( 3, $dto->get_season_count() );
		$this->assertSame( 7, $dto->get_event_count() );
	}

	public function test_instantiation_with_competition_type_enum(): void {
		$raw               = new stdClass();
		$raw->id           = 99;
		$raw->name         = 'Annual Tournament';
		$raw->type         = Competition_Type::TOURNAMENT;
		$raw->age_group    = null;
		$raw->season_count = 1;
		$raw->event_count  = 4;

		$dto = new Competition_Summary_DTO( $raw );

		$this->assertSame( 'tournament', $dto->get_type() );
		$this->assertSame( 'tournament', $dto->type );
		$this->assertNull( $dto->get_age_group() );
	}

	public function test_instantiation_with_defaults_and_empty_values(): void {
		$dto = new Competition_Summary_DTO( array() );

		$this->assertSame( 0, $dto->get_id() );
		$this->assertSame( '', $dto->get_name() );
		$this->assertNull( $dto->get_age_group() );
		$this->assertSame( '', $dto->get_type() );
		$this->assertSame( 0, $dto->get_season_count() );
		$this->assertSame( 0, $dto->get_event_count() );

		$raw            = new stdClass();
		$raw->age_group = '';
		$dto_empty_age  = new Competition_Summary_DTO( $raw );

		$this->assertNull( $dto_empty_age->get_age_group() );
	}
}
