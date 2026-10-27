<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Factory;
use Racketmanager\Domain\DTO\Competition\League_Hydration_DTO;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class League_Factory_Test extends TestCase {
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$GLOBALS['wpdb'] = new \wpdb();
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wp_stubs_events'] );
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_missing_event_returns_null_without_constructing_league(): void {
		$dto = new League_Hydration_DTO(
			id: 1,
			title: 'Orphan Division',
			event_id: 99
		);

		$this->assertNull( League_Factory::create_from_dto( $dto, null ) );
		$this->assertNull( League_Factory::from_object( (object) array(
			'id'       => 1,
			'title'    => 'Orphan Division',
			'event_id' => 99,
		), null ) );
	}

	public function test_missing_event_from_get_event_returns_null(): void {
		$dto = new League_Hydration_DTO(
			id: 1,
			title: 'Orphan Division',
			event_id: 99
		);

		$this->assertNull( League_Factory::create_from_dto( $dto, null ) );
	}

	public function test_empty_event_id_returns_null(): void {
		$dto = new League_Hydration_DTO(
			id: 1,
			title: 'No Event'
		);
		$this->assertNull( League_Factory::create_from_dto( $dto, null ) );
	}

	public function test_known_event_hydrates_league_or_sport_subclass(): void {
		$event_raw                 = new \stdClass();
		$event_raw->id             = 10;
		$event_raw->name           = 'Main Event';
		$event_raw->type           = 'singles';
		$event_raw->competition_id = 1;
		$event_raw->seasons        = '[]';
		$event                     = \Racketmanager\Domain\Competition\Event::from_object( $event_raw );

		$dto = new League_Hydration_DTO(
			id: 4,
			title: 'Division 1',
			event_id: 10
		);
		$league = League_Factory::create_from_dto( $dto, $event );

		$this->assertInstanceOf( League::class, $league );
		$this->assertSame( 4, $league->id );
		$this->assertSame( 'Division 1', $league->title );

		$sport_class = 'Racketmanager\\sports\\League_Tennis';
		if ( class_exists( $sport_class ) ) {
			$this->assertInstanceOf( $sport_class, $league );
		}
	}

	public function test_from_array_with_empty_title_throws_invalid_argument_exception(): void {
		$event_raw                 = new \stdClass();
		$event_raw->id             = 3;
		$event_raw->name           = 'Event';
		$event_raw->type           = 'singles';
		$event_raw->competition_id = 1;
		$event                     = \Racketmanager\Domain\Competition\Event::from_object( $event_raw );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'League title is required.' );
		League_Factory::from_array( array(), $event );
	}

	public function test_from_array_with_whitespace_title_throws_invalid_argument_exception(): void {
		$event_raw                 = new \stdClass();
		$event_raw->id             = 3;
		$event_raw->name           = 'Event';
		$event_raw->type           = 'singles';
		$event_raw->competition_id = 1;
		$event                     = \Racketmanager\Domain\Competition\Event::from_object( $event_raw );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'League title is required.' );
		League_Factory::from_array( array( 'title' => '   ' ), $event );
	}

	public function test_from_array_with_mismatched_event_id_throws_invalid_argument_exception(): void {
		$event_raw                 = new \stdClass();
		$event_raw->id             = 3;
		$event_raw->name           = 'Event';
		$event_raw->type           = 'singles';
		$event_raw->competition_id = 1;
		$event                     = \Racketmanager\Domain\Competition\Event::from_object( $event_raw );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'League event_id 99 does not match Event id 3.' );
		League_Factory::from_array( array( 'event_id' => 99, 'title' => 'Division 1' ), $event );
	}

	public function test_from_array_with_negative_id_throws_invalid_argument_exception(): void {
		$event_raw                 = new \stdClass();
		$event_raw->id             = 3;
		$event_raw->name           = 'Event';
		$event_raw->type           = 'singles';
		$event_raw->competition_id = 1;
		$event                     = \Racketmanager\Domain\Competition\Event::from_object( $event_raw );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'League id must be non-negative.' );
		League_Factory::from_array( array( 'id' => -1, 'title' => 'Division 1' ), $event );
	}

	public function test_from_array_populates_event_id_from_event_when_omitted(): void {
		$event_raw                 = new \stdClass();
		$event_raw->id             = 5;
		$event_raw->name           = 'Event';
		$event_raw->type           = 'singles';
		$event_raw->competition_id = 1;
		$event                     = \Racketmanager\Domain\Competition\Event::from_object( $event_raw );

		$league = League_Factory::from_array( array( 'id' => 12, 'title' => 'Division 2' ), $event );
		$this->assertInstanceOf( League::class, $league );
		$this->assertSame( 12, $league->id );
		$this->assertSame( 'Division 2', $league->title );
		$this->assertSame( 5, $league->event_id );
	}

	public function test_from_object_hydrates_league(): void {
		$event_raw                 = new \stdClass();
		$event_raw->id             = 5;
		$event_raw->name           = 'Event';
		$event_raw->type           = 'singles';
		$event_raw->competition_id = 1;
		$event                     = \Racketmanager\Domain\Competition\Event::from_object( $event_raw );

		$row           = new \stdClass();
		$row->id       = 8;
		$row->title    = 'Championship Div';
		$row->event_id = 5;

		$league = League_Factory::from_object( $row, $event );
		$this->assertInstanceOf( League::class, $league );
		$this->assertSame( 8, $league->id );
		$this->assertSame( 'Championship Div', $league->title );
		$this->assertSame( 5, $league->event_id );
	}

	public function test_constructor_accepts_only_the_hydration_dto(): void {
		$this->expectException( \TypeError::class );
		new League( (object) array( 'id' => 1, 'title' => 'Raw' ) );
	}

	public function test_constructor_from_dto_merges_settings_over_columns_and_derives_event_id(): void {
		$event_raw                 = new \stdClass();
		$event_raw->id             = 21;
		$event_raw->name           = 'Event';
		$event_raw->type           = 'singles';
		$event_raw->competition_id = 1;
		$event                     = \Racketmanager\Domain\Competition\Event::from_object( $event_raw );

		$dto = new League_Hydration_DTO(
			id: 6,
			title: 'Division 2',
			sequence: '2',
			settings: array( 'title' => 'Settings Title', 'num_teams_per_page' => '12', 'standings' => array( 'pld' => 1 ) )
		);

		$league = new League( $dto, $event );

		$this->assertSame( 'Settings Title', $league->title, 'settings keys take precedence over table columns' );
		$this->assertSame( 'Division 2', $league->name, 'name mirrors the title column' );
		$this->assertSame( '2', $league->sequence );
		$this->assertSame( 12, $league->num_teams_per_page );
		$this->assertSame( 21, $league->event_id, 'event_id is derived from the Event when the DTO value is null' );
		$this->assertSame( $event, $league->event );
		$this->assertFalse( method_exists( $dto, 'to_std_class' ) );
	}

	public function test_lenient_from_object_without_event_returns_league_while_factory_returns_null(): void {
		$row = (object) array( 'id' => 10, 'title' => 'Orphan', 'unknown_column' => 'dropped' );

		$this->assertNull( League_Factory::from_object( $row, null ) );

		$league = League::from_object( $row );
		$this->assertInstanceOf( League::class, $league );
		$this->assertSame( 10, $league->id );
		$this->assertSame( 'Orphan', $league->title );
		$this->assertNull( $league->event );
		$this->assertFalse( property_exists( $league, 'unknown_column' ) );
	}

	public function test_lenient_from_object_matches_factory_hydration_for_valid_rows(): void {
		$event_raw                 = new \stdClass();
		$event_raw->id             = 5;
		$event_raw->name           = 'Event';
		$event_raw->type           = 'singles';
		$event_raw->competition_id = 1;
		$event                     = \Racketmanager\Domain\Competition\Event::from_object( $event_raw );

		$row = (object) array(
			'id'       => '8',
			'title'    => 'Division 1',
			'event_id' => '5',
			'settings' => serialize( array( 'num_matches_per_page' => '20' ) ),
			'seasons'  => serialize( array( '2026' => array( 'name' => '2026' ) ) ),
		);

		$this->assertEquals( League_Factory::from_object( $row, $event ), League::from_object( $row, $event ) );
	}

	public function test_empty_constructor_yields_empty_league(): void {
		$league = new League();
		$this->assertNull( $league->id );
		$this->assertSame( '', $league->title );
		$this->assertNull( $league->event );
	}
}
