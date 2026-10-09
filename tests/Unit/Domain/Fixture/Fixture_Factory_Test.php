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
}
