<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Club;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club_Player;
use Racketmanager\Domain\DTO\Club\Club_Player_DTO;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Player_Test_Factory;

class Club_Player_DTO_Test extends TestCase {

	public function test_construct_with_null_display_name(): void {
		$player = Player_Test_Factory::create_sparse( [
			'id'           => 10,
			'display_name' => null,
			'firstname'    => null,
			'surname'      => null,
		] );

		$club = Club_Test_Factory::create( shortcode: 'TST' );

		$registration = new Club_Player();
		$registration->id = 1;
		$registration->player_id = 10;
		$registration->club_id = 2;

		$club_player_dto = new Club_Player_DTO(
			$player,
			$club,
			$registration,
			null,
			null,
			null
		);

		$this->assertSame( 1, $club_player_dto->registration_id );
		$this->assertSame( 10, $club_player_dto->user_id );
		$this->assertSame( 2, $club_player_dto->club_id );
		$this->assertSame( '', $club_player_dto->display_name );
		$this->assertSame( 'TST', $club_player_dto->club_name );
		$this->assertSame( 'Test Club', $club_player_dto->club_display_name );
	}

	public function test_construct_with_valid_display_name(): void {
		$player = Player_Test_Factory::create_sparse( [
			'id'           => 20,
			'display_name' => 'John Doe',
			'firstname'    => 'John',
			'surname'      => 'Doe',
		] );

		$club = Club_Test_Factory::create( shortcode: 'ABC' );

		$registration = new Club_Player();
		$registration->id = 5;
		$registration->player_id = 20;
		$registration->club_id = 3;

		$club_player_dto = new Club_Player_DTO(
			$player,
			$club,
			$registration,
			'Admin User',
			'Approver User',
			null
		);

		$this->assertSame( 5, $club_player_dto->registration_id );
		$this->assertSame( 20, $club_player_dto->user_id );
		$this->assertSame( 'John Doe', $club_player_dto->display_name );
		$this->assertSame( 'John', $club_player_dto->firstname );
		$this->assertSame( 'Doe', $club_player_dto->surname );
		$this->assertSame( 'ABC', $club_player_dto->club_name );
		$this->assertSame( 'Test Club', $club_player_dto->club_display_name );
		$this->assertSame( 'Admin User', $club_player_dto->registered_by_user );
		$this->assertSame( 'Approver User', $club_player_dto->approved_by_user );
	}
}
