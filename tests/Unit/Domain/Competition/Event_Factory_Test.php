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

	public function test_constructor_accepts_only_the_hydration_dto(): void {
		$this->expectException( \TypeError::class );
		new Event( (object) array( 'id' => 1, 'name' => 'Raw' ) );
	}

	public function test_constructor_from_dto_merges_settings_over_columns_and_derives_competition_id(): void {
		$competition = $this->sample_competition( 9 );
		$dto         = new Event_Hydration_DTO(
			id: 11,
			name: 'Mixed',
			type: 'XD',
			num_sets: 3,
			settings: array( 'num_sets' => 5, 'offset' => '2', 'scoring' => 'TB10' ),
			seasons: array( '2026' => array( 'name' => '2026' ) )
		);

		$event = new Event( $dto, $competition );

		$this->assertSame( 5, $event->num_sets, 'settings keys take precedence over table columns' );
		$this->assertSame( 2, $event->offset );
		$this->assertSame( 'TB10', $event->scoring );
		$this->assertSame( array( 'num_sets', 'offset', 'scoring' ), $event->settings_keys );
		$this->assertSame( 9, $event->competition_id, 'competition_id is derived from the Competition when the DTO value is null' );
		$this->assertSame( $competition, $event->competition );
		$this->assertSame( 1, $event->num_seasons );
		$this->assertFalse( method_exists( $dto, 'to_std_class' ) );
	}

	public function test_lenient_from_object_skips_invariants_and_matches_factory_hydration(): void {
		$lenient = Event::from_object( (object) array( 'id' => 1 ) );
		$this->assertInstanceOf( Event::class, $lenient );
		$this->assertSame( '', $lenient->name );
		$this->assertNull( $lenient->num_sets );

		$row = (object) array(
			'id'             => '7',
			'name'           => 'Mixed Doubles',
			'type'           => 'XD',
			'competition_id' => '3',
			'num_sets'       => '3',
			'settings'       => serialize( array( 'num_rubbers' => '4', 'age_limit' => 'open' ) ),
			'seasons'        => '[]',
			'unknown_column' => 'dropped',
		);
		$this->assertEquals( Event_Factory::from_object( $row ), Event::from_object( $row ) );
		$this->assertFalse( property_exists( Event::from_object( $row ), 'unknown_column' ) );
	}

	public function test_lenient_from_object_uses_competition_attached_to_row(): void {
		$competition      = $this->sample_competition( 4 );
		$row              = (object) array( 'id' => 2, 'name' => 'Open' );
		$row->competition = $competition;

		$event = Event::from_object( $row );

		$this->assertSame( $competition, $event->competition );
		$this->assertSame( 4, $event->competition_id );
	}

	public function test_empty_constructor_yields_empty_event(): void {
		$event = new Event();
		$this->assertSame( 0, $event->id );
		$this->assertSame( '', $event->name );
		$this->assertSame( array(), $event->seasons );
	}

	/**
	 * Regression: `events.num_sets` / `events.num_rubbers` are `int DEFAULT NULL`. The validating path must
	 * keep the legacy 3 / 0 defaults for NULL rows (callers rely on `get_num_sets(): int`), while the
	 * lenient `Event::from_object()` mirrors the raw constructor and leaves them null.
	 */
	public function test_factory_restores_legacy_num_sets_and_num_rubbers_defaults_for_null_row_columns(): void {
		$row = (object) array(
			'id'             => '5',
			'name'           => 'Ev',
			'type'           => 'MS',
			'competition_id' => '2',
			'num_sets'       => null,
			'num_rubbers'    => null,
			'settings'       => serialize( array() ),
			'seasons'        => '[]',
		);

		$validated = Event_Factory::from_object( $row );
		$this->assertSame( 3, $validated->num_sets );
		$this->assertSame( 0, $validated->num_rubbers );
		$this->assertSame( 3, $validated->get_num_sets() );
		$this->assertSame( 0, $validated->get_num_rubbers() );

		$lenient = Event::from_object( $row );
		$this->assertNull( $lenient->num_sets );
		$this->assertNull( $lenient->num_rubbers );

		// Missing columns behave like NULL columns on the factory path.
		$missing = Event_Factory::from_object( (object) array( 'id' => 6, 'name' => 'Ev', 'type' => 'MS', 'competition_id' => 2 ) );
		$this->assertSame( 3, $missing->num_sets );
		$this->assertSame( 0, $missing->num_rubbers );

		// Settings still take precedence over the restored column defaults.
		$row->settings = serialize( array( 'num_sets' => '5', 'num_rubbers' => '6' ) );
		$overridden    = Event_Factory::from_object( $row );
		$this->assertSame( 5, $overridden->num_sets );
		$this->assertSame( 6, $overridden->num_rubbers );

		// Explicit column values are never replaced.
		$row->settings    = serialize( array() );
		$row->num_sets    = '1';
		$row->num_rubbers = '2';
		$explicit         = Event_Factory::from_object( $row );
		$this->assertSame( 1, $explicit->num_sets );
		$this->assertSame( 2, $explicit->num_rubbers );

		$dto = new Event_Hydration_DTO( id: 7, name: 'Ev', type: 'MS', competition_id: 2, num_sets: 0, num_rubbers: null );
		$this->assertSame( 0, Event_Factory::create_from_dto( $dto )->num_sets );
		$this->assertSame( 0, Event_Factory::create_from_dto( $dto )->num_rubbers );
	}

	public function test_lenient_empty_numeric_columns_keep_legacy_zero_coercion(): void {
		$base = array(
			'id'             => 5,
			'name'           => 'Ev',
			'type'           => 'MS',
			'competition_id' => 2,
		);

		$empty_row = (object) array_merge(
			$base,
			array( 'num_sets' => '', 'num_rubbers' => '' )
		);
		$lenient = Event::from_object( $empty_row );
		$this->assertSame( 0, $lenient->num_sets );
		$this->assertSame( 0, $lenient->num_rubbers );

		// The validating path still treats empty values as missing database defaults.
		$validated = Event_Factory::from_object( $empty_row );
		$this->assertSame( 3, $validated->num_sets );
		$this->assertSame( 0, $validated->num_rubbers );

		$settings_row = (object) array_merge(
			(array) $empty_row,
			array( 'settings' => serialize( array( 'num_sets' => '5', 'num_rubbers' => '6' ) ) )
		);
		$settings_event = Event::from_object( $settings_row );
		$this->assertSame( 5, $settings_event->num_sets, 'settings continue to override empty row columns' );
		$this->assertSame( 6, $settings_event->num_rubbers, 'settings continue to override empty row columns' );

		$null_row = (object) array_merge(
			$base,
			array( 'num_sets' => null, 'num_rubbers' => null )
		);
		$null_values = Event::from_object( $null_row );
		$this->assertNull( $null_values->num_sets );
		$this->assertNull( $null_values->num_rubbers );

		$missing_values = Event::from_object( (object) $base );
		$this->assertNull( $missing_values->num_sets );
		$this->assertNull( $missing_values->num_rubbers );
	}
}
