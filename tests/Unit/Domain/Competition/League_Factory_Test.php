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
		$event                     = new \Racketmanager\Domain\Competition\Event( $event_raw );

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
		$event                     = new \Racketmanager\Domain\Competition\Event( $event_raw );

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
		$event                     = new \Racketmanager\Domain\Competition\Event( $event_raw );

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
		$event                     = new \Racketmanager\Domain\Competition\Event( $event_raw );

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
		$event                     = new \Racketmanager\Domain\Competition\Event( $event_raw );

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
		$event                     = new \Racketmanager\Domain\Competition\Event( $event_raw );

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
		$event                     = new \Racketmanager\Domain\Competition\Event( $event_raw );

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
}
