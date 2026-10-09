<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Player;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Player\Player_Hydration_DTO;
use Racketmanager\Domain\Player;
use Racketmanager\Domain\Player_Factory;

class Player_Factory_Test extends TestCase {
	public function test_create_from_dto(): void {
		$dto = new Player_Hydration_DTO(
			id: 42,
			email: 'player@example.test',
			display_name: 'John Doe',
			firstname: 'John',
			surname: 'Doe',
			gender: 'M',
			btm: '12345678',
			year_of_birth: 1990
		);

		$player = Player_Factory::create_from_dto( $dto );

		$this->assertInstanceOf( Player::class, $player );
		$this->assertSame( 42, $player->get_id() );
		$this->assertSame( 'player@example.test', $player->get_email() );
		$this->assertSame( 'John Doe', $player->get_fullname() );
		$this->assertSame( 'John', $player->get_firstname() );
		$this->assertSame( 'Doe', $player->get_surname() );
		$this->assertSame( 'M', $player->get_gender() );
		$this->assertSame( '12345678', $player->get_btm() );
	}

	public function test_from_object(): void {
		$row = (object) [
			'ID'            => '99',
			'user_email'    => 'jane@example.test',
			'display_name'  => 'Jane Smith',
			'first_name'    => 'Jane',
			'last_name'     => 'Smith',
			'gender'        => 'F',
			'btm'           => '87654321',
			'year_of_birth' => '1995',
		];

		$player = Player_Factory::from_object( $row );

		$this->assertInstanceOf( Player::class, $player );
		$this->assertSame( 99, $player->get_id() );
		$this->assertSame( 'jane@example.test', $player->get_email() );
		$this->assertSame( 'Jane Smith', $player->get_fullname() );
		$this->assertSame( 'Jane', $player->get_firstname() );
		$this->assertSame( 'Smith', $player->get_surname() );
		$this->assertSame( 'F', $player->get_gender() );
	}
}
