<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Player;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Player\User;

final class User_Test extends TestCase {
	public function test_user_initialization(): void {
		$data               = new \stdClass();
		$data->ID           = 42;
		$data->user_email   = 'test@example.com';
		$data->display_name = 'John Doe';

		$user = User::from_object( $data );

		$this->assertSame( 42, $user->id );
		$this->assertSame( 42, $user->ID );
		$this->assertSame( 'test@example.com', $user->email );
		$this->assertSame( 'test@example.com', $user->user_email );
		$this->assertSame( 'John Doe', $user->fullname );
		$this->assertSame( 'John Doe', $user->display_name );
	}

	public function test_user_does_not_expose_get_messages(): void {
		$user = new User();
		$this->assertFalse( method_exists( $user, 'get_messages' ) );
	}

	public function test_asymmetric_visibility_rejects_external_writes_to_id(): void {
		$user = new User();

		$this->expectException( \Error::class );
		$this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

		/** @phpstan-ignore-next-line */
		$user->id = 99;
	}

	public function test_asymmetric_visibility_rejects_external_writes_to_email(): void {
		$user = new User();

		$this->expectException( \Error::class );
		$this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

		/** @phpstan-ignore-next-line */
		$user->email = 'new@example.com';
	}

	public function test_asymmetric_visibility_rejects_external_writes_to_firstname(): void {
		$user = new User();

		$this->expectException( \Error::class );
		$this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

		/** @phpstan-ignore-next-line */
		$user->firstname = 'Alice';
	}

	public function test_set_id_updates_both_id_and_id_alias(): void {
		$user = new User();
		$user->set_id( 123 );

		$this->assertSame( 123, $user->id );
		$this->assertSame( 123, $user->ID );
	}

	public function test_set_email_updates_both_email_and_user_email_alias(): void {
		$user = new User();
		$user->set_email( 'alice@example.com' );

		$this->assertSame( 'alice@example.com', $user->email );
		$this->assertSame( 'alice@example.com', $user->user_email );
	}

	public function test_set_display_name_updates_both_display_name_and_fullname_alias(): void {
		$user = new User();
		$user->set_display_name( 'Alice Smith' );

		$this->assertSame( 'Alice Smith', $user->display_name );
		$this->assertSame( 'Alice Smith', $user->fullname );
	}

	public function test_mutators_update_fields(): void {
		$user = new User();
		$user->set_firstname( 'Alice' );
		$user->set_surname( 'Smith' );
		$user->set_contactno( '01234567890' );
		$user->set_gender( 'female' );
		$user->set_btm( 102030 );
		$user->set_password( 'secret123' );
		$user->set_re_password( 'secret123' );

		$this->assertSame( 'Alice', $user->firstname );
		$this->assertSame( 'Smith', $user->surname );
		$this->assertSame( '01234567890', $user->contactno );
		$this->assertSame( 'female', $user->gender );
		$this->assertSame( 102030, $user->btm );
		$this->assertSame( 'secret123', $user->password );
		$this->assertSame( 'secret123', $user->re_password );
	}

	public function test_set_year_of_birth_calculates_age(): void {
		$user = new User();
		$user->set_year_of_birth( 2000 );

		$expected_age = (int) gmdate( 'Y' ) - 2000;
		$this->assertSame( 2000, $user->year_of_birth );
		$this->assertSame( $expected_age, $user->age );

		$user->set_year_of_birth( null );
		$this->assertNull( $user->year_of_birth );
		$this->assertSame( 0, $user->age );
	}

	public function test_set_opt_ins_normalizes_data(): void {
		$user = new User();

		$user->set_opt_ins( [ 'news', 'events' ] );
		$this->assertSame( [ 'news', 'events' ], $user->opt_ins );

		$user->set_opt_ins( 'single_opt_in' );
		$this->assertSame( [ 'single_opt_in' ], $user->opt_ins );

		$user->set_opt_ins( null );
		$this->assertSame( [], $user->opt_ins );
	}

	public function test_set_locked_status(): void {
		$user = new User();
		$user->set_locked_status( true, '2026-11-21 10:00:00', 1, 'admin' );

		$this->assertTrue( $user->locked );
		$this->assertSame( '2026-11-21 10:00:00', $user->locked_date );
		$this->assertSame( 1, $user->locked_user );
		$this->assertSame( 'admin', $user->locked_user_name );
	}

	public function test_set_favourites(): void {
		$user       = new User();
		$favourites = [ 'club' => [ 1, 2 ] ];
		$user->set_favourites( $favourites );

		$this->assertSame( $favourites, $user->favourites );
	}

	public function test_update_profile(): void {
		$user = new User();
		$user->update_profile(
			email: 'bob@example.com',
			firstname: 'Bob',
			surname: 'Jones',
			contactno: '07700900123',
			gender: 'male',
			btm: '998877',
			year_of_birth: '1995',
			password: 'pass',
			re_password: 'pass',
			opt_ins: [ 'marketing' ]
		);

		$this->assertSame( 'bob@example.com', $user->email );
		$this->assertSame( 'bob@example.com', $user->user_email );
		$this->assertSame( 'Bob', $user->firstname );
		$this->assertSame( 'Jones', $user->surname );
		$this->assertSame( '07700900123', $user->contactno );
		$this->assertSame( 'male', $user->gender );
		$this->assertSame( '998877', $user->btm );
		$this->assertSame( '1995', $user->year_of_birth );
		$this->assertSame( (int) gmdate( 'Y' ) - 1995, $user->age );
		$this->assertSame( 'pass', $user->password );
		$this->assertSame( 'pass', $user->re_password );
		$this->assertSame( [ 'marketing' ], $user->opt_ins );
	}
}
