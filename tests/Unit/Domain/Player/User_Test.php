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
}
