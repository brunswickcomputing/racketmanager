<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Fixture;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Fixture\Fixture_Hydration_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;

class Fixture_Factory_Test extends TestCase {
	public function test_create_from_dto(): void {
		$dto = new Fixture_Hydration_DTO(
			id: 123,
			fixture_title: 'Team A vs Team B',
			home_team: '1',
			away_team: '2',
			match_day: 1,
			league_id: 10,
			season: '2026',
			status: 1,
			winner_id: 1
		);

		$fixture = Fixture_Factory::create_from_dto( $dto );

		$this->assertInstanceOf( Fixture::class, $fixture );
		$this->assertSame( 123, $fixture->get_id() );
		$this->assertSame( '1', $fixture->get_home_team() );
		$this->assertSame( '2', $fixture->get_away_team() );
		$this->assertSame( 1, $fixture->get_match_day() );
		$this->assertSame( 10, $fixture->get_league_id() );
		$this->assertSame( '2026', $fixture->get_season() );
		$this->assertTrue( $fixture->is_walkover );
		$this->assertFalse( $fixture->is_pending );
	}

	public function test_from_object_with_serialized_data(): void {
		$row = (object) [
			'id'           => '456',
			'title'        => 'Cup Final',
			'home_team'    => '3',
			'away_team'    => '4',
			'status'       => '2',
			'winner_id'    => '0',
			'custom'       => serialize( [ 'walkover' => '1', 'sets' => [ '6-4', '6-2' ] ] ),
			'comments'     => serialize( [ 'note' => 'Great match' ] ),
			'linked_match' => '789',
		];

		$fixture = Fixture_Factory::from_object( $row );

		$this->assertInstanceOf( Fixture::class, $fixture );
		$this->assertSame( 456, $fixture->get_id() );
		$this->assertSame( '3', $fixture->get_home_team() );
		$this->assertSame( '4', $fixture->get_away_team() );
		$this->assertSame( 789, $fixture->get_linked_fixture() );
		$this->assertTrue( $fixture->is_retired );
		$this->assertTrue( $fixture->is_pending );
		$this->assertSame( [ 'note' => 'Great match' ], $fixture->get_comments() );
		$this->assertSame( [ 'walkover' => '1', 'sets' => [ '6-4', '6-2' ] ], $fixture->get_custom() );
	}

	public function test_from_object_extracts_start_time_from_datetime(): void {
		$row = (object) [
			'id'        => '789',
			'home_team' => '1',
			'away_team' => '2',
			'date'      => '2026-05-15 19:30:00',
		];

		$fixture = Fixture_Factory::from_object( $row );

		$this->assertSame( '2026-05-15 19:30:00', $fixture->get_date() );
		$this->assertSame( '19:30', $fixture->get_start_time() );
	}

	public function test_from_object_leaves_start_time_null_for_date_only_or_midnight(): void {
		$row_date_only = (object) [
			'id'        => '790',
			'home_team' => '1',
			'away_team' => '2',
			'date'      => '2026-05-15',
		];

		$fixture_date_only = Fixture_Factory::from_object( $row_date_only );
		$this->assertNull( $fixture_date_only->get_start_time() );

		$row_midnight = (object) [
			'id'        => '791',
			'home_team' => '1',
			'away_team' => '2',
			'date'      => '2026-05-15 00:00:00',
		];

		$fixture_midnight = Fixture_Factory::from_object( $row_midnight );
		$this->assertNull( $fixture_midnight->get_start_time() );
	}

	public function test_from_array_extracts_start_time_from_datetime(): void {
		$data = [
			'id'        => 792,
			'home_team' => '5',
			'away_team' => '6',
			'date'      => '2026-06-01 18:45:00',
		];

		$fixture = Fixture_Factory::from_array( $data );

		$this->assertSame( '2026-06-01 18:45:00', $fixture->get_date() );
		$this->assertSame( '18:45', $fixture->get_start_time() );
	}

	public function test_from_object_honors_explicit_start_time_override(): void {
		$row = (object) [
			'id'         => '793',
			'home_team'  => '1',
			'away_team'  => '2',
			'date'       => '2026-05-15 19:30:00',
			'start_time' => '20:15',
		];

		$fixture = Fixture_Factory::from_object( $row );

		$this->assertSame( '2026-05-15 20:15:00', $fixture->get_date() );
		$this->assertSame( '20:15', $fixture->get_start_time() );

		$reloaded = Fixture_Factory::from_object( (object) [ 'id' => '793', 'date' => $fixture->get_date() ] );
		$this->assertSame( '20:15', $reloaded->get_start_time() );
	}

	public function test_from_object_honors_explicit_start_time_override_with_date_only(): void {
		$row = (object) [
			'id'         => '794',
			'home_team'  => '1',
			'away_team'  => '2',
			'date'       => '2026-05-15',
			'start_time' => '18:00',
		];

		$fixture = Fixture_Factory::from_object( $row );

		$this->assertSame( '2026-05-15 18:00:00', $fixture->get_date() );
		$this->assertSame( '18:00', $fixture->get_start_time() );

		$reloaded = Fixture_Factory::from_object( (object) [ 'id' => '794', 'date' => $fixture->get_date() ] );
		$this->assertSame( '18:00', $reloaded->get_start_time() );
	}

	public function test_from_object_honors_explicit_null_start_time_override(): void {
		$row = (object) [
			'id'         => '795',
			'home_team'  => '1',
			'away_team'  => '2',
			'date'       => '2026-05-15 19:30:00',
			'start_time' => null,
		];

		$fixture = Fixture_Factory::from_object( $row );

		$this->assertSame( '2026-05-15 00:00:00', $fixture->get_date() );
		$this->assertNull( $fixture->get_start_time() );

		$reloaded = Fixture_Factory::from_object( (object) [ 'id' => '795', 'date' => $fixture->get_date() ] );
		$this->assertNull( $reloaded->get_start_time() );
	}

	public function test_from_object_explicit_midnight_start_time_clears_datetime_to_midnight(): void {
		$fixture = Fixture_Factory::from_object(
			(object) [
				'id'         => '796',
				'date'       => '2026-05-15 19:30:00',
				'start_time' => '00:00',
			]
		);

		$this->assertSame( '2026-05-15 00:00:00', $fixture->get_date() );
		$this->assertNull( $fixture->get_start_time() );
	}

	public function test_from_object_equal_explicit_start_time_preserves_date_exactly(): void {
		$fixture = Fixture_Factory::from_object(
			(object) [
				'id'         => '797',
				'date'       => '2026-05-15 19:30:45',
				'start_time' => '19:30',
			]
		);

		$this->assertSame( '2026-05-15 19:30:45', $fixture->get_date() );
		$this->assertSame( '19:30', $fixture->get_start_time() );
	}

	public function test_direct_dto_with_start_time_reconciles_date_and_round_trips(): void {
		$fixture = new Fixture( new Fixture_Hydration_DTO( date: '2026-05-15 19:30:00', start_time: '20:00' ) );
		$this->assertSame( '2026-05-15 20:00:00', $fixture->get_date() );
		$this->assertSame( '20:00', $fixture->get_start_time() );

		$reloaded = new Fixture( new Fixture_Hydration_DTO( date: $fixture->get_date() ) );
		$this->assertSame( '20:00', $reloaded->get_start_time() );

		$date_only = new Fixture( new Fixture_Hydration_DTO( date: '2026-05-15', start_time: '14:00' ) );
		$this->assertSame( '2026-05-15 14:00:00', $date_only->get_date() );
	}

	public function test_direct_dto_default_null_start_time_preserves_timed_date(): void {
		$fixture = new Fixture( new Fixture_Hydration_DTO( date: '2026-05-15 19:30:45' ) );
		$this->assertSame( '2026-05-15 19:30:45', $fixture->get_date() );
		$this->assertSame( '19:30', $fixture->get_start_time() );
	}
}
