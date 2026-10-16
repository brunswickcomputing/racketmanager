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

		$this->assertNull( League_Factory::create_from_dto( $dto, false ) );
		$this->assertNull( League_Factory::from_object( (object) array(
			'id'       => 1,
			'title'    => 'Orphan Division',
			'event_id' => 99,
		), false ) );
	}

	public function test_missing_event_from_get_event_returns_null(): void {
		$GLOBALS['wp_stubs_events'][99] = null;
		$dto = new League_Hydration_DTO(
			id: 1,
			title: 'Orphan Division',
			event_id: 99
		);

		$this->assertNull( League_Factory::create_from_dto( $dto ) );
	}

	public function test_empty_event_id_returns_null(): void {
		$dto = new League_Hydration_DTO(
			id: 1,
			title: 'No Event'
		);
		$this->assertNull( League_Factory::create_from_dto( $dto ) );
	}

	public function test_known_event_hydrates_league_or_sport_subclass(): void {
		$dto = new League_Hydration_DTO(
			id: 4,
			title: 'Division 1',
			event_id: 10
		);
		$league = League_Factory::create_from_dto( $dto );

		$this->assertInstanceOf( League::class, $league );
		$this->assertSame( 4, $league->id );
		$this->assertSame( 'Division 1', $league->title );

		$sport_class = 'Racketmanager\\sports\\League_Tennis';
		if ( class_exists( $sport_class ) ) {
			$this->assertInstanceOf( $sport_class, $league );
		}
	}
}
