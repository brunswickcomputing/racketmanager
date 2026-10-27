<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Competition;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\DTO\Competition\League_Team_Hydration_DTO;
use Racketmanager\Domain\Enums\Team_Profile;

class League_Team_Hydration_DTO_Test extends TestCase {
	public function test_from_object_casts_wpdb_string_row_to_declared_types(): void {
		$dto = League_Team_Hydration_DTO::from_object( (object) array(
			'id'             => '25',
			'table_id'       => '500',
			'team_id'        => '25',
			'league_id'      => '3',
			'season'         => '2026',
			'title'          => 'Team Alpha',
			'club_id'        => '10',
			'stadium'        => 'Centre Court',
			'home'           => '1',
			'profile'        => '2',
			'status'         => 'W',
			'points_plus'    => '10.5',
			'points_minus'   => '2',
			'points_2_plus'  => '5',
			'points_2_minus' => '1',
			'add_points'     => '0.5',
			'done_matches'   => '4',
			'won_matches'    => '3',
			'draw_matches'   => '0',
			'lost_matches'   => '1',
			'diff'           => '8',
			'group'          => 'A',
			'rank'           => '1',
			'rating'         => '7.25',
			'captain'        => '77',
			'match_day'      => 'Monday',
			'match_time'     => '19:00:00',
			'team_type'      => 'P',
			'league_status'  => 'active',
		) );

		$this->assertSame( 25, $dto->id );
		$this->assertSame( 500, $dto->table_id );
		$this->assertSame( 25, $dto->team_id );
		$this->assertSame( 3, $dto->league_id );
		$this->assertSame( '2026', $dto->season );
		$this->assertSame( 'Team Alpha', $dto->title );
		$this->assertSame( 10, $dto->club_id );
		$this->assertSame( 'Centre Court', $dto->stadium );
		$this->assertTrue( $dto->home );
		$this->assertSame( 2, $dto->profile );
		$this->assertSame( 'W', $dto->status );
		$this->assertSame( 10.5, $dto->points_plus );
		$this->assertSame( 2.0, $dto->points_minus );
		$this->assertSame( 5, $dto->points_2_plus );
		$this->assertSame( 1, $dto->points_2_minus );
		$this->assertSame( 0.5, $dto->add_points );
		$this->assertSame( 4, $dto->done_matches );
		$this->assertSame( 3, $dto->won_matches );
		$this->assertSame( 0, $dto->draw_matches );
		$this->assertSame( 1, $dto->lost_matches );
		$this->assertSame( '8', $dto->diff );
		$this->assertSame( 'A', $dto->group );
		$this->assertSame( 1, $dto->rank );
		$this->assertSame( 7.25, $dto->rating );
		$this->assertSame( 77, $dto->captain );
		$this->assertSame( 'Monday', $dto->match_day );
		$this->assertSame( '19:00:00', $dto->match_time );
		$this->assertSame( 'P', $dto->team_type );
		$this->assertSame( 'active', $dto->league_status );
		$this->assertSame( array(), $dto->custom );
		$this->assertNull( $dto->roster );
	}

	public function test_missing_columns_are_null_and_empty_strings_follow_entity_coercion(): void {
		$dto = League_Team_Hydration_DTO::from_object( (object) array(
			'points_plus' => '',
			'rank'        => '',
			'old_rank'    => '',
			'rating'      => '',
			'captain'     => '',
			'captain_id'  => '',
			'table_id'    => '',
			'home'        => '',
			'status'      => '',
		) );

		$this->assertNull( $dto->id );
		$this->assertNull( $dto->title );
		$this->assertNull( $dto->league_id );
		$this->assertSame( 0.0, $dto->points_plus, "'' coerces to 0.0 for a non-nullable float property" );
		$this->assertSame( 0, $dto->table_id, "'' coerces to 0 for a non-nullable int property" );
		$this->assertFalse( $dto->home );
		$this->assertSame( 0, $dto->rank, "'' coerces to 0 for a nullable int property" );
		$this->assertSame( 0, $dto->old_rank, "'' coerces to 0 for a nullable int property" );
		$this->assertSame( 0.0, $dto->rating, "'' coerces to 0.0 for a nullable float property" );
		$this->assertSame( 0, $dto->captain, "'' coerces to 0 for a nullable int property" );
		$this->assertSame( 0, $dto->captain_id, "'' coerces to 0 for a nullable int property" );
		$this->assertSame( '', $dto->status, "'' is kept for string properties" );

		$missing = League_Team_Hydration_DTO::from_object( new \stdClass() );
		$this->assertNull( $missing->rank );
		$this->assertNull( $missing->old_rank );
		$this->assertNull( $missing->rating );
		$this->assertNull( $missing->captain );
		$this->assertNull( $missing->captain_id );

		$explicit_null = League_Team_Hydration_DTO::from_object( (object) array(
			'rank'       => null,
			'old_rank'   => null,
			'rating'     => null,
			'captain'    => null,
			'captain_id' => null,
		) );
		$this->assertNull( $explicit_null->rank );
		$this->assertNull( $explicit_null->old_rank );
		$this->assertNull( $explicit_null->rating );
		$this->assertNull( $explicit_null->captain );
		$this->assertNull( $explicit_null->captain_id );

		$empty_entity = League_Team::from_object( (object) array(
			'rank'       => '',
			'old_rank'   => '',
			'rating'     => '',
			'captain'    => '',
			'captain_id' => '',
		) );
		$this->assertSame( 0, $empty_entity->rank );
		$this->assertSame( 0, $empty_entity->old_rank );
		$this->assertSame( 0.0, $empty_entity->rating );
		$this->assertSame( 0, $empty_entity->captain );
		$this->assertSame( 0, $empty_entity->captain_id );

		$null_entity = League_Team::from_object( (object) array(
			'rank'       => null,
			'old_rank'   => null,
			'rating'     => null,
			'captain'    => null,
			'captain_id' => null,
		) );
		$this->assertNull( $null_entity->rank );
		$this->assertNull( $null_entity->old_rank );
		$this->assertNull( $null_entity->rating );
		$this->assertNull( $null_entity->captain );
		$this->assertNull( $null_entity->captain_id );
	}

	public function test_custom_column_is_decoded_unslashed_and_merged_over_table_columns(): void {
		$dto = League_Team_Hydration_DTO::from_object( (object) array(
			'id'         => 25,
			'title'      => 'Team Alpha',
			'diff'       => 8,
			'add_points' => '0',
			'custom'     => serialize(
				array(
					'add_points'   => 2,
					'title'        => "O\\'Brien Park",
					'diff'         => 12,
					'sets_won'     => '6',
					'sets_shared'  => 0,
					'straight_set' => array( 'win' => 1, 'lost' => 0 ),
					'unknown'      => 'kept only inside custom',
				)
			),
		) );

		$this->assertSame( "O'Brien Park", $dto->title, 'custom keys override table columns and are unslashed' );
		$this->assertSame( 2.0, $dto->add_points );
		$this->assertSame( 12, $dto->diff );
		$this->assertSame( 6, $dto->sets_won );
		$this->assertSame( '0', $dto->sets_shared );
		$this->assertSame( array( 'win' => 1, 'lost' => 0 ), $dto->straight_set );
		$this->assertSame( "O'Brien Park", $dto->custom['title'] );
		$this->assertSame( 'kept only inside custom', $dto->custom['unknown'] );
		$this->assertFalse( property_exists( $dto, 'unknown' ) );
	}

	public function test_array_custom_and_empty_custom_are_accepted(): void {
		$array_custom = League_Team_Hydration_DTO::from_object( (object) array( 'custom' => array( 'games_won' => 3 ) ) );
		$this->assertSame( array( 'games_won' => 3 ), $array_custom->custom );
		$this->assertSame( 3, $array_custom->games_won );

		$empty_custom = League_Team_Hydration_DTO::from_object( (object) array( 'custom' => '' ) );
		$this->assertSame( array(), $empty_custom->custom );

		$null_custom = League_Team_Hydration_DTO::from_object( (object) array( 'custom' => null ) );
		$this->assertSame( array(), $null_custom->custom );
	}

	public function test_roster_is_decoded_from_serialized_column(): void {
		$dto = League_Team_Hydration_DTO::from_object( (object) array( 'roster' => serialize( array( 77, 78 ) ) ) );
		$this->assertSame( array( 77, 78 ), $dto->roster );

		$already_decoded = League_Team_Hydration_DTO::from_object( (object) array( 'roster' => array( 1 ) ) );
		$this->assertSame( array( 1 ), $already_decoded->roster );

		$empty = League_Team_Hydration_DTO::from_object( (object) array( 'roster' => '' ) );
		$this->assertSame( '', $empty->roster );
	}

	public function test_unknown_row_keys_are_dropped_and_keys_are_trimmed(): void {
		$dto = League_Team_Hydration_DTO::from_object( (object) array(
			'id'          => 3,
			' rank '      => '4',
			'competition' => (object) array( 'id' => 1 ),
			'event_id'    => 9,
			'team_count'  => 7,
		) );

		$this->assertSame( 3, $dto->id );
		$this->assertSame( 4, $dto->rank );
		foreach ( array( 'competition', 'event_id', 'team_count' ) as $dropped ) {
			$this->assertFalse( property_exists( $dto, $dropped ), $dropped );
		}
	}

	public function test_from_array_matches_from_object(): void {
		$data = array(
			'id'          => '1',
			'title'       => 'Team_Alpha',
			'points_plus' => '3',
			'custom'      => serialize( array( 'games_won' => 2 ) ),
		);

		$this->assertEquals( League_Team_Hydration_DTO::from_object( (object) $data ), League_Team_Hydration_DTO::from_array( $data ) );
	}

	/**
	 * Legacy parity: the old constructor applied `(int)` to any present `profile` value, so '' and NULL
	 * resolved to Team_Profile::PENDING (0); only an absent column leaves the entity profile unset.
	 */
	public function test_present_empty_profile_coerces_to_zero_and_absent_profile_stays_null(): void {
		$this->assertSame( 0, League_Team_Hydration_DTO::from_object( (object) array( 'id' => 1, 'profile' => '' ) )->profile );
		$this->assertSame( 0, League_Team_Hydration_DTO::from_object( (object) array( 'id' => 1, 'profile' => null ) )->profile );
		$this->assertSame( 3, League_Team_Hydration_DTO::from_object( (object) array( 'id' => 1, 'profile' => '3' ) )->profile );
		$this->assertNull( League_Team_Hydration_DTO::from_object( (object) array( 'id' => 1 ) )->profile );

		$this->assertSame( 0, League_Team::from_object( (object) array( 'id' => 1, 'profile' => '' ) )->get_profile() );
		$this->assertSame( Team_Profile::PENDING, League_Team::from_object( (object) array( 'id' => 1, 'profile' => '' ) )->get_profile_enum() );
		$this->assertNull( League_Team::from_object( (object) array( 'id' => 1 ) )->get_profile() );
	}
}
