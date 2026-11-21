<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Player;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Player\Player_Error_Hydration_DTO;
use Racketmanager\Domain\Player\Player_Error;

final class Player_Error_Test extends TestCase {

	private function create_error(): Player_Error {
		return new Player_Error();
	}

	public function test_properties_are_readable_natively_and_via_getters_after_hydration(): void {
		$dto   = new Player_Error_Hydration_DTO(
			id: 10,
			player_id: 25,
			message: 'WTN lookup failed: user not found',
			created_date: '2026-11-21 10:00:00'
		);
		$error = new Player_Error( $dto );

		$this->assertSame( 10, $error->id );
		$this->assertSame( 10, $error->get_id() );

		$this->assertSame( 25, $error->player_id );
		$this->assertSame( 25, $error->get_player_id() );

		$this->assertSame( 'WTN lookup failed: user not found', $error->message );
		$this->assertSame( 'WTN lookup failed: user not found', $error->get_message() );

		$this->assertSame( '2026-11-21 10:00:00', $error->created_date );
		$this->assertSame( '2026-11-21 10:00:00', $error->get_created_date() );
	}

	public function test_asymmetric_visibility_rejects_external_writes_to_id(): void {
		$error = $this->create_error();

		$this->expectException( \Error::class );
		$this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

		/** @phpstan-ignore-next-line */
		$error->id = 99;
	}

	public function test_asymmetric_visibility_rejects_external_writes_to_player_id(): void {
		$error = $this->create_error();

		$this->expectException( \Error::class );
		$this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

		/** @phpstan-ignore-next-line */
		$error->player_id = 99;
	}

	public function test_asymmetric_visibility_rejects_external_writes_to_message(): void {
		$error = $this->create_error();

		$this->expectException( \Error::class );
		$this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

		/** @phpstan-ignore-next-line */
		$error->message = 'New error message';
	}

	public function test_asymmetric_visibility_rejects_external_writes_to_created_date(): void {
		$error = $this->create_error();

		$this->expectException( \Error::class );
		$this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

		/** @phpstan-ignore-next-line */
		$error->created_date = '2026-11-21 12:00:00';
	}

	public function test_set_id_mutates_id_property(): void {
		$error = $this->create_error();
		$this->assertNull( $error->id );
		$this->assertNull( $error->get_id() );

		$error->set_id( 42 );

		$this->assertSame( 42, $error->id );
		$this->assertSame( 42, $error->get_id() );
	}

	public function test_from_object_and_from_array_factories(): void {
		$row = (object) array(
			'id'           => 1,
			'player_id'    => 2,
			'message'      => 'Test message',
			'created_date' => '2026-11-21',
		);

		$error_from_object = Player_Error::from_object( $row );
		$this->assertSame( 1, $error_from_object->id );
		$this->assertSame( 2, $error_from_object->player_id );

		$error_from_array = Player_Error::from_array( (array) $row );
		$this->assertSame( 1, $error_from_array->id );
		$this->assertSame( 2, $error_from_array->player_id );
	}
}
