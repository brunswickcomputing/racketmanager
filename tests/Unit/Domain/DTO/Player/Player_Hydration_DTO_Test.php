<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Player;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Player\Player_Hydration_DTO;

final class Player_Hydration_DTO_Test extends TestCase {
	public function test_from_object_casts_db_shaped_user_row(): void {
		$row = (object) array(
			'ID'              => '42',
			'user_email'      => 'jane@example.test',
			'display_name'    => 'Jane Smith',
			'first_name'      => 'Jane',
			'last_name'       => 'Smith',
			'gender'          => 'F',
			'btm'             => 87654321,
			'year_of_birth'   => '1995',
			'removed_date'    => 20260101,
			'locked_date'     => 20260202,
			'locked_user'     => 9,
			'locked_user_name'=> 0,
			'link'            => 1,
			'user_login'      => 'jane.smith',
		);

		$dto = Player_Hydration_DTO::from_object( $row );

		$this->assertSame( 42, $dto->id );
		$this->assertSame( 'jane@example.test', $dto->email );
		$this->assertSame( 'Jane Smith', $dto->display_name );
		$this->assertSame( 'Jane', $dto->firstname );
		$this->assertSame( 'Smith', $dto->surname );
		$this->assertSame( 'F', $dto->gender );
		$this->assertSame( '87654321', $dto->btm );
		$this->assertSame( 1995, $dto->year_of_birth );
		$this->assertSame( '20260101', $dto->removed_date );
		$this->assertSame( '20260202', $dto->locked_date );
		$this->assertSame( '9', $dto->locked_user );
		$this->assertSame( '0', $dto->locked_user_name );
		$this->assertSame( '1', $dto->link );
		$this->assertSame( 'jane.smith', $dto->user_login );
	}

	public function test_from_object_prefers_id_and_email_aliases(): void {
		$dto = Player_Hydration_DTO::from_object(
			(object) array(
				'id'       => '7',
				'email'    => 'alias@example.test',
				'fullname' => 'Alias Player',
				'surname'  => 'Player',
				'firstname'=> 'Alias',
			)
		);

		$this->assertSame( 7, $dto->id );
		$this->assertSame( 'alias@example.test', $dto->email );
		$this->assertSame( 'Alias Player', $dto->display_name );
		$this->assertSame( 'Alias', $dto->firstname );
		$this->assertSame( 'Player', $dto->surname );
	}

	public function test_from_object_treats_empty_year_of_birth_as_null(): void {
		$dto = Player_Hydration_DTO::from_object(
			(object) array(
				'ID'            => '1',
				'year_of_birth' => '',
			)
		);

		$this->assertNull( $dto->year_of_birth );
	}
}
