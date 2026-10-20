<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Player;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Player\Player_Hydration_DTO;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Player\Player_Factory;

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

	public function test_factory_derives_link_with_btm_and_locked_user_name(): void {
		$player = Player_Factory::from_array(
			array(
				'ID'           => '100',
				'display_name' => 'Jane Smith',
				'btm'          => '87654321',
				'locked_user'  => '17',
			)
		);

		$this->assertSame( '/player/jane-smith/87654321/', $player->link );
		$this->assertSame( 'User 17', $player->locked_user_name );
	}

	public function test_factory_sets_empty_locked_user_name_without_locked_user(): void {
		$player = Player_Factory::from_array( array( 'ID' => '101', 'display_name' => 'No Lock' ) );

		$this->assertSame( '', $player->locked_user_name );
		$this->assertSame( '/player/no-lock/', $player->link );
	}

	public function test_create_player_with_null_display_name(): void {
		$dto = new Player_Hydration_DTO(
			id: 102,
			display_name: null
		);

		$player = Player_Factory::create_from_dto( $dto );

		$this->assertInstanceOf( Player::class, $player );
		$this->assertNull( $player->fullname );
		$this->assertNull( $player->display_name );
		$this->assertSame( '', $player->get_fullname() );
		$this->assertSame( '', $player->get_name() );
		$this->assertNull( $player->team );
		$this->assertNull( $player->status );
	}

	public function test_from_empty_array(): void {
		$player = Player_Factory::from_array( array() );

		$this->assertInstanceOf( Player::class, $player );
		$this->assertNull( $player->get_id() );
		$this->assertNull( $player->fullname );
		$this->assertSame( '', $player->get_fullname() );
	}

	public function test_player_get_ranking_integration(): void {
		$player = Player_Factory::from_array( array(
			'id'  => 10,
			'wtn' => array(
				'singles' => '22.5',
				'doubles' => '-',
			),
		) );

		$singles_ranking = $player->get_ranking( 'singles' );
		$this->assertInstanceOf( \Racketmanager\Domain\Player\Player_Ranking::class, $singles_ranking );
		$this->assertTrue( $singles_ranking->has_rating() );
		$this->assertSame( 22.5, $singles_ranking->get_rating() );
		$this->assertSame( '22.5', $singles_ranking->get_raw_value() );

		$doubles_ranking = $player->get_ranking( 'doubles' );
		$this->assertInstanceOf( \Racketmanager\Domain\Player\Player_Ranking::class, $doubles_ranking );
		$this->assertFalse( $doubles_ranking->has_rating() );
		$this->assertNull( $doubles_ranking->get_rating() );
		$this->assertSame( '-', $doubles_ranking->get_raw_value() );

		// Discipline lookup by single letter 'S'
		$s_ranking = $player->get_ranking( 'S' );
		$this->assertTrue( $s_ranking->has_rating() );
		$this->assertSame( 22.5, $s_ranking->get_rating() );

		// Unset discipline
		$unknown_ranking = $player->get_ranking( 'mixed' );
		$this->assertFalse( $unknown_ranking->has_rating() );
		$this->assertNull( $unknown_ranking->get_rating() );
	}
}
