<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Tests\Helpers\League_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;
use wpdb;

class League_Test_Factory_Test extends TestCase {
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$GLOBALS['wpdb'] = new wpdb();
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wp_stubs_events'] );
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_from_object_does_not_mutate_caller_row_when_title_defaulted(): void {
		$row = new stdClass();
		$row->id       = 5;
		$row->event_id = 3;

		$league = League_Test_Factory::from_object( $row );

		self::assertInstanceOf( League::class, $league );
		self::assertSame( 'Test League', $league->title );
		self::assertFalse( isset( $row->title ) );
		self::assertSame( 5, $row->id );
		self::assertSame( 3, $row->event_id );
	}

	public function test_from_object_preserves_supplied_title(): void {
		$row = new stdClass();
		$row->id    = 6;
		$row->title = 'Division 2';

		$league = League_Test_Factory::from_object( $row );

		self::assertSame( 'Division 2', $league->title );
		self::assertSame( 'Division 2', $row->title );
	}

	public function test_from_object_does_not_add_title_when_name_supplied(): void {
		$row = new stdClass();
		$row->id   = 7;
		$row->name = 'Named Division';

		$league = League_Test_Factory::from_object( $row );

		self::assertFalse( isset( $row->title ) );
		self::assertInstanceOf( League::class, $league );
	}

	public function test_from_object_uses_attached_event_without_mutating_row(): void {
		$event = League_Test_Factory::default_event( 8 );
		$row   = new stdClass();
		$row->id       = 9;
		$row->event_id = 8;
		$row->event    = $event;

		$league = League_Test_Factory::from_object( $row );

		self::assertSame( $event, $league->event );
		self::assertSame( $event, $row->event );
		self::assertFalse( isset( $row->title ) );
	}

	public function test_from_object_uses_explicit_event_argument(): void {
		$event = League_Test_Factory::default_event( 11 );
		$row   = new stdClass();
		$row->id       = 10;
		$row->event_id = 11;

		$league = League_Test_Factory::from_object( $row, $event );

		self::assertSame( $event, $league->event );
		self::assertSame( 11, $league->event_id );
	}

	public function test_from_object_defaults_event_id_to_one_when_absent(): void {
		$row = new stdClass();
		$row->id = 12;

		$league = League_Test_Factory::from_object( $row );

		self::assertSame( 1, $league->event_id );
		self::assertInstanceOf( Event::class, $league->event );
	}

	public function test_from_object_copies_sport_from_row(): void {
		$row = new stdClass();
		$row->id       = 13;
		$row->event_id = 1;
		$row->sport    = 'tennis';

		$league = League_Test_Factory::from_object( $row );

		self::assertSame( 'tennis', $league->sport );
		self::assertFalse( isset( $row->title ) );
	}

	public function test_from_array_does_not_mutate_input_and_hydrates_league(): void {
		$data = array(
			'id'       => 14,
			'event_id' => 2,
		);

		$league = League_Test_Factory::from_array( $data );

		self::assertSame( 'Test League', $league->title );
		self::assertSame( array( 'id' => 14, 'event_id' => 2 ), $data );
	}

	public function test_from_array_matches_from_object_for_same_data(): void {
		$data = array(
			'id'       => 15,
			'title'    => 'Division 3',
			'event_id' => 4,
		);

		$from_array  = League_Test_Factory::from_array( $data );
		$from_object = League_Test_Factory::from_object( (object) $data );

		self::assertSame( $from_object->id, $from_array->id );
		self::assertSame( $from_object->title, $from_array->title );
		self::assertSame( $from_object->event_id, $from_array->event_id );
	}
}
