<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Factory;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\Event_Factory;
use Racketmanager\Domain\DTO\Competition\Event_Hydration_DTO;
use stdClass;

final class Event_Factory_Test extends TestCase {
	private function sample_competition( int $id = 1 ): Competition {
		return Competition_Factory::from_object( (object) array(
			'id'   => $id,
			'name' => 'Main Competition',
			'type' => 'league',
		) );
	}

	public function test_from_array_with_missing_type_throws_invalid_argument_exception(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Event type is required.' );
		Event_Factory::from_array( array( 'id' => 3, 'name' => 'X' ), $this->sample_competition() );
	}

	public function test_from_array_with_empty_name_throws_invalid_argument_exception(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Event name is required.' );
		Event_Factory::from_array( array( 'id' => 3, 'name' => '   ', 'type' => 'singles' ), $this->sample_competition() );
	}

	public function test_from_array_with_missing_competition_id_and_no_competition_throws_invalid_argument_exception(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Event competition_id is required.' );
		Event_Factory::from_array( array( 'id' => 3, 'name' => 'Men Singles', 'type' => 'MS' ), null );
	}

	public function test_from_array_with_mismatched_competition_id_throws_invalid_argument_exception(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Event competition_id 5 does not match Competition id 10.' );
		Event_Factory::from_array(
			array(
				'id'             => 3,
				'name'           => 'Men Singles',
				'type'           => 'MS',
				'competition_id' => 5,
			),
			$this->sample_competition( 10 )
		);
	}

	public function test_from_array_with_negative_id_throws_invalid_argument_exception(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Event id must be non-negative.' );
		Event_Factory::from_array(
			array(
				'id'             => -1,
				'name'           => 'Men Singles',
				'type'           => 'MS',
				'competition_id' => 1,
			)
		);
	}

	public function test_from_array_with_valid_data_and_competition_object_hydrates_event(): void {
		$competition = $this->sample_competition( 2 );
		$event       = Event_Factory::from_array(
			array(
				'id'   => 4,
				'name' => 'Women Doubles',
				'type' => 'WD',
			),
			$competition
		);

		$this->assertInstanceOf( Event::class, $event );
		$this->assertSame( 4, $event->id );
		$this->assertSame( 'Women Doubles', $event->name );
		$this->assertSame( 'WD', $event->type );
		$this->assertSame( 2, $event->competition_id );
		$this->assertSame( $competition, $event->competition );
	}

	public function test_from_array_with_unsaved_event_preserves_zero_id(): void {
		$event = Event_Factory::from_array(
			array(
				'name'           => 'New Event',
				'type'           => 'singles',
				'competition_id' => 1,
			)
		);

		$this->assertInstanceOf( Event::class, $event );
		$this->assertSame( 0, $event->id );
		$this->assertSame( 'New Event', $event->name );
		$this->assertSame( 'singles', $event->type );
		$this->assertSame( 1, $event->competition_id );
	}

	public function test_from_object_hydrates_event(): void {
		$row                 = new stdClass();
		$row->id             = 7;
		$row->name           = 'Mixed Doubles';
		$row->type           = 'XD';
		$row->competition_id = 3;
		$row->num_sets       = 3;
		$row->num_rubbers    = 4;

		$event = Event_Factory::from_object( $row );
		$this->assertInstanceOf( Event::class, $event );
		$this->assertSame( 7, $event->id );
		$this->assertSame( 'Mixed Doubles', $event->name );
		$this->assertSame( 'XD', $event->type );
		$this->assertSame( 3, $event->competition_id );
		$this->assertSame( 3, $event->num_sets );
		$this->assertSame( 4, $event->num_rubbers );
	}

	public function test_create_from_dto_hydrates_event(): void {
		$dto = new Event_Hydration_DTO(
			id: 9,
			name: 'Junior Boys',
			type: 'BS',
			competition_id: 1
		);

		$event = Event_Factory::create_from_dto( $dto );
		$this->assertInstanceOf( Event::class, $event );
		$this->assertSame( 9, $event->id );
		$this->assertSame( 'Junior Boys', $event->name );
		$this->assertSame( 'BS', $event->type );
		$this->assertSame( 1, $event->competition_id );
	}
}
