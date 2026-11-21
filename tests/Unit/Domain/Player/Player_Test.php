<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Player;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Player\Player;
use stdClass;

final class Player_Test extends TestCase {
	public function test_player_initialization(): void {
		$data               = new stdClass();
		$data->ID           = 55;
		$data->user_email   = 'player@example.com';
		$data->display_name = 'Andy Murray';
		$data->firstname    = 'Andy';
		$data->surname      = 'Murray';
		$data->gender       = 'M';
		$data->btm          = '100200';

		$player = Player::from_object( $data );

		$this->assertSame( 55, $player->id );
		$this->assertSame( 55, $player->ID );
		$this->assertSame( 'player@example.com', $player->email );
		$this->assertSame( 'player@example.com', $player->user_email );
		$this->assertSame( 'Andy Murray', $player->display_name );
		$this->assertSame( 'Andy Murray', $player->fullname );
		$this->assertSame( 'Andy', $player->firstname );
		$this->assertSame( 'Murray', $player->surname );
		$this->assertSame( 'M', $player->gender );
		$this->assertSame( '100200', (string) $player->btm );
	}

	public function test_asymmetric_visibility_rejects_external_writes_to_id(): void {
		$player = new Player();

		$this->expectException( \Error::class );
		$this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

		/** @phpstan-ignore-next-line */
		$player->id = 10;
	}

	public function test_asymmetric_visibility_rejects_external_writes_to_email(): void {
		$player = new Player();

		$this->expectException( \Error::class );
		$this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

		/** @phpstan-ignore-next-line */
		$player->email = 'new@example.com';
	}

	public function test_asymmetric_visibility_rejects_external_writes_to_firstname(): void {
		$player = new Player();

		$this->expectException( \Error::class );
		$this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

		/** @phpstan-ignore-next-line */
		$player->firstname = 'Roger';
	}

	public function test_asymmetric_visibility_rejects_external_writes_to_matches(): void {
		$player = new Player();

		$this->expectException( \Error::class );
		$this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

		/** @phpstan-ignore-next-line */
		$player->matches = [ 'match1' ];
	}

	public function test_asymmetric_visibility_rejects_external_writes_to_stats(): void {
		$player = new Player();

		$this->expectException( \Error::class );
		$this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

		/** @phpstan-ignore-next-line */
		$player->stats = [ 'total' => 1 ];
	}

	public function test_asymmetric_visibility_rejects_external_writes_to_statistics(): void {
		$player = new Player();

		$this->expectException( \Error::class );
		$this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

		/** @phpstan-ignore-next-line */
		$player->statistics = [ 'detail' => [] ];
	}

	public function test_asymmetric_visibility_rejects_external_writes_to_club(): void {
		$player = new Player();

		$this->expectException( \Error::class );
		$this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

		/** @phpstan-ignore-next-line */
		$player->club = new stdClass();
	}

	public function test_asymmetric_visibility_rejects_external_writes_to_team(): void {
		$player = new Player();

		$this->expectException( \Error::class );
		$this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

		/** @phpstan-ignore-next-line */
		$player->team = new stdClass();
	}

	public function test_set_id_updates_both_id_and_id_alias(): void {
		$player = new Player();
		$player->set_id( 77 );

		$this->assertSame( 77, $player->id );
		$this->assertSame( 77, $player->ID );
		$this->assertSame( 77, $player->get_id() );
	}

	public function test_set_email_updates_both_email_and_user_email_alias(): void {
		$player = new Player();
		$player->set_email( 'novak@example.com' );

		$this->assertSame( 'novak@example.com', $player->email );
		$this->assertSame( 'novak@example.com', $player->user_email );
		$this->assertSame( 'novak@example.com', $player->get_email() );
	}

	public function test_set_display_name_updates_both_display_name_and_fullname_alias(): void {
		$player = new Player();
		$player->set_display_name( 'Novak Djokovic' );

		$this->assertSame( 'Novak Djokovic', $player->display_name );
		$this->assertSame( 'Novak Djokovic', $player->fullname );
		$this->assertSame( 'Novak Djokovic', $player->get_fullname() );
	}

	public function test_mutators_update_fields(): void {
		$player = new Player();
		$player->set_firstname( 'Carlos' );
		$player->set_surname( 'Alcaraz' );
		$player->set_contactno( '0123456789' );
		$player->set_gender( 'M' );
		$player->set_btm( '334455' );
		$player->set_link( '/player/carlos-alcaraz/' );
		$player->set_index( 'Alcaraz, Carlos' );
		$player->set_locked_user_name( 'admin' );
		$player->set_locked( true );
		$player->set_locked_date( '2026-11-21' );
		$player->set_locked_user( 1 );
		$player->set_removed_date( '2026-11-20' );
		$player->set_removed_user( 2 );

		$this->assertSame( 'Carlos', $player->firstname );
		$this->assertSame( 'Carlos', $player->get_firstname() );
		$this->assertSame( 'Alcaraz', $player->surname );
		$this->assertSame( 'Alcaraz', $player->get_surname() );
		$this->assertSame( '0123456789', $player->contactno );
		$this->assertSame( '0123456789', $player->get_contactno() );
		$this->assertSame( 'M', $player->gender );
		$this->assertSame( 'M', $player->get_gender() );
		$this->assertSame( '334455', (string) $player->btm );
		$this->assertSame( '334455', (string) $player->get_btm() );
		$this->assertSame( '/player/carlos-alcaraz/', $player->link );
		$this->assertSame( 'Alcaraz, Carlos', $player->index );
		$this->assertSame( 'admin', $player->locked_user_name );
		$this->assertTrue( (bool) $player->locked );
		$this->assertSame( '2026-11-21', $player->locked_date );
		$this->assertSame( 1, (int) $player->locked_user );
		$this->assertSame( '2026-11-20', $player->removed_date );
		$this->assertSame( 2, (int) $player->removed_user );
	}

	public function test_stats_and_matches_mutators(): void {
		$player = new Player();
		$match  = (object) [ 'id' => 101 ];
		$player->set_matches( [ $match ] );
		$this->assertSame( [ $match ], $player->matches );

		$stats = [ 'total' => (object) [ 'played' => 5 ] ];
		$player->set_stats( $stats );
		$this->assertSame( $stats, $player->stats );

		$statistics = [ 'detail' => [], 'total' => new stdClass() ];
		$player->set_statistics( $statistics );
		$this->assertSame( $statistics, $player->statistics );

		$player->set_stats_summary( 10, 2, 83.3, 12 );
		$this->assertSame( 10, $player->matches_won );
		$this->assertSame( 2, $player->matches_lost );
		$this->assertSame( 83.3, $player->win_pct );
		$this->assertSame( 12, $player->played );
	}

	public function test_club_and_teams_mutators(): void {
		$player = new Player();
		$club   = (object) [ 'id' => 5, 'name' => 'Wimbledon' ];
		$team   = (object) [ 'id' => 12, 'name' => 'First Team' ];
		$teams  = [ $team ];

		$player->set_club( $club );
		$player->set_team( $team );
		$player->set_teams( $teams );

		$this->assertSame( $club, $player->club );
		$this->assertSame( $team, $player->team );
		$this->assertSame( $teams, $player->teams );
	}

	public function test_year_of_birth_calculates_age(): void {
		$player = new Player();
		$player->set_year_of_birth( 2003 );

		$expected_age = (int) gmdate( 'Y' ) - 2003;
		$this->assertSame( 2003, $player->year_of_birth );
		$this->assertSame( $expected_age, $player->age );

		$player->set_year_of_birth( null );
		$this->assertNull( $player->year_of_birth );
		$this->assertSame( 0, $player->age );
	}
}
