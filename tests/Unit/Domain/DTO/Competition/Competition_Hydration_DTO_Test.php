<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Competition;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\Event_Factory;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Competition\Event_Hydration_DTO;
use Racketmanager\Domain\DTO\Competition\League_Hydration_DTO;
use stdClass;

class Competition_Hydration_DTO_Test extends TestCase {
	public function test_league_dto_unserializes_php_settings_and_seasons(): void {
		$dto = League_Hydration_DTO::from_object( (object) array(
			'id'       => '7',
			'title'    => 'Division 1',
			'event_id' => '3',
			'sequence' => '1',
			'settings' => serialize( array( 'num_match_days' => 14 ) ),
			'seasons'  => serialize( array( '2026' => array( 'name' => '2026' ) ) ),
		) );

		$this->assertSame( 7, $dto->id );
		$this->assertSame( 'Division 1', $dto->title );
		$this->assertSame( 3, $dto->event_id );
		$this->assertSame( array( 'num_match_days' => 14 ), $dto->settings );
		$this->assertSame( array( '2026' => array( 'name' => '2026' ) ), $dto->seasons );
	}

	public function test_league_dto_decodes_json_settings_and_missing_fields(): void {
		$dto = League_Hydration_DTO::from_object( (object) array(
			'settings' => '{"point_rule":"three"}',
			'seasons'  => '{"2025":{"name":"2025"}}',
		) );

		$this->assertNull( $dto->id );
		$this->assertSame( '', $dto->title );
		$this->assertNull( $dto->event_id );
		$this->assertSame( array( 'point_rule' => 'three' ), $dto->settings );
		$this->assertSame( array( '2025' => array( 'name' => '2025' ) ), $dto->seasons );
	}

	public function test_league_constructed_from_dto_merges_settings_keys(): void {
		$dto = new League_Hydration_DTO(
			id: 1,
			title: 'Div 1',
			event_id: 2,
			settings: array( 'num_teams_per_page' => 14 )
		);
		$league = new League( $dto );
		$this->assertSame( 'Div 1', $league->title );
		$this->assertSame( 'Div 1', $league->name );
		$this->assertSame( 2, $league->event_id );
		$this->assertSame( 14, $league->num_teams_per_page );
		$this->assertFalse( method_exists( $dto, 'to_std_class' ) );
	}

	public function test_event_dto_unserializes_settings_and_keeps_json_seasons(): void {
		$dto = Event_Hydration_DTO::from_object( (object) array(
			'id'             => '11',
			'name'           => 'Open Doubles',
			'settings'       => serialize( array( 'num_sets' => 5 ) ),
			'seasons'        => serialize( array( array( 'name' => '2026' ) ) ),
			'competition_id' => '4',
		) );

		$this->assertSame( 11, $dto->id );
		$this->assertSame( 'Open Doubles', $dto->name );
		$this->assertSame( 4, $dto->competition_id );
		$this->assertSame( array( 'num_sets' => 5 ), $dto->settings );
		$this->assertSame( array( array( 'name' => '2026' ) ), $dto->seasons );
	}

	/**
	 * The DTO deliberately keeps `num_sets`/`num_rubbers` nullable; the legacy 3/0 defaults
	 * are applied by the validating factory, including through the entity wrappers.
	 */
	public function test_event_dto_missing_fields_are_nullable_and_factory_restores_legacy_defaults(): void {
		$dto = Event_Hydration_DTO::from_array( array() );
		$this->assertNull( $dto->id );
		$this->assertSame( '', $dto->name );
		$this->assertNull( $dto->num_sets );
		$this->assertNull( $dto->num_rubbers );
		$this->assertSame( array(), $dto->settings );

		$wrapped = Event::from_array( array( 'id' => 1, 'name' => 'Ev', 'type' => 'MS', 'competition_id' => 2 ) );
		$this->assertSame( Event_Factory::DEFAULT_NUM_SETS, $wrapped->num_sets );
		$this->assertSame( Event_Factory::DEFAULT_NUM_RUBBERS, $wrapped->num_rubbers );

		$validated = Event_Factory::from_array( array( 'id' => 1, 'name' => 'Ev', 'type' => 'MS', 'competition_id' => 2 ) );
		$this->assertSame( Event_Factory::DEFAULT_NUM_SETS, $validated->num_sets );
		$this->assertSame( Event_Factory::DEFAULT_NUM_RUBBERS, $validated->num_rubbers );
		$this->assertSame( 3, $validated->get_num_sets() );
	}

	public function test_event_constructed_from_dto_merges_settings_and_encodes_array_seasons(): void {
		$dto = new Event_Hydration_DTO(
			id: 2,
			name: 'Mixed',
			settings: array( 'point_rule' => 'three' ),
			seasons: array( array( 'name' => '2026' ) )
		);
		$event = new Event( $dto );
		$this->assertSame( 'Mixed', $event->name );
		$this->assertSame( 'three', $event->point_rule );
		$this->assertSame( array( 'point_rule' ), $event->settings_keys );
		$this->assertIsString( $event->seasons );
		$this->assertStringContainsString( '2026', $event->seasons );
		$this->assertFalse( method_exists( $dto, 'to_std_class' ) );
	}

	public function test_event_dto_accepts_json_settings(): void {
		$row = new stdClass();
		$row->settings = '{"foo":"bar"}';
		$dto = Event_Hydration_DTO::from_object( $row );
		$this->assertSame( array( 'foo' => 'bar' ), $dto->settings );
	}
}
