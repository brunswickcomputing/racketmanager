<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition\Scheduling;

use Closure;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Scheduling\Competition_Scheduler;
use Racketmanager\Domain\Competition\Scheduling\Schedule_Division_DTO;
use Racketmanager\Domain\Competition\Scheduling\Schedule_Request_DTO;
use Racketmanager\Domain\Competition\Scheduling\Schedule_Result_DTO;
use Racketmanager\Domain\Competition\Scheduling\Schedule_Team_DTO;
use Racketmanager\Domain\Competition\Scheduling\Scheduled_Match_DTO;

final class Competition_Scheduler_Test extends TestCase {
	private const array DATES = [
		'2026-04-06',
		'2026-04-13',
		'2026-04-20',
		'2026-04-27',
		'2026-05-04',
		'2026-05-11',
		'2026-05-18',
		'2026-05-25',
		'2026-06-01',
		'2026-06-08',
	];

	private int $next_id = 1;

	protected function setUp(): void {
		$this->next_id = 1;
	}

	// ----------------------------------------------------------------------
	// Helpers
	// ----------------------------------------------------------------------

	private static function identity_shuffle(): Closure {
		return static function ( array &$items ): void {};
	}

	/**
	 * Deterministic engine: identity shuffle, rand_bool always true, frozen clock.
	 */
	private function scheduler( int $max_retries = 1, float $timeout = 60.0, ?Closure $shuffle = null, ?Closure $rand_bool = null, ?Closure $clock = null ): Competition_Scheduler {
		return new Competition_Scheduler(
			$max_retries,
			$timeout,
			$shuffle ?? self::identity_shuffle(),
			$rand_bool ?? static fn(): bool => true,
			$clock ?? static fn(): float => 0.0,
		);
	}

	private function team( int $club_id, string $day = 'Monday', string $time = '18:30', int $event_id = 1, ?string $name = null ): Schedule_Team_DTO {
		$id = $this->next_id++;
		return new Schedule_Team_DTO(
			id: $id,
			name: $name ?? 'T' . $id,
			club_id: $club_id,
			match_day: $day,
			match_time: $time,
			location: 'CLUB' . $club_id,
			preferred_slot: $day . ' ' . $time,
			event_id: $event_id,
		);
	}

	/**
	 * @param int[] $club_ids
	 */
	private function division( int $league_id, array $club_ids, int $event_id = 1, string $day = 'Monday' ): Schedule_Division_DTO {
		$teams = [];
		foreach ( $club_ids as $club_id ) {
			$teams[] = $this->team( $club_id, $day, '18:30', $event_id );
		}
		return new Schedule_Division_DTO( $league_id, 'Division ' . $league_id, $teams );
	}

	/**
	 * @return Scheduled_Match_DTO[]
	 */
	private static function week_fixtures( Schedule_Result_DTO $result, int $league_id, int $week ): array {
		return array_values( array_filter( $result->matches( $league_id, $week ), static fn( Scheduled_Match_DTO $m ): bool => ! $m->is_bye ) );
	}

	/**
	 * Team ids that are home in a given week across all leagues.
	 *
	 * @return int[]
	 */
	private static function home_ids_in_week( Schedule_Result_DTO $result, int $week ): array {
		$ids = [];
		foreach ( $result->schedule as $data ) {
			foreach ( $data['weeks'][ $week ] ?? [] as $match ) {
				if ( ! $match->is_bye ) {
					$ids[] = $match->home_id;
				}
			}
		}
		return $ids;
	}

	/**
	 * Assert every team of a division appears exactly once per week (fixture or bye) and no
	 * fixture entry is duplicated.
	 */
	private function assert_division_invariants( Schedule_Result_DTO $result, Schedule_Division_DTO $division ): void {
		$team_ids = array_map( static fn( Schedule_Team_DTO $t ): int => $t->id, $division->teams );
		for ( $week = 1; $week <= $result->total_weeks; $week++ ) {
			$seen = [];
			$keys = [];
			foreach ( $result->matches( $division->league_id, $week ) as $match ) {
				if ( $match->is_bye ) {
					$seen[] = $match->bye_team_id;
					continue;
				}
				$seen[] = $match->home_id;
				$seen[] = $match->away_id;
				$key    = $match->home_id . '-' . $match->away_id;
				$this->assertNotContains( $key, $keys, "Duplicate fixture $key in week $week" );
				$keys[] = $key;
			}
			sort( $seen );
			sort( $team_ids );
			$this->assertSame( $team_ids, $seen, "Division {$division->league_id} week $week: every team must appear exactly once" );
		}
	}

	// ----------------------------------------------------------------------
	// Circle rotation
	// ----------------------------------------------------------------------

	public function test_circle_rotation_for_six_seeds_has_five_rounds_each_seed_once_per_round_and_every_pair_once(): void {
		$rotation = $this->scheduler()->get_circle_rotation( 6 );

		$this->assertCount( 5, $rotation );
		$pairs = [];
		foreach ( $rotation as $round ) {
			$this->assertCount( 3, $round );
			$seeds = [];
			foreach ( $round as [ $home, $away ] ) {
				$seeds[] = $home;
				$seeds[] = $away;
				$pair    = [ min( $home, $away ), max( $home, $away ) ];
				$pairs[] = implode( '-', $pair );
			}
			sort( $seeds );
			$this->assertSame( [ 1, 2, 3, 4, 5, 6 ], $seeds );
		}
		$this->assertCount( 15, array_unique( $pairs ), 'every pair of seeds meets exactly once' );
	}

	public function test_circle_rotation_matches_legacy_sequence_for_four_seeds(): void {
		// Same output as Admin_Competition_Scheduler::get_circle_rotation( 4 ) on 9.7.0.
		$this->assertSame(
			[
				[ [ 1, 4 ], [ 2, 3 ] ],
				[ [ 4, 3 ], [ 1, 2 ] ],
				[ [ 2, 4 ], [ 3, 1 ] ],
			],
			$this->scheduler()->get_circle_rotation( 4 )
		);
	}

	public function test_circle_rotation_rounds_odd_seed_count_up(): void {
		$this->assertCount( 5, $this->scheduler()->get_circle_rotation( 5 ) );
		$this->assertSame( [ [ [ 1, 2 ] ] ], $this->scheduler()->get_circle_rotation( 1 ) );
		$this->assertSame( [], $this->scheduler()->get_circle_rotation( 0 ) );
	}

	// ----------------------------------------------------------------------
	// Basic scheduling
	// ----------------------------------------------------------------------

	public function test_empty_divisions_return_valid_empty_schedule(): void {
		$result = $this->scheduler()->schedule( new Schedule_Request_DTO( [], 6, false, self::DATES ) );

		$this->assertTrue( $result->is_valid );
		$this->assertSame( 0, $result->retry );
		$this->assertSame( 5, $result->total_weeks );
		$this->assertSame( [], $result->schedule );
		$this->assertSame( [], $result->conflicts );
		$this->assertSame( [], $result->alerts );
		$this->assertSame( [], $result->resource_pairings );
		$this->assertSame( [], $result->fixtures() );
	}

	public function test_full_division_single_round_schedules_every_pair_once_over_five_weeks(): void {
		$division = $this->division( 10, [ 1, 2, 3, 4, 5, 6 ] );
		$result   = $this->scheduler()->schedule( new Schedule_Request_DTO( [ $division ], 6, false, self::DATES ) );

		$this->assertTrue( $result->is_valid );
		$this->assertSame( 1, $result->retry );
		$this->assertSame( 5, $result->total_weeks );
		$this->assertSame( 10, $result->schedule[10]['league_id'] );
		$this->assertSame( 'Division 10', $result->schedule[10]['league_name'] );
		$this->assertCount( 15, $result->fixtures() );
		$this->assert_division_invariants( $result, $division );

		$pairs = [];
		foreach ( $result->fixtures() as $fixture ) {
			$m       = $fixture['match'];
			$pairs[] = min( $m->home_id, $m->away_id ) . '-' . max( $m->home_id, $m->away_id );
		}
		$this->assertCount( 15, array_unique( $pairs ) );
	}

	public function test_home_away_doubles_weeks_and_second_half_swaps_home_and_away(): void {
		$division = $this->division( 1, [ 1, 2, 3, 4, 5, 6 ] );
		$result   = $this->scheduler()->schedule( new Schedule_Request_DTO( [ $division ], 6, true, self::DATES ) );

		$this->assertTrue( $result->is_valid );
		$this->assertSame( 10, $result->total_weeks );
		$this->assertCount( 30, $result->fixtures() );
		$this->assert_division_invariants( $result, $division );

		for ( $week = 1; $week <= 5; $week++ ) {
			$first  = array_map( static fn( Scheduled_Match_DTO $m ): string => $m->home_id . '-' . $m->away_id, self::week_fixtures( $result, 1, $week ) );
			$second = array_map( static fn( Scheduled_Match_DTO $m ): string => $m->away_id . '-' . $m->home_id, self::week_fixtures( $result, 1, $week + 5 ) );
			sort( $first );
			sort( $second );
			$this->assertSame( $first, $second, "week $week and week " . ( $week + 5 ) . ' are mirror images' );
		}
	}

	public function test_fixture_fields_come_from_home_team(): void {
		$home     = new Schedule_Team_DTO( 1, 'Home', 1, 'Wednesday', '19:00', 'HOM', 'Wednesday 19:00', 7 );
		$away     = new Schedule_Team_DTO( 2, 'Away', 2, 'Monday', '18:00', 'AWY', 'Monday 18:00', 7 );
		$division = new Schedule_Division_DTO( 3, 'Div', [ $home, $away ] );
		$result   = $this->scheduler()->schedule( new Schedule_Request_DTO( [ $division ], 2, false, [ '2026-04-06' ] ) );

		$this->assertSame( 1, $result->total_weeks );
		$matches = $result->matches( 3, 1 );
		$this->assertCount( 1, $matches );
		$match = $matches[0];
		$this->assertFalse( $match->is_bye );
		$this->assertSame( 1, $match->home_id );
		$this->assertSame( 2, $match->away_id );
		$this->assertSame( 'Home', $match->home );
		$this->assertSame( 'Away', $match->away );
		$this->assertSame( 'Wednesday 19:00', $match->slot );
		$this->assertSame( '19:00', $match->time );
		$this->assertSame( 'HOM', $match->location );
		$this->assertSame( '1_Wednesday 19:00_C0', $match->resource );
		$this->assertSame( '2026-04-08', $match->date, 'week start Monday + 2 days for Wednesday' );
		$this->assertFalse( $match->is_cross_division );
		$this->assertSame( '2026-04-08', $match->to_array()['date'] );
	}

	public function test_team_fixed_match_day_date_overrides_calculated_date(): void {
		$home     = new Schedule_Team_DTO( 1, 'Home', 1, 'Friday', '19:00', 'HOM', 'Friday 19:00', 1, '2026-12-25' );
		$away     = new Schedule_Team_DTO( 2, 'Away', 2, 'Monday', '18:00', 'AWY', 'Monday 18:00', 1 );
		$division = new Schedule_Division_DTO( 3, 'Div', [ $home, $away ] );
		$result   = $this->scheduler()->schedule( new Schedule_Request_DTO( [ $division ], 2, false, [ '2026-04-06' ] ) );

		$this->assertSame( '2026-12-25', $result->matches( 3, 1 )[0]->date );
	}

	public function test_match_dates_shorter_than_total_weeks_yield_null_dates_without_error(): void {
		$division = $this->division( 1, [ 1, 2, 3, 4, 5, 6 ], 1, 'Tuesday' );
		$result   = $this->scheduler()->schedule( new Schedule_Request_DTO( [ $division ], 6, false, [ '2026-04-06', '2026-04-13' ] ) );

		$this->assertTrue( $result->is_valid );
		foreach ( self::week_fixtures( $result, 1, 1 ) as $match ) {
			$this->assertSame( '2026-04-07', $match->date );
		}
		foreach ( self::week_fixtures( $result, 1, 2 ) as $match ) {
			$this->assertSame( '2026-04-14', $match->date );
		}
		for ( $week = 3; $week <= 5; $week++ ) {
			foreach ( $result->matches( 1, $week ) as $match ) {
				$this->assertNull( $match->date );
			}
		}
	}

	public function test_unparseable_week_start_gives_null_date_when_offset_applies(): void {
		$division = $this->division( 1, [ 1, 2 ], 1, 'Thursday' );
		$result   = $this->scheduler()->schedule( new Schedule_Request_DTO( [ $division ], 2, false, [ 'not-a-date' ] ) );

		$this->assertNull( $result->matches( 1, 1 )[0]->date );
	}

	public function test_matches_within_a_week_are_sorted_by_date_with_unknown_dates_last(): void {
		$teams    = [
			$this->team( 1, 'Friday' ),
			$this->team( 2, 'Monday' ),
			$this->team( 3, 'Wednesday' ),
			$this->team( 4, 'Tuesday' ),
			$this->team( 5, 'Thursday' ),
			$this->team( 6, 'Saturday' ),
		];
		$division = new Schedule_Division_DTO( 1, 'Div', $teams );
		$result   = $this->scheduler()->schedule( new Schedule_Request_DTO( [ $division ], 6, false, [ '2026-04-06' ] ) );

		$week1_dates = array_map( static fn( Scheduled_Match_DTO $m ): ?string => $m->date, $result->matches( 1, 1 ) );
		$sorted      = $week1_dates;
		sort( $sorted );
		$this->assertSame( $sorted, $week1_dates );
		$this->assertSame( [ 1, 2, 3, 4, 5 ], array_keys( $result->schedule[1]['weeks'] ) );
	}

	// ----------------------------------------------------------------------
	// Court resources
	// ----------------------------------------------------------------------

	public function test_same_club_same_slot_teams_in_different_divisions_share_a_court_and_are_never_both_home(): void {
		$div1 = $this->division( 1, [ 1, 2, 3, 4, 5, 6 ] );
		$div2 = $this->division( 2, [ 1, 7, 8, 9, 10, 11 ] );
		$result = $this->scheduler()->schedule( new Schedule_Request_DTO( [ $div1, $div2 ], 6, true, self::DATES ) );

		$this->assertTrue( $result->is_valid );
		$this->assertSame( [], $result->conflicts );

		$club1_a = $div1->teams[0];
		$club1_b = $div2->teams[0];
		$this->assertArrayHasKey( '1_Monday 18:30_C0', $result->resource_pairings );
		$this->assertSame(
			[
				[ 'id' => $club1_a->id, 'name' => $club1_a->name, 'division' => 'Division 1' ],
				[ 'id' => $club1_b->id, 'name' => $club1_b->name, 'division' => 'Division 2' ],
			],
			$result->resource_pairings['1_Monday 18:30_C0']
		);
		$this->assertCount( 11, $result->resource_pairings, 'ten single-team courts plus one shared court' );

		for ( $week = 1; $week <= $result->total_weeks; $week++ ) {
			$homes = self::home_ids_in_week( $result, $week );
			$this->assertFalse(
				in_array( $club1_a->id, $homes, true ) && in_array( $club1_b->id, $homes, true ),
				"court-sharing teams both at home in week $week"
			);
		}
	}

	public function test_teams_with_different_slots_do_not_share_a_court(): void {
		$a        = $this->team( 1, 'Monday', '18:30' );
		$b        = $this->team( 1, 'Monday', '19:30' );
		$division = new Schedule_Division_DTO( 1, 'Div', [ $a, $b, $this->team( 2 ), $this->team( 3 ) ] );
		$result   = $this->scheduler()->schedule( new Schedule_Request_DTO( [ $division ], 4, false, self::DATES ) );

		$this->assertArrayHasKey( '1_Monday 18:30_C0', $result->resource_pairings );
		$this->assertArrayHasKey( '1_Monday 19:30_C0', $result->resource_pairings );
		$this->assertCount( 1, $result->resource_pairings['1_Monday 18:30_C0'] );
	}

	public function test_event_interleaving_pairs_teams_across_events_on_a_shared_slot(): void {
		// Club 1 has a men's (event 1) and a ladies' (event 2) team in each of two divisions per event.
		$men1    = $this->division( 1, [ 1, 2, 3, 4 ], 1 );
		$men2    = $this->division( 2, [ 1, 5, 6, 7 ], 1 );
		$ladies1 = $this->division( 3, [ 1, 8, 9, 10 ], 2 );
		$ladies2 = $this->division( 4, [ 1, 11, 12, 13 ], 2 );
		$result  = $this->scheduler()->schedule( new Schedule_Request_DTO( [ $men1, $men2, $ladies1, $ladies2 ], 4, false, self::DATES ) );

		$court0 = $result->resource_pairings['1_Monday 18:30_C0'];
		$court1 = $result->resource_pairings['1_Monday 18:30_C1'];
		$this->assertSame( [ $men1->teams[0]->id, $ladies1->teams[0]->id ], array_column( $court0, 'id' ), 'E1 pairs with E2, not E1 with E1' );
		$this->assertSame( [ $men2->teams[0]->id, $ladies2->teams[0]->id ], array_column( $court1, 'id' ) );
		$this->assertSame( [ 'Division 1', 'Division 3' ], array_column( $court0, 'division' ) );
	}

	public function test_three_same_slot_teams_give_a_pair_and_a_single_court(): void {
		$div1   = $this->division( 1, [ 1, 2, 3, 4 ] );
		$div2   = $this->division( 2, [ 1, 5, 6, 7 ] );
		$div3   = $this->division( 3, [ 1, 8, 9, 10 ] );
		$result = $this->scheduler()->schedule( new Schedule_Request_DTO( [ $div1, $div2, $div3 ], 4, false, self::DATES ) );

		$this->assertCount( 2, $result->resource_pairings['1_Monday 18:30_C0'] );
		$this->assertCount( 1, $result->resource_pairings['1_Monday 18:30_C1'] );
		$this->assertTrue( $result->is_valid );
	}

	// ----------------------------------------------------------------------
	// Derby seeding
	// ----------------------------------------------------------------------

	public function test_two_teams_of_one_club_in_a_division_meet_in_week_one(): void {
		// Derby teams play on different nights (same-slot teams would be court-paired instead).
		$derby_a  = $this->team( 9, 'Monday' );
		$derby_b  = $this->team( 9, 'Tuesday' );
		$division = new Schedule_Division_DTO( 1, 'Division 1', [ $this->team( 2 ), $this->team( 3 ), $derby_a, $this->team( 5 ), $derby_b, $this->team( 6 ) ] );
		$result   = $this->scheduler()->schedule( new Schedule_Request_DTO( [ $division ], 6, false, self::DATES ) );

		$this->assertTrue( $result->is_valid );
		$week1 = self::week_fixtures( $result, 1, 1 );
		$derby = array_values( array_filter( $week1, static fn( Scheduled_Match_DTO $m ): bool => in_array( $m->home_id, [ $derby_a->id, $derby_b->id ], true ) && in_array( $m->away_id, [ $derby_a->id, $derby_b->id ], true ) ) );
		$this->assertCount( 1, $derby, 'derby teams play each other in week 1' );
		$this->assert_division_invariants( $result, $division );
	}

	public function test_same_slot_derby_teams_are_court_paired_rather_than_forced_to_week_one(): void {
		// Legacy behaviour: court sharing seeds (x, x + n/2) take precedence over derby seeding.
		$division = $this->division( 1, [ 9, 2, 3, 9, 5, 6 ] );
		$derby_a  = $division->teams[0];
		$derby_b  = $division->teams[3];
		$result   = $this->scheduler()->schedule( new Schedule_Request_DTO( [ $division ], 6, true, self::DATES ) );

		$this->assertTrue( $result->is_valid );
		$this->assertSame( [ $derby_a->id, $derby_b->id ], array_column( $result->resource_pairings['9_Monday 18:30_C0'], 'id' ) );
		for ( $week = 1; $week <= 10; $week++ ) {
			$homes = self::home_ids_in_week( $result, $week );
			$this->assertCount( 1, array_intersect( [ $derby_a->id, $derby_b->id ], $homes ), "exactly one derby team at home in week $week" );
		}
	}

	public function test_derby_still_meets_in_week_one_when_one_derby_team_is_pre_seeded_by_court_sharing(): void {
		// Club 9 team in division 1 shares its slot with a club 9 team in division 2 (court seeding),
		// and division 1 also contains a second club 9 team on another slot (derby).
		$shared_a = $this->team( 9, 'Monday' );
		$derby_b  = $this->team( 9, 'Tuesday' );
		$div1     = new Schedule_Division_DTO( 1, 'Division 1', [ $shared_a, $this->team( 2 ), $this->team( 3 ), $derby_b, $this->team( 5 ), $this->team( 6 ) ] );
		$div2     = $this->division( 2, [ 9, 7, 8, 10, 11, 12 ] );
		$result   = $this->scheduler()->schedule( new Schedule_Request_DTO( [ $div1, $div2 ], 6, false, self::DATES ) );

		$this->assertTrue( $result->is_valid );
		$week1 = self::week_fixtures( $result, 1, 1 );
		$derby = array_filter( $week1, static fn( Scheduled_Match_DTO $m ): bool => [ min( $m->home_id, $m->away_id ), max( $m->home_id, $m->away_id ) ] === [ $shared_a->id, $derby_b->id ] );
		$this->assertCount( 1, $derby );
	}

	// ----------------------------------------------------------------------
	// Under-populated divisions / bye replacement
	// ----------------------------------------------------------------------

	public function test_five_team_division_in_six_slot_schedule_has_one_bye_per_week_and_no_extra_matches(): void {
		$division = $this->division( 1, [ 1, 2, 3, 4, 5 ] );
		$result   = $this->scheduler()->schedule( new Schedule_Request_DTO( [ $division ], 6, false, self::DATES ) );

		$this->assertTrue( $result->is_valid );
		$this->assert_division_invariants( $result, $division );
		for ( $week = 1; $week <= 5; $week++ ) {
			$byes = array_filter( $result->matches( 1, $week ), static fn( Scheduled_Match_DTO $m ): bool => $m->is_bye );
			$this->assertCount( 1, $byes );
			$bye = array_values( $byes )[0];
			$this->assertSame( self::DATES[ $week - 1 ], $bye->date );
			$this->assertNotNull( $bye->bye_team_id );
			$this->assertSame( [ 'team', 'team_id', 'is_bye', 'date' ], array_keys( $bye->to_array() ) );
		}
		$this->assertCount( 10, $result->fixtures() );
		$this->assertSame( [], $result->alerts );
	}

	public function test_four_team_division_in_six_slot_schedule_gets_same_division_bye_replacement_matches(): void {
		$small  = $this->division( 1, [ 1, 2, 3, 4 ] );
		$full   = $this->division( 2, [ 5, 6, 7, 8, 9, 10 ] );
		$result = $this->scheduler()->schedule( new Schedule_Request_DTO( [ $small, $full ], 6, true, self::DATES ) );

		$this->assertTrue( $result->is_valid );
		$this->assert_division_invariants( $result, $small );
		$this->assert_division_invariants( $result, $full );

		$small_ids = array_map( static fn( Schedule_Team_DTO $t ): int => $t->id, $small->teams );
		$extras    = [];
		$meetings  = [];
		foreach ( $result->fixtures() as $fixture ) {
			$m   = $fixture['match'];
			$key = min( $m->home_id, $m->away_id ) . '-' . max( $m->home_id, $m->away_id );
			if ( $m->is_cross_division ) {
				$extras[] = $m;
				$this->assertSame( 1, $fixture['league_id'], 'extra matches stay within the under-populated division' );
				$this->assertContains( $m->home_id, $small_ids );
				$this->assertContains( $m->away_id, $small_ids );
				$this->assertSame( 'Extra Match', $m->location );
				$this->assertSame( $m->home_id . '_Monday 18:30_C0', $m->resource, 'extra match uses the home team court' );
				$this->assertSame( self::DATES[ $fixture['week'] - 1 ], $m->date );
			} else {
				$this->assertSame( 'CLUB' . $m->home_id, $m->location );
			}
			if ( in_array( $m->home_id, $small_ids, true ) ) {
				$meetings[ $key ]                = $meetings[ $key ] ?? [];
				$meetings[ $key ][ $m->home_id ] = ( $meetings[ $key ][ $m->home_id ] ?? 0 ) + 1;
			}
		}

		$this->assertNotEmpty( $extras, 'byes were replaced with extra matches' );
		foreach ( $meetings as $key => $home_counts ) {
			$this->assertLessThanOrEqual( 3, array_sum( $home_counts ), "pair $key meets at most three times" );
			foreach ( $home_counts as $count ) {
				$this->assertLessThanOrEqual( 2, $count, "pair $key: at most two home games per side" );
			}
		}

		// The full division is unaffected.
		$this->assertCount( 30, array_filter( $result->fixtures(), static fn( array $f ): bool => 2 === $f['league_id'] ) );
	}

	public function test_bye_replacement_never_leaves_a_team_both_with_a_bye_and_a_match_in_the_same_week(): void {
		$small  = $this->division( 1, [ 1, 2, 3, 4 ] );
		$result = $this->scheduler()->schedule( new Schedule_Request_DTO( [ $small ], 6, false, self::DATES ) );

		for ( $week = 1; $week <= 5; $week++ ) {
			$playing = [];
			$byes    = [];
			foreach ( $result->matches( 1, $week ) as $m ) {
				if ( $m->is_bye ) {
					$byes[] = $m->bye_team_id;
				} else {
					$playing[] = $m->home_id;
					$playing[] = $m->away_id;
				}
			}
			$this->assertSame( [], array_intersect( $byes, $playing ) );
			$this->assertSame( count( $playing ), count( array_unique( $playing ) ) );
		}
	}

	// ----------------------------------------------------------------------
	// Conflicts, retries, best-effort result
	// ----------------------------------------------------------------------

	/**
	 * Club 1 has three same-slot teams in division 1 and one in division 2, club 2 one team in
	 * each division. With deterministic ordering the club 2 pair cannot get complementary seeds
	 * (seeds 1 and 2 of division 1 are already taken) so its teams clash on their shared court.
	 */
	private function conflicting_request( bool $home_away = false ): Schedule_Request_DTO {
		$div1 = $this->division( 1, [ 1, 1, 1, 2 ] );
		$div2 = $this->division( 2, [ 1, 2, 3, 4 ] );
		return new Schedule_Request_DTO( [ $div1, $div2 ], 4, $home_away, self::DATES );
	}

	public function test_unavoidable_conflict_returns_invalid_result_with_conflict_details_and_full_schedule(): void {
		$result = $this->scheduler( 3 )->schedule( $this->conflicting_request() );

		$this->assertFalse( $result->is_valid );
		$this->assertSame( 4, $result->retry, 'all three attempts used, legacy reports max_retries + 1' );
		$this->assertCount( 1, $result->conflicts );
		$conflict = $result->conflicts[0];
		$this->assertSame( [ 'week', 'division', 'court', 'home', 'with' ], array_keys( $conflict ) );
		$this->assertSame( '2_Monday 18:30_C0', $conflict['court'] );
		$this->assertContains( $conflict['home'], [ 'T4', 'T6' ] );
		$this->assertContains( $conflict['with'], [ 'T4', 'T6' ] );
		$this->assertNotSame( $conflict['home'], $conflict['with'] );
		$this->assertGreaterThanOrEqual( 1, $conflict['week'] );

		// Best-effort schedule is still complete.
		$this->assertCount( 12, $result->fixtures() );
		$homes = self::home_ids_in_week( $result, $conflict['week'] );
		$this->assertContains( 4, $homes );
		$this->assertContains( 6, $homes );
	}

	public function test_retry_loop_keeps_best_schedule_and_stops_when_a_conflict_free_seeding_is_found(): void {
		// Attempt 1 (reverse shuffle, rand false) clashes; attempt 2 (rand true) is clean.
		$attempt   = 0;
		$clock     = static function () use ( &$attempt ): float {
			$attempt++;
			return 0.0;
		};
		$rand_bool = static function () use ( &$attempt ): bool {
			return $attempt >= 2;
		};
		$reverse   = static function ( array &$items ): void {
			$items = array_reverse( $items );
		};

		$single = $this->scheduler( 1, 60.0, $reverse, static fn(): bool => false )->schedule( $this->conflicting_request() );
		$this->assertFalse( $single->is_valid, 'precondition: first seeding clashes' );
		$this->assertCount( 1, $single->conflicts );

		$this->next_id = 1;
		$result        = $this->scheduler( 10, 60.0, $reverse, $rand_bool, $clock )->schedule( $this->conflicting_request() );
		$this->assertTrue( $result->is_valid );
		$this->assertSame( 2, $result->retry );
		$this->assertSame( [], $result->conflicts );
	}

	public function test_retry_loop_keeps_the_schedule_with_fewest_conflicts(): void {
		// Attempt 1 (rand false) produces 2 conflicts; attempts 2 and 3 (rand true) produce 1. The best (1) is kept.
		$attempt   = 0;
		$clock     = static function () use ( &$attempt ): float {
			$attempt++;
			return 0.0;
		};
		$rand_bool = static function () use ( &$attempt ): bool {
			return 1 !== $attempt;
		};

		$div1    = $this->division( 1, [ 3, 3, 1, 1 ] );
		$div2    = $this->division( 2, [ 3, 2, 2, 2 ] );
		$div3    = $this->division( 3, [ 1, 2, 2, 2 ] );
		$div4    = $this->division( 4, [ 3, 3, 3, 1 ] );
		$request = new Schedule_Request_DTO( [ $div1, $div2, $div3, $div4 ], 4, false, self::DATES );

		$first = $this->scheduler( 1, 60.0, null, static fn(): bool => false )->schedule( $request );
		$only  = $this->scheduler( 1 )->schedule( $request );
		$this->assertFalse( $first->is_valid );
		$this->assertFalse( $only->is_valid );
		$this->assertGreaterThan( count( $only->conflicts ), count( $first->conflicts ), 'precondition: attempt 1 is worse than later attempts' );

		$result = $this->scheduler( 3, 60.0, null, $rand_bool, $clock )->schedule( $request );
		$this->assertFalse( $result->is_valid );
		$this->assertSame( 4, $result->retry );
		$this->assertSame( 2, count( $first->conflicts ) );
		$this->assertCount( 1, $result->conflicts );
		$this->assertSame( $only->conflicts, $result->conflicts );
	}

	public function test_timeout_ends_the_retry_loop_after_the_first_attempt(): void {
		$now   = 0.0;
		$clock = static function () use ( &$now ): float {
			$now += 100.0;
			return $now;
		};
		$result = $this->scheduler( 1000, 60.0, null, null, $clock )->schedule( $this->conflicting_request() );

		$this->assertFalse( $result->is_valid );
		$this->assertSame( 1, $result->retry );
		$this->assertCount( 1, $result->conflicts );
	}

	public function test_timeout_is_not_consulted_when_a_valid_schedule_is_found_immediately(): void {
		$calls  = 0;
		$clock  = static function () use ( &$calls ): float {
			$calls++;
			return 0.0;
		};
		$result = $this->scheduler( 5, 60.0, null, null, $clock )->schedule( new Schedule_Request_DTO( [ $this->division( 1, [ 1, 2, 3, 4 ] ) ], 4, false, self::DATES ) );

		$this->assertTrue( $result->is_valid );
		$this->assertSame( 1, $result->retry );
		$this->assertSame( 1, $calls, 'only the start time is read' );
	}

	public function test_impossible_seeding_is_retried_and_reported_invalid_with_empty_schedule(): void {
		// Court sharing pre-seeds division 1 seeds 1 and 2 (clubs 2 and 3); the club 1 derby teams
		// then cannot be placed on any week 1 pairing ((1,4) and (2,3)) -> every attempt fails to seed.
		$div1   = new Schedule_Division_DTO( 1, 'Division 1', [ $this->team( 1, 'Monday' ), $this->team( 1, 'Tuesday' ), $this->team( 2 ), $this->team( 3 ) ] );
		$div2   = $this->division( 2, [ 2, 3, 4, 5 ] );
		$result = $this->scheduler( 3 )->schedule( new Schedule_Request_DTO( [ $div1, $div2 ], 4, false, self::DATES ) );

		$this->assertFalse( $result->is_valid );
		$this->assertSame( 4, $result->retry );
		$this->assertSame( [], $result->conflicts );
		$this->assertSame( [], $result->fixtures() );
		$this->assertSame( [], $result->schedule[1]['weeks'] );
		$this->assertSame( [], $result->schedule[2]['weeks'] );
		$this->assertSame( [], $result->alerts );
	}

	// ----------------------------------------------------------------------
	// Equity alerts
	// ----------------------------------------------------------------------

	public function test_equity_alert_raised_when_a_team_has_more_home_games_than_opponents(): void {
		// A 3-team division in a 6-slot schedule: bye replacements add home games beyond teams - 1.
		$small  = $this->division( 1, [ 1, 2, 3 ] );
		$full   = $this->division( 2, [ 4, 5, 6, 7, 8, 9 ] );
		$result = $this->scheduler()->schedule( new Schedule_Request_DTO( [ $small, $full ], 6, true, self::DATES ) );

		$this->assertTrue( $result->is_valid );
		$this->assertNotEmpty( $result->alerts );
		foreach ( $result->alerts as $alert ) {
			$this->assertSame( 'equity_compromise', $alert['type'] );
			$this->assertSame( 'Division 1', $alert['division'] );
			$this->assertContains( $alert['team_name'], [ 'T1', 'T2', 'T3' ] );
			$this->assertGreaterThan( 2, $alert['count'] );
			$this->assertEquals( 5, $alert['ideal'] );
		}

		// Alert counts equal the actual home games of the team.
		$home_counts = [];
		foreach ( $result->fixtures() as $fixture ) {
			$home_counts[ $fixture['match']->home_id ] = ( $home_counts[ $fixture['match']->home_id ] ?? 0 ) + 1;
		}
		foreach ( $result->alerts as $alert ) {
			$team_id = (int) substr( $alert['team_name'], 1 );
			$this->assertSame( $home_counts[ $team_id ], $alert['count'] );
		}
	}

	public function test_no_equity_alert_for_balanced_full_divisions(): void {
		$result = $this->scheduler()->schedule( new Schedule_Request_DTO( [ $this->division( 1, [ 1, 2, 3, 4, 5, 6 ] ) ], 6, true, self::DATES ) );
		$this->assertSame( [], $result->alerts );
	}

	// ----------------------------------------------------------------------
	// Determinism / defaults
	// ----------------------------------------------------------------------

	public function test_default_randomness_produces_a_valid_schedule_respecting_invariants(): void {
		$div1   = $this->division( 1, [ 1, 2, 3, 4, 5, 6 ] );
		$div2   = $this->division( 2, [ 1, 2, 7, 8, 9, 10 ] );
		$result = ( new Competition_Scheduler( 200, 5.0 ) )->schedule( new Schedule_Request_DTO( [ $div1, $div2 ], 6, true, self::DATES ) );

		$this->assertTrue( $result->is_valid );
		$this->assert_division_invariants( $result, $div1 );
		$this->assert_division_invariants( $result, $div2 );
		foreach ( [ '1_Monday 18:30_C0', '2_Monday 18:30_C0' ] as $court ) {
			$pair = array_column( $result->resource_pairings[ $court ], 'id' );
			for ( $week = 1; $week <= $result->total_weeks; $week++ ) {
				$homes = self::home_ids_in_week( $result, $week );
				$this->assertLessThanOrEqual( 1, count( array_intersect( $pair, $homes ) ), "$court double-booked in week $week" );
			}
		}
	}

	public function test_scheduler_instance_can_be_reused_without_leaking_resource_state(): void {
		$scheduler = $this->scheduler();
		$first     = $scheduler->schedule( new Schedule_Request_DTO( [ $this->division( 1, [ 1, 2, 3, 4 ] ) ], 4, false, self::DATES ) );
		$second    = $scheduler->schedule( new Schedule_Request_DTO( [ $this->division( 2, [ 7, 8 ] ) ], 2, false, self::DATES ) );

		$this->assertCount( 4, $first->resource_pairings );
		$this->assertCount( 2, $second->resource_pairings );
		$this->assertArrayNotHasKey( 1, $second->schedule );
	}
}
