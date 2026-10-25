<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Fixture;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Fixture\Fixture_Hydration_DTO;
use InvalidArgumentException;

final class Fixture_Hydration_DTO_Test extends TestCase {
	public function test_from_object_casts_db_shaped_row(): void {
		$row = (object) array(
			'id'                  => '12',
			'title'               => 'Cup Final',
			'link'                => 0,
			'group'               => 1,
			'date'                => '2026-05-01 18:00:00',
			'date_original'       => 20260501,
			'home_team'           => 10,
			'away_team'           => 20,
			'match_day'           => '3',
			'location'            => 7,
			'host'                => 4,
			'league_id'           => '99',
			'season'              => 2026,
			'home_points'         => 3,
			'away_points'         => 1,
			'winner_id'           => '0',
			'loser_id'            => null,
			'status'              => '2',
			'linked_match'        => '55',
			'leg'                 => '1',
			'final'               => 1,
			'custom'              => serialize( array( 'note' => 'db' ) ),
			'comments'            => serialize( array( 'admin' => 'ok' ) ),
			'updated'             => 20260501,
			'date_result_entered' => 20260502,
			'confirmed'           => 'Y',
			'updated_by'          => 8,
			'start_time'          => '18:00',
		);

		$dto = Fixture_Hydration_DTO::from_object( $row );

		$this->assertSame( 12, $dto->id );
		$this->assertSame( 'Cup Final', $dto->fixture_title );
		$this->assertSame( '0', $dto->link );
		$this->assertSame( '1', $dto->group );
		$this->assertSame( '2026-05-01 18:00:00', $dto->date );
		$this->assertSame( '20260501', $dto->date_original );
		$this->assertSame( '10', $dto->home_team );
		$this->assertSame( '20', $dto->away_team );
		$this->assertSame( 3, $dto->match_day );
		$this->assertSame( '7', $dto->location );
		$this->assertSame( '4', $dto->host );
		$this->assertSame( 99, $dto->league_id );
		$this->assertSame( '2026', $dto->season );
		$this->assertSame( '3', $dto->home_points );
		$this->assertSame( '1', $dto->away_points );
		$this->assertSame( 0, $dto->winner_id );
		$this->assertNull( $dto->loser_id );
		$this->assertSame( 2, $dto->status );
		$this->assertSame( 55, $dto->linked_fixture );
		$this->assertSame( 1, $dto->leg );
		$this->assertSame( '1', $dto->final );
		$this->assertSame( array( 'note' => 'db' ), $dto->custom );
		$this->assertSame( array( 'admin' => 'ok' ), $dto->comments );
		$this->assertSame( '20260501', $dto->updated );
		$this->assertSame( '20260502', $dto->date_result_entered );
		$this->assertSame( 'Y', $dto->confirmed );
		$this->assertSame( '8', $dto->updated_by );
		$this->assertSame( '18:00', $dto->start_time );
	}

	public function test_from_object_uses_linked_fixture_when_linked_match_absent(): void {
		$dto = Fixture_Hydration_DTO::from_object(
			(object) array(
				'id'             => '1',
				'linked_fixture' => '77',
			)
		);

		$this->assertSame( 77, $dto->linked_fixture );
	}

	public function test_from_object_wraps_plain_string_comments(): void {
		$dto = Fixture_Hydration_DTO::from_object(
			(object) array(
				'id'       => '1',
				'comments' => 'plain comment',
			)
		);

		$this->assertSame( array( 'legacy' => 'plain comment' ), $dto->comments );
	}

	public function test_from_array_null_custom_stays_null(): void {
		$dto = Fixture_Hydration_DTO::from_array(
			array(
				'id'     => 4,
				'custom' => null,
			)
		);

		$this->assertSame( 4, $dto->id );
		$this->assertNull( $dto->custom );
	}

	public function test_from_object_treats_empty_custom_as_an_empty_array(): void {
		$dto = Fixture_Hydration_DTO::from_object( (object) array( 'custom' => '' ) );

		$this->assertSame( array(), $dto->custom );
		$this->assertSame( array(), Fixture_Hydration_DTO::from_object( (object) array( 'custom' => " \t\n" ) )->custom );
	}

	public function test_from_object_preserves_legacy_plain_custom_string(): void {
		$dto = Fixture_Hydration_DTO::from_object( (object) array( 'custom' => 'legacy custom data' ) );

		$this->assertSame( array( 0 => 'legacy custom data' ), $dto->custom );
	}

	public function test_from_object_rejects_corrupt_serialized_custom_payload(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Unable to unserialize Fixture hydration field "custom".' );

		Fixture_Hydration_DTO::from_object( (object) array( 'custom' => 'a:1:{s:3:"foo";s:3:"bar";' ) );
	}

	public function test_from_object_unserializes_valid_serialized_comments_array(): void {
		$dto = Fixture_Hydration_DTO::from_object(
			(object) array(
				'comments' => serialize(
					array(
						'home'   => 'Home note',
						'away'   => 'Away note',
						'result' => '',
					)
				),
			)
		);

		$this->assertSame(
			array(
				'home'   => 'Home note',
				'away'   => 'Away note',
				'result' => '',
			),
			$dto->comments
		);
	}

	public function test_from_object_falls_back_to_legacy_for_corrupt_serialized_comments(): void {
		// Byte-length mismatch matching live rows 159290/159296 (emoji replaced by `?`).
		$corrupt = 'a:3:{s:4:"away";s:0:"";s:4:"home";s:24:"Thanks?";s:6:"result";s:0:"";}';

		$dto = Fixture_Hydration_DTO::from_object( (object) array( 'comments' => $corrupt ) );

		$this->assertSame( array( 'legacy' => $corrupt ), $dto->comments );
	}

	public function test_from_object_does_not_instantiate_serialized_comment_objects(): void {
		$payload = 'O:8:"stdClass":1:{s:4:"hack";s:3:"yes";}';

		$dto = Fixture_Hydration_DTO::from_object( (object) array( 'comments' => $payload ) );

		$this->assertIsArray( $dto->comments );
		$this->assertNotInstanceOf( \stdClass::class, $dto->comments );
		foreach ( $dto->comments as $value ) {
			$this->assertNotInstanceOf( \stdClass::class, $value );
		}
	}

	public function test_from_object_extracts_start_time_from_standard_datetime_with_seconds(): void {
		$dto = Fixture_Hydration_DTO::from_object(
			(object) array(
				'id'   => 1,
				'date' => '2026-05-15 19:30:00',
			)
		);

		$this->assertSame( '2026-05-15 19:30:00', $dto->date );
		$this->assertSame( '19:30', $dto->start_time );
	}

	public function test_from_object_extracts_start_time_from_datetime_without_seconds(): void {
		$dto = Fixture_Hydration_DTO::from_object(
			(object) array(
				'id'   => 1,
				'date' => '2026-05-15 19:30',
			)
		);

		$this->assertSame( '2026-05-15 19:30', $dto->date );
		$this->assertSame( '19:30', $dto->start_time );
	}

	public function test_from_object_extracts_start_time_from_iso_datetime(): void {
		$dto = Fixture_Hydration_DTO::from_object(
			(object) array(
				'id'   => 1,
				'date' => '2026-05-15T19:30:00',
			)
		);

		$this->assertSame( '19:30', $dto->start_time );
	}

	public function test_from_object_returns_null_start_time_for_midnight(): void {
		$dto_with_seconds = Fixture_Hydration_DTO::from_object(
			(object) array(
				'id'   => 1,
				'date' => '2026-05-15 00:00:00',
			)
		);
		$this->assertNull( $dto_with_seconds->start_time );

		$dto_without_seconds = Fixture_Hydration_DTO::from_object(
			(object) array(
				'id'   => 2,
				'date' => '2026-05-15 00:00',
			)
		);
		$this->assertNull( $dto_without_seconds->start_time );
	}

	public function test_from_object_returns_null_start_time_for_date_only(): void {
		$dto = Fixture_Hydration_DTO::from_object(
			(object) array(
				'id'   => 1,
				'date' => '2026-05-15',
			)
		);

		$this->assertNull( $dto->start_time );
	}

	public function test_from_object_returns_null_start_time_for_zero_date(): void {
		$dto = Fixture_Hydration_DTO::from_object(
			(object) array(
				'id'   => 1,
				'date' => '0000-00-00 00:00:00',
			)
		);
		$this->assertNull( $dto->start_time );

		$dto_short = Fixture_Hydration_DTO::from_object(
			(object) array(
				'id'   => 2,
				'date' => '0000-00-00',
			)
		);
		$this->assertNull( $dto_short->start_time );
	}

	public function test_from_object_returns_null_start_time_when_date_is_null_or_empty_even_if_date_original_has_time(): void {
		$dto_null_date = Fixture_Hydration_DTO::from_object(
			(object) array(
				'id'            => 1,
				'date'          => null,
				'date_original' => '2026-05-15 20:15:00',
			)
		);
		$this->assertNull( $dto_null_date->start_time );

		$dto_empty_date = Fixture_Hydration_DTO::from_object(
			(object) array(
				'id'            => 2,
				'date'          => '',
				'date_original' => '2026-05-15 20:15:00',
			)
		);
		$this->assertNull( $dto_empty_date->start_time );

		$dto_zero_date = Fixture_Hydration_DTO::from_object(
			(object) array(
				'id'            => 3,
				'date'          => '0000-00-00 00:00:00',
				'date_original' => '2026-05-15 20:15:00',
			)
		);
		$this->assertNull( $dto_zero_date->start_time );

		$dto_date_only = Fixture_Hydration_DTO::from_object(
			(object) array(
				'id'            => 4,
				'date'          => '2026-05-15',
				'date_original' => '2026-05-15 20:15:00',
			)
		);
		$this->assertNull( $dto_date_only->start_time );
	}

	public function test_from_object_honors_explicit_start_time_override(): void {
		$dto = Fixture_Hydration_DTO::from_object(
			(object) array(
				'id'         => 1,
				'date'       => '2026-05-15 19:30:00',
				'start_time' => '18:00',
			)
		);

		$this->assertSame( '18:00', $dto->start_time );
	}

	public function test_from_object_normalizes_explicit_start_time_with_seconds_to_hi(): void {
		$dto = Fixture_Hydration_DTO::from_object(
			(object) array(
				'id'         => 1,
				'date'       => '2026-05-15 19:30:00',
				'start_time' => '18:00:00',
			)
		);

		$this->assertSame( '18:00', $dto->start_time );
	}

	public function test_from_object_normalizes_explicit_start_time_with_whitespace(): void {
		$dto = Fixture_Hydration_DTO::from_object(
			(object) array(
				'id'         => 1,
				'date'       => '2026-05-15 19:30:00',
				'start_time' => '  18:00  ',
			)
		);

		$this->assertSame( '18:00', $dto->start_time );
	}

	public function test_from_object_explicit_null_start_time_overrides_date_derived_time(): void {
		$dto = Fixture_Hydration_DTO::from_object(
			(object) array(
				'id'         => 1,
				'date'       => '2026-05-15 19:30:00',
				'start_time' => null,
			)
		);

		$this->assertNull( $dto->start_time );
	}

	public function test_from_object_converts_explicit_midnight_start_time_to_null(): void {
		$dto_midnight_seconds = Fixture_Hydration_DTO::from_object(
			(object) array(
				'id'         => 1,
				'date'       => '2026-05-15 19:30:00',
				'start_time' => '00:00:00',
			)
		);
		$this->assertNull( $dto_midnight_seconds->start_time );

		$dto_midnight_short = Fixture_Hydration_DTO::from_object(
			(object) array(
				'id'         => 2,
				'date'       => '2026-05-15 19:30:00',
				'start_time' => '00:00',
			)
		);
		$this->assertNull( $dto_midnight_short->start_time );
	}

	public function test_from_object_handles_explicit_empty_start_time(): void {
		$dto = Fixture_Hydration_DTO::from_object(
			(object) array(
				'id'         => 1,
				'date'       => '2026-05-15 19:30:00',
				'start_time' => '',
			)
		);

		$this->assertNull( $dto->start_time );
	}

	public function test_from_array_extracts_start_time_from_datetime(): void {
		$dto = Fixture_Hydration_DTO::from_array(
			array(
				'id'   => 1,
				'date' => '2026-05-15 14:15:00',
			)
		);

		$this->assertSame( '14:15', $dto->start_time );
	}
}
