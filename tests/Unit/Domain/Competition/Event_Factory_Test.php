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
use TypeError;

final class Event_Factory_Test extends TestCase {
	private function sample_competition( int $id = 1 ): Competition {
		return Competition_Factory::from_object(
			(object) array(
				'id'   => $id,
				'name' => 'Main Competition',
				'type' => 'league',
			)
		);
	}

	public function test_from_array_with_missing_type_throws_invalid_argument_exception(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessageIsOrContains( 'Event type is required.' );
		Event_Factory::from_array( array( 'id' => 3, 'name' => 'X' ), $this->sample_competition() );
	}

	public function test_from_array_with_empty_name_throws_invalid_argument_exception(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessageIsOrContains( 'Event name is required.' );
		Event_Factory::from_array( array( 'id' => 3, 'name' => '   ', 'type' => 'singles' ), $this->sample_competition() );
	}

	public function test_from_array_with_missing_competition_id_and_no_competition_throws_invalid_argument_exception(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessageIsOrContains( 'Event competition_id is required.' );
		Event_Factory::from_array( array( 'id' => 3, 'name' => 'Men Singles', 'type' => 'MS' ), null );
	}

	public function test_from_array_with_mismatched_competition_id_throws_invalid_argument_exception(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessageIsOrContains( 'Event competition_id 5 does not match Competition id 10.' );
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
		$this->expectExceptionMessageIsOrContains( 'Event id must be non-negative.' );
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
		$this->expectException( TypeError::class );
        $this->assertInstanceOf(
            Event::class,
            new Event( (object) array( 'id' => 1, 'name' => 'Raw' ) )
        );
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

	public function test_entity_wrappers_match_validating_factory_hydration(): void {
		$row = (object) array(
			'id'             => '7',
			'name'           => 'Mixed Doubles',
			'type'           => 'XD',
			'competition_id' => '3',
			'num_sets'       => '3',
			'settings'       => serialize( array( 'num_rubbers' => '4', 'age_limit' => 'open', 'sport' => 'badminton' ) ),
			'seasons'        => '[]',
			'unknown_column' => 'dropped',
		);
		$from_object = Event::from_object( $row );
		$this->assertEquals( Event_Factory::from_object( $row ), $from_object );
		$this->assertEquals( Event_Factory::from_array( (array) $row ), Event::from_array( (array) $row ) );
		$this->assertSame( 'badminton', $from_object->sport );
		$this->assertFalse( property_exists( $from_object, 'unknown_column' ) );
	}

	public function test_entity_wrappers_reject_missing_name_and_competition(): void {
		try {
			Event::from_object( (object) array( 'id' => 1 ) );
			$this->fail( 'Missing event name should be rejected.' );
		} catch ( InvalidArgumentException $e ) {
			$this->assertSame( 'Event name is required.', $e->getMessage() );
		}

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessageIsOrContains( 'Event competition_id is required.' );
		Event::from_array( array( 'name' => 'Open', 'type' => 'MS' ) );
	}

	public function test_entity_from_object_uses_competition_attached_to_row(): void {
		$competition      = $this->sample_competition( 4 );
		$row              = (object) array( 'id' => 2, 'name' => 'Open', 'type' => 'MS' );
		$row->competition = $competition;

		$event = Event::from_object( $row );

		$this->assertSame( $competition, $event->competition );
		$this->assertSame( 4, $event->competition_id );
		$this->assertEquals( $event, Event_Factory::from_object( $row ) );
		$this->assertEquals( $event, Event::from_array( (array) $row ) );
	}

	public function test_attached_competition_cannot_hide_mismatched_competition_id(): void {
		$row = (object) array(
			'name'           => 'Open',
			'type'           => 'MS',
			'competition_id' => 3,
			'competition'    => $this->sample_competition( 4 ),
		);

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessageIsOrContains( 'Event competition_id 3 does not match Competition id 4.' );
		Event::from_object( $row );
	}

	public function test_inherited_entity_wrappers_return_factory_event(): void {
		$subclass = new class() extends Event {};
		$data = array( 'name' => 'Open', 'type' => 'MS', 'competition_id' => 4 );

		foreach ( array( $subclass::from_object( (object) $data ), $subclass::from_array( $data ) ) as $event ) {
			$this->assertInstanceOf( Event::class, $event );
			$this->assertNotInstanceOf( $subclass::class, $event );
			$this->assertSame( 'Open', $event->name );
		}
	}

	public function test_empty_constructor_yields_empty_event(): void {
		$event = new Event();
		$this->assertSame( 0, $event->id );
		$this->assertSame( '', $event->name );
		$this->assertSame( array(), $event->seasons );
	}

	/**
	 * Regression: `events.num_sets` / `events.num_rubbers` are `int DEFAULT NULL`. The validating path must
	 * keep the legacy 3 / 0 defaults for NULL rows (callers rely on `get_num_sets(): int`).
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

		$wrapped = Event::from_object( $row );
		$this->assertSame( 3, $wrapped->num_sets );
		$this->assertSame( 0, $wrapped->num_rubbers );

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

	public function test_empty_numeric_columns_use_factory_defaults(): void {
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
		$wrapped = Event::from_object( $empty_row );
		$this->assertSame( 3, $wrapped->num_sets );
		$this->assertSame( 0, $wrapped->num_rubbers );

		// Empty values are treated as missing database defaults.
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
		$this->assertSame( 3, $null_values->num_sets );
		$this->assertSame( 0, $null_values->num_rubbers );

		$missing_values = Event::from_object( (object) $base );
		$this->assertSame( 3, $missing_values->num_sets );
		$this->assertSame( 0, $missing_values->num_rubbers );
	}

	public function test_dto_and_factory_hydrate_extended_state(): void {
		$league = \Racketmanager\Tests\Helpers\League_Test_Factory::create( id: 10, title: 'Parent Div' );
		$dto    = new Event_Hydration_DTO(
			id: 12,
			name: 'Tournament Event',
			type: 'singles',
			num_sets: 3,
			num_rubbers: 0,
			competition_id: 1,
			matches: array( array( 'id' => 101 ) ),
			stages: array( 'group', 'knockout' ),
			rounds: array( 'semi-final', 'final' ),
			champion: 42,
			runner_up: 84,
			parent_league: $league,
			draw: 'main-draw',
			status: 'active',
			primary_league: 10
		);

		$event = Event_Factory::create_from_dto( $dto );
		$this->assertSame( array( array( 'id' => 101 ) ), $event->matches );
		$this->assertSame( array( 'group', 'knockout' ), $event->stages );
		$this->assertSame( array( 'semi-final', 'final' ), $event->rounds );
		$this->assertSame( 42, $event->champion );
		$this->assertSame( 84, $event->runner_up );
		$this->assertSame( $league, $event->parent_league );
		$this->assertSame( 'main-draw', $event->draw );
		$this->assertSame( 'active', $event->status );
		$this->assertSame( 10, $event->primary_league );

		// Test from_object with extended properties.
		$row = (object) array(
			'id'             => 14,
			'name'           => 'Object Event',
			'type'           => 'singles',
			'competition_id' => 1,
			'matches'        => array( array( 'id' => 202 ) ),
			'stages'         => array( 'knockout' ),
			'rounds'         => array( 'final' ),
			'champion'       => '55',
			'runner_up'      => '66',
			'parent_league'  => $league,
			'draw'           => 'draw-1',
			'status'         => 'completed',
			'primary_league' => '10',
		);
		$event_from_obj = Event_Factory::from_object( $row );
		$this->assertSame( array( array( 'id' => 202 ) ), $event_from_obj->matches );
		$this->assertSame( array( 'knockout' ), $event_from_obj->stages );
		$this->assertSame( array( 'final' ), $event_from_obj->rounds );
		$this->assertSame( 55, $event_from_obj->champion );
		$this->assertSame( 66, $event_from_obj->runner_up );
		$this->assertSame( $league, $event_from_obj->parent_league );
		$this->assertSame( 'draw-1', $event_from_obj->draw );
		$this->assertSame( 'completed', $event_from_obj->status );
		$this->assertSame( 10, $event_from_obj->primary_league );
	}

	public function test_event_test_factory_presets_and_overrides(): void {
		// Default create
		$default_event = \Racketmanager\Tests\Helpers\Event_Test_Factory::create( id: 100, name: 'Custom Default' );
		$this->assertInstanceOf( Event::class, $default_event );
		$this->assertSame( 100, $default_event->id );
		$this->assertSame( 'Custom Default', $default_event->name );
		$this->assertSame( 'MD', $default_event->type );

		// League preset
		$league_event = \Racketmanager\Tests\Helpers\Event_Test_Factory::league_event( id: 101, name: 'Div 1' );
		$this->assertInstanceOf( Event::class, $league_event );
		$this->assertSame( 101, $league_event->id );
		$this->assertSame( 'Div 1', $league_event->name );
		$this->assertSame( 'MD', $league_event->type );

		// Tournament preset
		$tourn_event = \Racketmanager\Tests\Helpers\Event_Test_Factory::tournament_event(
			id: 102,
			name: 'Open Singles',
			champion: 1,
			runner_up: 2,
			overrides: array( 'draw' => 'custom-draw' )
		);
		$this->assertInstanceOf( Event::class, $tourn_event );
		$this->assertSame( 102, $tourn_event->id );
		$this->assertSame( 'Open Singles', $tourn_event->name );
		$this->assertSame( 'MS', $tourn_event->type );
		$this->assertSame( 'M', substr( $tourn_event->type, 0, 1 ) );
		$this->assertSame( 'S', substr( $tourn_event->type, 1, 1 ) );
		$this->assertSame( 1, $tourn_event->champion );
		$this->assertSame( 2, $tourn_event->runner_up );
		$this->assertSame( 'custom-draw', $tourn_event->draw );

		// Cup preset
		$cup_event = \Racketmanager\Tests\Helpers\Event_Test_Factory::cup_event( id: 103, name: 'Cup Knockout' );
		$this->assertInstanceOf( Event::class, $cup_event );
		$this->assertSame( 103, $cup_event->id );
		$this->assertSame( 'MD', $cup_event->type );

		// from_object / from_array
		$row_data = (object) array(
			'id'             => 104,
			'name'           => 'From Object',
			'type'           => 'WD',
			'competition_id' => 1,
		);
		$from_obj = \Racketmanager\Tests\Helpers\Event_Test_Factory::from_object( $row_data );
		$this->assertSame( 104, $from_obj->id );
		$this->assertSame( 'WD', $from_obj->type );

		$arr_data = array(
			'id'             => 105,
			'name'           => 'From Array',
			'type'           => 'XD',
			'competition_id' => 1,
		);
		$from_arr = \Racketmanager\Tests\Helpers\Event_Test_Factory::from_array( $arr_data );
		$this->assertSame( 105, $from_arr->id );
		$this->assertSame( 'XD', $from_arr->type );
	}

	public function test_event_test_factory_rejects_unknown_override(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Unknown Event test property: nmae' );

		\Racketmanager\Tests\Helpers\Event_Test_Factory::create(
			overrides: array( 'nmae' => 'Typo' )
		);
	}

	public function test_dto_from_object_handles_plain_db_row(): void {
		$row = (object) array(
			'id'             => 200,
			'competition_id' => 1,
			'name'           => 'DB Event',
			'type'           => 'MD',
			'num_sets'       => 3,
			'num_rubbers'    => 4,
			'settings'       => array(),
			'seasons'        => array(),
		);

		$dto = Event_Hydration_DTO::from_object( $row );
		$this->assertSame( array(), $dto->matches );
		$this->assertSame( array(), $dto->stages );
		$this->assertSame( array(), $dto->rounds );
		$this->assertNull( $dto->champion );
		$this->assertNull( $dto->runner_up );
		$this->assertNull( $dto->parent_league );
		$this->assertNull( $dto->draw );
		$this->assertSame( '', $dto->status );
		$this->assertNull( $dto->primary_league );

		$event = Event_Factory::create_from_dto( $dto );
		$this->assertSame( 0, $event->primary_league );
	}

	public function test_dto_from_object_handles_empty_strings_and_malformed_inputs(): void {
		$row = (object) array(
			'id'             => 201,
			'competition_id' => 1,
			'name'           => 'Edge Event',
			'type'           => 'MD',
			'champion'       => '',
			'runner_up'      => '',
			'primary_league' => '',
			'matches'        => 'not an array',
			'stages'         => 12345,
			'rounds'         => new \stdClass(),
			'parent_league'  => new \stdClass(),
			'draw'           => array( 'invalid' ),
			'status'         => array( 'invalid' ),
		);

		$dto = Event_Hydration_DTO::from_object( $row );
		$this->assertNull( $dto->champion );
		$this->assertNull( $dto->runner_up );
		$this->assertNull( $dto->primary_league );
		$this->assertSame( array(), $dto->matches );
		$this->assertSame( array(), $dto->stages );
		$this->assertSame( array(), $dto->rounds );
		$this->assertNull( $dto->parent_league );
		$this->assertNull( $dto->draw );
		$this->assertSame( '', $dto->status );

		$event = Event_Factory::create_from_dto( $dto );
		$this->assertSame( 0, $event->primary_league );
	}

	public function test_settings_primary_league_overrides_dto_column(): void {
		$dto = new Event_Hydration_DTO(
			id: 202,
			name: 'Override Event',
			type: 'MD',
			competition_id: 1,
			settings: array( 'primary_league' => 20 ),
			primary_league: 10
		);

		$event = Event_Factory::create_from_dto( $dto );
		$this->assertSame( 20, $event->primary_league );
	}

	public function test_league_test_factory_default_event_behavior(): void {
		$event = \Racketmanager\Tests\Helpers\League_Test_Factory::default_event(
			event_id: 7,
			seasons: array( '2026' => array( 'name' => '2026' ) )
		);

		$this->assertInstanceOf( Event::class, $event );
		$this->assertSame( 7, $event->id );
		$this->assertSame( 'MD', $event->type );
		$this->assertNotNull( $event->competition );
		$this->assertNotNull( $event->get_season_by_name( '2026' ) );
	}

	public function test_malformed_serialized_settings_and_seasons_do_not_emit_warnings_and_use_fallbacks(): void {
		$captured_warnings = array();
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Error handler capturing warnings for assertion.
		set_error_handler(
			static function ( int $errno, string $errstr ) use ( &$captured_warnings ): bool {
				$captured_warnings[] = $errstr;
				return true;
			}
		);

		try {
			$dto = Event_Hydration_DTO::from_object(
				(object) array(
					'name'           => 'Corrupted Row',
					'type'           => 'MS',
					'competition_id' => 1,
					'settings'       => 'a:1:{s:3:"bad";}',
					'seasons'        => 'a:2:{s:1:"x";}',
				)
			);

			$event = Event::from_array(
				array(
					'name'           => 'Corrupted Row',
					'type'           => 'MS',
					'competition_id' => 1,
					'settings'       => 'a:1:{s:3:"bad";}',
					'seasons'        => 'a:2:{s:1:"x";}',
				)
			);
		} finally {
			restore_error_handler();
		}

		$this->assertSame( array(), $captured_warnings, 'Malformed serialized data must not emit PHP warnings.' );
		$this->assertSame( array(), $dto->settings, 'Malformed serialized settings must fallback to empty array in DTO.' );
		$this->assertSame( 'a:2:{s:1:"x";}', $dto->seasons, 'Malformed serialized seasons must be left unchanged in DTO.' );
		$this->assertSame( array(), $event->settings, 'Malformed serialized settings must fallback to empty array on Event.' );
		$this->assertSame( 'a:2:{s:1:"x";}', $event->seasons, 'Malformed serialized seasons must be left unchanged on Event.' );
	}

	public function test_event_test_factory_dto_override(): void {
		$event = \Racketmanager\Tests\Helpers\Event_Test_Factory::create(
			overrides: array(
				'status'   => 'active',
				'draw'     => 'custom-draw',
				'champion' => 42,
			)
		);

		$this->assertSame( 'active', $event->status );
		$this->assertSame( 'custom-draw', $event->draw );
		$this->assertSame( 42, $event->champion );
	}

	public function test_event_test_factory_real_setter_override(): void {
		$event = \Racketmanager\Tests\Helpers\Event_Test_Factory::create(
			overrides: array(
				'is_championship' => true,
				'num_leagues'     => 5,
			)
		);

		$this->assertTrue( $event->is_championship );
		$this->assertSame( 5, $event->num_leagues );
	}

	public function test_event_test_factory_rejects_setter_less_override(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Event property "num_ascend" has no setter method.' );

		\Racketmanager\Tests\Helpers\Event_Test_Factory::create(
			overrides: array( 'num_ascend' => 2 )
		);
	}

	public function test_event_test_factory_rejects_non_public_meta_override(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Cannot override non-public Event property: meta' );

		\Racketmanager\Tests\Helpers\Event_Test_Factory::create(
			overrides: array( 'meta' => array( 'key' => 'value' ) )
		);
	}
}
