<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Player\Commands;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Player\Commands\Create_Player_Command;
use Racketmanager\Application\Player\Commands\Create_Player_Handler;
use Racketmanager\Application\Player\Commands\Update_Player_Command;
use Racketmanager\Application\Player\Commands\Update_Player_Handler;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Exceptions\Duplicate_BTM_Exception;
use Racketmanager\Exceptions\Duplicate_Email_Exception;
use Racketmanager\Exceptions\Player_Exists_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Updated_Exception;
use Racketmanager\Services\Player_Service;
use WP_Error;

require_once dirname( __DIR__, 4 ) . '/wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Player_Commands_Test extends TestCase {

	public function test_update_player_command_from_post_and_to_array(): void {
		$post = [
			'playerId'      => '42',
			'firstname'     => 'Jane',
			'surname'       => 'Smith',
			'gender'        => 'F',
			'email'         => 'jane.smith@example.com',
			'contactno'     => '07123456789',
			'btm'           => '87654321',
			'year_of_birth' => '1995',
			'locked'        => '1',
			'club_Id'       => '5',
			'page_referrer' => 'player-edit-page',
		];

		$command = Update_Player_Command::from_post( $post );

		$this->assertSame( 42, $command->player_id );
		$this->assertSame( 'Jane', $command->first_name );
		$this->assertSame( 'Smith', $command->last_name );
		$this->assertSame( 'F', $command->gender );
		$this->assertSame( 'jane.smith@example.com', $command->email );
		$this->assertSame( '07123456789', $command->telephone );
		$this->assertSame( '87654321', $command->btm );
		$this->assertSame( 1995, $command->year_of_birth );
		$this->assertTrue( $command->locked );
		$this->assertSame( 5, $command->club_id );
		$this->assertSame( 'player-edit-page', $command->page_referrer );

		$array = $command->to_array();
		$this->assertSame( 42, $array['playerId'] );
		$this->assertSame( 42, $array['id'] );
		$this->assertSame( 'Jane', $array['firstname'] );
		$this->assertSame( 'Smith', $array['surname'] );
		$this->assertSame( 'F', $array['gender'] );
		$this->assertSame( 'jane.smith@example.com', $array['email'] );
		$this->assertSame( '07123456789', $array['contactno'] );
		$this->assertSame( '87654321', $array['btm'] );
		$this->assertSame( 1995, $array['year_of_birth'] );
		$this->assertSame( 'Locked', $array['locked'] );
		$this->assertSame( 5, $array['club_Id'] );
		$this->assertSame( 'player-edit-page', $array['page_referrer'] );
	}

	public function test_update_player_command_from_post_alternative_keys_and_unlocked(): void {
		$post = [
			'id'         => '10',
			'first_name' => 'John',
			'last_name'  => 'Doe',
			'telephone'  => '0123456789',
			'dob'        => '1988',
			'club_id'    => '3',
		];

		$command = Update_Player_Command::from_post( $post );

		$this->assertSame( 10, $command->player_id );
		$this->assertSame( 'John', $command->first_name );
		$this->assertSame( 'Doe', $command->last_name );
		$this->assertSame( '0123456789', $command->telephone );
		$this->assertSame( 1988, $command->year_of_birth );
		$this->assertNull( $command->locked );
		$this->assertSame( 3, $command->club_id );

		$array = $command->to_array();
		$this->assertArrayNotHasKey( 'locked', $array );
		$this->assertSame( 10, $array['playerId'] );
		$this->assertSame( 'John', $array['firstname'] );
		$this->assertSame( 'Doe', $array['surname'] );
	}

	public function test_update_player_handler_delegates_to_service_success(): void {
		$player = $this->createMock( Player::class );
		$service = $this->createMock( Player_Service::class );
		$service->expects( $this->once() )
			->method( 'amend_player_details' )
			->with(
				$this->identicalTo( 42 ),
				$this->callback( function ( array $data ): bool {
					return isset( $data['playerId'] ) && 42 === $data['playerId']
						&& 'Jane' === $data['firstname']
						&& 'Smith' === $data['surname'];
				} )
			)
			->willReturn( $player );

		$handler = new Update_Player_Handler( $service );
		$command = new Update_Player_Command(
			player_id: 42,
			first_name: 'Jane',
			last_name: 'Smith'
		);

		$result = $handler->handle( $command );
		$this->assertSame( $player, $result );
	}

	public function test_update_player_handler_returns_wp_error(): void {
		$wp_error = new WP_Error( 'invalid_email', 'Invalid email address' );
		$service  = $this->createMock( Player_Service::class );
		$service->expects( $this->once() )
			->method( 'amend_player_details' )
			->willReturn( $wp_error );

		$handler = new Update_Player_Handler( $service );
		$command = new Update_Player_Command( player_id: 42, email: 'bad-email' );

		$result = $handler->handle( $command );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_email', $result->get_error_code() );
	}

	public function test_update_player_handler_propagates_exceptions(): void {
		$service = $this->createMock( Player_Service::class );
		$service->expects( $this->once() )
			->method( 'amend_player_details' )
			->willThrowException( new Player_Not_Found_Exception( 'Player 999 not found' ) );

		$handler = new Update_Player_Handler( $service );
		$command = new Update_Player_Command( player_id: 999 );

		$this->expectException( Player_Not_Found_Exception::class );
		$this->expectExceptionMessage( 'Player 999 not found' );
		$handler->handle( $command );
	}

	public function test_create_player_command_from_post_and_to_array(): void {
		$post = [
			'firstname'     => 'Alice',
			'surname'       => 'Wonder',
			'gender'        => 'F',
			'email'         => 'alice@example.com',
			'contactno'     => '01234567890',
			'btm'           => '11223344',
			'year_of_birth' => '2000',
			'locked'        => '1',
			'club_Id'       => '8',
			'page_referrer' => 'add-player-page',
		];

		$command = Create_Player_Command::from_post( $post );

		$this->assertSame( 'Alice', $command->first_name );
		$this->assertSame( 'Wonder', $command->last_name );
		$this->assertSame( 'F', $command->gender );
		$this->assertSame( 'alice@example.com', $command->email );
		$this->assertSame( '01234567890', $command->telephone );
		$this->assertSame( '11223344', $command->btm );
		$this->assertSame( 2000, $command->year_of_birth );
		$this->assertTrue( $command->locked );
		$this->assertSame( 8, $command->club_id );
		$this->assertSame( 'add-player-page', $command->page_referrer );

		$array = $command->to_array();
		$this->assertSame( 'Alice', $array['firstname'] );
		$this->assertSame( 'Wonder', $array['surname'] );
		$this->assertSame( 'F', $array['gender'] );
		$this->assertSame( 'alice@example.com', $array['email'] );
		$this->assertSame( '01234567890', $array['contactno'] );
		$this->assertSame( '11223344', $array['btm'] );
		$this->assertSame( 2000, $array['year_of_birth'] );
		$this->assertSame( 'Locked', $array['locked'] );
		$this->assertSame( 8, $array['club_Id'] );
		$this->assertSame( 'add-player-page', $array['page_referrer'] );
	}

	public function test_create_player_handler_delegates_to_service_success(): void {
		$player  = $this->createMock( Player::class );
		$service = $this->createMock( Player_Service::class );
		$service->expects( $this->once() )
			->method( 'add_new_player' )
			->with( $this->callback( function ( array $data ): bool {
				return 'Alice' === $data['firstname']
					&& 'Wonder' === $data['surname']
					&& 'alice@example.com' === $data['email']
					&& 8 === $data['club_Id'];
			} ) )
			->willReturn( $player );

		$handler = new Create_Player_Handler( $service );
		$command = new Create_Player_Command(
			first_name: 'Alice',
			last_name: 'Wonder',
			gender: 'F',
			email: 'alice@example.com',
			club_id: 8
		);

		$result = $handler->handle( $command );
		$this->assertSame( $player, $result );
	}

	public function test_create_player_handler_returns_wp_error(): void {
		$wp_error = new WP_Error( 'duplicate_btm', 'BTM already registered' );
		$service  = $this->createMock( Player_Service::class );
		$service->expects( $this->once() )
			->method( 'add_new_player' )
			->willReturn( $wp_error );

		$handler = new Create_Player_Handler( $service );
		$command = new Create_Player_Command( first_name: 'Bob', last_name: 'Builder' );

		$result = $handler->handle( $command );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'duplicate_btm', $result->get_error_code() );
	}

	public function test_create_player_handler_propagates_exceptions(): void {
		$service = $this->createMock( Player_Service::class );
		$service->expects( $this->once() )
			->method( 'add_new_player' )
			->willThrowException( new Player_Exists_Exception( 'Player already exists' ) );

		$handler = new Create_Player_Handler( $service );
		$command = new Create_Player_Command( first_name: 'Bob', last_name: 'Builder' );

		$this->expectException( Player_Exists_Exception::class );
		$this->expectExceptionMessage( 'Player already exists' );
		$handler->handle( $command );
	}
}
