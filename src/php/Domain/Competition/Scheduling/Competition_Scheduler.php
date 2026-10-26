<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Competition\Scheduling;

use Closure;
use DateTimeImmutable;
use Exception;

/**
 * Conflict-minimising league scheduler (port of the 9.7.0 Admin_Competition_Scheduler).
 *
 * Algorithm:
 *  1. Map teams to physical court resources: teams of a club sharing a slot are
 *     interleaved by event and paired two per court (`{club}_{slot}_C{n}`).
 *  2. Rank divisions by aggregate club demand.
 *  3. Retry loop over random seedings. Teams sharing a court get complementary seeds
 *     `x` and `x + n/2` so they are never both at home in the same week; same-club teams
 *     in one division (derbies) are seeded to meet in week 1; remaining seeds are random.
 *     A circle-rotation round robin is played out from the seeds; the schedule with the
 *     fewest same-court-same-week conflicts is kept, stopping early at zero conflicts,
 *     on `max_retries` or on `timeout`.
 *  4. Byes in under-populated divisions are replaced by extra matches within the
 *     same division (max three meetings per pair, at most two at home for either side).
 *  5. Teams with more home matches than `teams - 1` raise an equity alert.
 *
 * Pure: no WordPress, database or global state. Randomness and the clock are injectable
 * so results can be made deterministic in tests.
 */
final class Competition_Scheduler {
	public const int DEFAULT_MAX_RETRIES = 375550;
	public const float DEFAULT_TIMEOUT   = 60.0;

	private const array DAY_OFFSETS = [
		'Monday'    => 0,
		'Tuesday'   => 1,
		'Wednesday' => 2,
		'Thursday'  => 3,
		'Friday'    => 4,
		'Saturday'  => 5,
		'Sunday'    => 6,
	];

	private Closure $shuffle_fn;
	private Closure $rand_bool;
	private Closure $clock;

	private int $teams_per_division = 0;
	private int $total_weeks        = 0;
	/** @var string[] */
	private array $match_dates = [];
	/** @var array<int, string> team id => resource id */
	private array $team_resources = [];
	/** @var array<string, array<int, array{id: int, name: string, division: string}>> */
	private array $resource_pairings = [];

	/**
	 * @param int          $max_retries Maximum number of seeding attempts.
	 * @param float        $timeout     Wall-clock budget in seconds for the retry loop.
	 * @param Closure|null $shuffle     `fn( array &$items ): void` – defaults to shuffle().
	 * @param Closure|null $rand_bool   `fn(): bool` – defaults to random_int( 0, 1 ).
	 * @param Closure|null $clock       `fn(): float` – defaults to microtime( true ).
	 */
	public function __construct(
		private readonly int $max_retries = self::DEFAULT_MAX_RETRIES,
		private readonly float $timeout = self::DEFAULT_TIMEOUT,
		?Closure $shuffle = null,
		?Closure $rand_bool = null,
		?Closure $clock = null,
	) {
		$this->shuffle_fn = $shuffle ?? static function ( array &$items ): void {
			shuffle( $items );
		};
		$this->rand_bool  = $rand_bool ?? static fn(): bool => 1 === random_int( 0, 1 );
		$this->clock      = $clock ?? static fn(): float => microtime( true );
	}

	/**
	 * Build the schedule for all divisions of the request.
	 *
	 * @param Schedule_Request_DTO $request
	 * @return Schedule_Result_DTO
	 */
	public function schedule( Schedule_Request_DTO $request ): Schedule_Result_DTO {
		$start_time               = ( $this->clock )();
		$this->teams_per_division = $request->teams_per_division;
		$this->match_dates        = array_values( $request->match_dates );
		$this->total_weeks        = $request->total_weeks();
		$this->team_resources     = [];
		$this->resource_pairings  = [];

		/** @var array<int, Schedule_Division_DTO> $divisions */
		$divisions = [];
		foreach ( $request->divisions as $division ) {
			$divisions[ $division->league_id ] = $division;
		}

		if ( empty( $divisions ) ) {
			return new Schedule_Result_DTO(
				retry: 0,
				is_valid: true,
				total_weeks: $this->total_weeks,
			);
		}

		// 1. Global resource mapping.
		$this->map_physical_courts( $divisions );
		$ranked_divisions = $this->rank_divisions( $divisions );
		$rotation         = $this->get_circle_rotation( $this->teams_per_division );
		$half_weeks       = $this->teams_per_division - 1;
		$n                = $this->teams_per_division;
		$half             = intdiv( $n, 2 );

		$best_schedule    = $this->empty_schedule( $divisions );
		$best_home_counts = $this->zero_home_counts( $divisions );
		$best_conflicts   = null;
		$best_valid       = false;
		$alerts           = [];

		// 2. Retry loop for seed assignment.
		for ( $retry = 0; $retry < $this->max_retries; $retry++ ) {
			$division_seeds            = [];
			$court_usage               = [];
			$current_retry_home_counts = $this->zero_home_counts( $divisions );
			$current_schedule          = $this->empty_schedule( $divisions );

			// 2.0 Shuffle resource pairings to change allocation order on each retry.
			$res_keys = array_keys( $this->resource_pairings );
			$this->shuffle( $res_keys );

			// 2.1 Initial seeding for shared resources: complementary seeds x and x + n/2.
			foreach ( $res_keys as $res_id ) {
				$teams = $this->resource_pairings[ $res_id ];
				if ( count( $teams ) < 2 ) {
					continue;
				}
				if ( ( $this->rand_bool )() ) {
					$t1_id = $teams[0]['id'];
					$t2_id = $teams[1]['id'];
				} else {
					$t1_id = $teams[1]['id'];
					$t2_id = $teams[0]['id'];
				}

				$div1_id = $this->find_division_id_by_team_id( $divisions, $t1_id );
				$div2_id = $this->find_division_id_by_team_id( $divisions, $t2_id );
				if ( null === $div1_id || null === $div2_id ) {
					continue;
				}

				$seeds_to_try = $half > 0 ? range( 1, $half ) : [];
				$this->shuffle( $seeds_to_try );
				foreach ( $seeds_to_try as $x ) {
					$seed_t1 = $x;
					$seed_t2 = $x + $half;
					if ( ! isset( $division_seeds[ $div1_id ][ $seed_t1 ] ) && ! isset( $division_seeds[ $div2_id ][ $seed_t2 ] ) ) {
						$division_seeds[ $div1_id ][ $seed_t1 ] = $divisions[ $div1_id ]->find_team( $t1_id );
						$division_seeds[ $div2_id ][ $seed_t2 ] = $divisions[ $div2_id ]->find_team( $t2_id );
						break;
					}
				}
			}

			// 2.2 Finalise remaining seeds for each division (derbies first, then random).
			$failed_to_seed = false;
			$div_ids        = array_keys( $ranked_divisions );
			$this->shuffle( $div_ids );
			foreach ( $div_ids as $div_id ) {
				$division                  = $ranked_divisions[ $div_id ];
				$division_seeds[ $div_id ] = $this->finalize_seeds( $division->teams, $n, $division_seeds[ $div_id ] ?? [] );
				if ( count( $division_seeds[ $div_id ] ) < count( $division->teams ) ) {
					$failed_to_seed = true;
					break;
				}
			}
			if ( $failed_to_seed ) {
				if ( $this->timed_out( $start_time ) ) {
					break;
				}
				continue;
			}

			// 3. Generate the schedule from the seeds.
			$conflicts = [];
			for ( $week = 1; $week <= $this->total_weeks; $week++ ) {
				$week_in_rotation = ( $week - 1 ) % $half_weeks;
				$is_second_half   = $week > $half_weeks;
				foreach ( $divisions as $div_id => $division ) {
					$seeds = $division_seeds[ $div_id ] ?? [];
					if ( empty( $seeds ) ) {
						continue;
					}
					foreach ( $rotation[ $week_in_rotation ] as $pair ) {
						[ $p1, $p2 ] = $pair;
						if ( $is_second_half ) {
							[ $p1, $p2 ] = [ $p2, $p1 ];
						}
						$t1 = $seeds[ $p1 ] ?? null;
						$t2 = $seeds[ $p2 ] ?? null;
						if ( null === $t1 || null === $t2 ) {
							$real = $t1 ?? $t2;
							if ( null !== $real ) {
								$current_schedule[ $div_id ]['weeks'][ $week ][] = Scheduled_Match_DTO::bye( $real, $this->match_dates[ $week - 1 ] ?? null );
							}
							continue;
						}

						// Home/away is deterministic from the seeds.
						$home = $t1;
						$away = $t2;
						$res  = $this->team_resources[ $home->id ];
						if ( isset( $court_usage[ $res ][ $week ] ) ) {
							$conflicts[] = [
								'week'     => $week,
								'division' => $division->league_name,
								'court'    => $res,
								'home'     => $home->name,
								'with'     => $court_usage[ $res ][ $week ],
							];
						}
						$court_usage[ $res ][ $week ]                    = $home->name;
						$current_retry_home_counts[ $home->id ]          = ( $current_retry_home_counts[ $home->id ] ?? 0 ) + 1;
						$current_schedule[ $div_id ]['weeks'][ $week ][] = Scheduled_Match_DTO::fixture(
							$home,
							$away,
							$this->match_date_for( $home, $week ),
							$res,
						);
					}
				}
			}

			// 4. Keep the best schedule so far (fewest conflicts; ties go to the latest attempt).
			if ( empty( $conflicts ) ) {
				$best_schedule    = $current_schedule;
				$best_conflicts   = [];
				$best_home_counts = $current_retry_home_counts;
				$best_valid       = true;
				break;
			}
			if ( null === $best_conflicts || count( $conflicts ) <= count( $best_conflicts ) ) {
				$best_schedule    = $current_schedule;
				$best_conflicts   = $conflicts;
				$best_home_counts = $current_retry_home_counts;
			}
			if ( $this->timed_out( $start_time ) ) {
				break;
			}
		}

		$best_conflicts ??= [];
		$this->sort_schedule( $best_schedule );

		// 5. Bye-replacement matches for under-populated divisions.
		$this->add_bye_replacement_matches( $divisions, $best_schedule, $best_home_counts );

		// 6. Equity compromise detection.
		foreach ( $divisions as $division ) {
			$max_allowed_home = count( $division->teams ) - 1;
			foreach ( $division->teams as $team ) {
				$count = $best_home_counts[ $team->id ] ?? 0;
				if ( $count > $max_allowed_home ) {
					$alerts[] = [
						'type'      => 'equity_compromise',
						'team_name' => $team->name,
						'division'  => $division->league_name,
						'count'     => $count,
						'ideal'     => $this->total_weeks / 2,
					];
				}
			}
		}

		return new Schedule_Result_DTO(
			retry: $retry + 1,
			is_valid: $best_valid,
			total_weeks: $this->total_weeks,
			schedule: $best_schedule,
			conflicts: $best_conflicts,
			alerts: $alerts,
			resource_pairings: $this->resource_pairings,
		);
	}

	/**
	 * Round-robin rotation for `n` seeds (rounded up to even): `rounds[round][] = [home_seed, away_seed]`.
	 *
	 * @param int $n
	 * @return array<int, array<int, array{0: int, 1: int}>>
	 */
	public function get_circle_rotation( int $n ): array {
		if ( 0 !== $n % 2 ) {
			$n++;
		}
		if ( $n < 2 ) {
			return [];
		}
		$teams_range = range( 1, $n );
		$half        = intdiv( $n, 2 );
		$indexes     = $teams_range;
		$rounds      = [];

		for ( $round_num = 1; $round_num < $n; $round_num++ ) {
			$fixtures = [];
			$start    = 0;

			// In even rounds the highest index is home.
			if ( 0 === $round_num % 2 ) {
				$home       = $teams_range[ $indexes[ $n - 1 ] - 1 ];
				$away       = $teams_range[ $indexes[0] - 1 ];
				$fixtures[] = [ $home, $away ];
				++$start;
			}
			for ( $i = $start; $i < $half; $i++ ) {
				$home       = $teams_range[ $indexes[ $i ] - 1 ];
				$away       = $teams_range[ $indexes[ $n - 1 - $i ] - 1 ];
				$fixtures[] = [ $home, $away ];
			}
			$rounds[] = $fixtures;

			// Rotate: keep the constant (last) index, move the first half to the end.
			$constant   = array_splice( $indexes, $n - 1, 1 )[0];
			$first_half = array_splice( $indexes, 0, $half );
			$indexes    = array_merge( $indexes, $first_half );
			$indexes[]  = $constant;
		}

		return $rounds;
	}

	/**
	 * Group teams by club + slot, interleave by event and allocate two teams per court.
	 *
	 * @param array<int, Schedule_Division_DTO> $divisions
	 */
	private function map_physical_courts( array $divisions ): void {
		$grouped_teams = [];
		foreach ( $divisions as $division ) {
			foreach ( $division->teams as $team ) {
				$key                     = $team->club_id . '_' . $team->slot();
				$grouped_teams[ $key ][] = [
					'team'     => $team,
					'div_name' => $division->league_name,
				];
			}
		}

		foreach ( $grouped_teams as $key => $entries ) {
			// Interleave events so that e.g. men's and ladies' teams pair across events:
			// E1, E1, E2, E2 -> E1, E2, E1, E2 -> courts (E1, E2), (E1, E2).
			$by_event = [];
			foreach ( $entries as $entry ) {
				$by_event[ $entry['team']->event_id ][] = $entry;
			}
			$interleaved = [];
			$max_count   = max( array_map( 'count', $by_event ) );
			for ( $i = 0; $i < $max_count; $i++ ) {
				foreach ( $by_event as $event_teams ) {
					if ( isset( $event_teams[ $i ] ) ) {
						$interleaved[] = $event_teams[ $i ];
					}
				}
			}

			foreach ( $interleaved as $idx => $entry ) {
				$team                               = $entry['team'];
				$res                                = $key . '_C' . intdiv( $idx, 2 );
				$this->team_resources[ $team->id ]  = $res;
				$this->resource_pairings[ $res ][] = [
					'id'       => $team->id,
					'name'     => $team->name,
					'division' => $entry['div_name'],
				];
			}
		}
	}

	/**
	 * Order divisions by aggregate club demand (most constrained first).
	 *
	 * @param array<int, Schedule_Division_DTO> $divisions
	 * @return array<int, Schedule_Division_DTO>
	 */
	private function rank_divisions( array $divisions ): array {
		$club_demand = [];
		foreach ( $divisions as $division ) {
			foreach ( $division->teams as $team ) {
				$club_demand[ $team->club_id ] = ( $club_demand[ $team->club_id ] ?? 0 ) + 1;
			}
		}
		$scores = [];
		foreach ( $divisions as $id => $division ) {
			$score = 0;
			foreach ( $division->teams as $team ) {
				$score += $club_demand[ $team->club_id ];
			}
			$scores[ $id ] = $score;
		}
		arsort( $scores );
		$ordered = [];
		foreach ( array_keys( $scores ) as $id ) {
			$ordered[ $id ] = $divisions[ $id ];
		}
		return $ordered;
	}

	/**
	 * Complete the seeding of a division: derbies meet in week 1, the rest is random.
	 * Returns an empty array when no valid seeding exists.
	 *
	 * @param Schedule_Team_DTO[]             $teams
	 * @param int                             $n
	 * @param array<int, Schedule_Team_DTO>   $existing_seeds
	 * @return array<int, Schedule_Team_DTO>
	 */
	private function finalize_seeds( array $teams, int $n, array $existing_seeds = [] ): array {
		$seeds        = $existing_seeds;
		$club_counts  = array_count_values( array_map( static fn( Schedule_Team_DTO $t ): int => $t->club_id, $teams ) );
		$assigned_ids = [];
		foreach ( $seeds as $t ) {
			$assigned_ids[ $t->id ] = true;
		}

		// 1. Derbies (exactly two teams of one club in the division).
		$derby_club_ids = array_keys( $club_counts, 2, true );
		if ( ! empty( $derby_club_ids ) ) {
			$this->shuffle( $derby_club_ids );
			foreach ( $derby_club_ids as $derby_club_id ) {
				$derby_teams = array_values( array_filter( $teams, static fn( Schedule_Team_DTO $t ): bool => $t->club_id === $derby_club_id ) );
				$this->shuffle( $derby_teams );

				$d1_seed = null;
				$d2_seed = null;
				foreach ( $seeds as $seed => $t ) {
					if ( $t->id === $derby_teams[0]->id ) {
						$d1_seed = $seed;
					}
					if ( $t->id === $derby_teams[1]->id ) {
						$d2_seed = $seed;
					}
				}

				if ( null === $d1_seed && null === $d2_seed ) {
					// Preferred week 1 pairing: seeds (1, n).
					if ( ! isset( $seeds[1] ) && ! isset( $seeds[ $n ] ) ) {
						$seeds[1]                              = $derby_teams[0];
						$seeds[ $n ]                           = $derby_teams[1];
						$assigned_ids[ $derby_teams[0]->id ]   = true;
						$assigned_ids[ $derby_teams[1]->id ]   = true;
					} else {
						// Otherwise any free pair that meets in week 1.
						$found_any   = false;
						$week1_pairs = $this->get_circle_rotation( $n )[0] ?? [];
						$this->shuffle( $week1_pairs );
						foreach ( $week1_pairs as $pair ) {
							if ( ! isset( $seeds[ $pair[0] ] ) && ! isset( $seeds[ $pair[1] ] ) ) {
								$seeds[ $pair[0] ]                   = $derby_teams[0];
								$seeds[ $pair[1] ]                   = $derby_teams[1];
								$assigned_ids[ $derby_teams[0]->id ] = true;
								$assigned_ids[ $derby_teams[1]->id ] = true;
								$found_any                           = true;
								break;
							}
						}
						if ( ! $found_any ) {
							return [];
						}
					}
				} elseif ( null !== $d1_seed && null === $d2_seed ) {
					// Only one is seeded: the other must be its week 1 opponent.
					$target = $this->get_week1_opponent( $d1_seed, $n );
					if ( null !== $target && ! isset( $seeds[ $target ] ) ) {
						$seeds[ $target ]                    = $derby_teams[1];
						$assigned_ids[ $derby_teams[1]->id ] = true;
					} else {
						return [];
					}
				} elseif ( null === $d1_seed && null !== $d2_seed ) {
					$target = $this->get_week1_opponent( $d2_seed, $n );
					if ( null !== $target && ! isset( $seeds[ $target ] ) ) {
						$seeds[ $target ]                    = $derby_teams[0];
						$assigned_ids[ $derby_teams[0]->id ] = true;
					} else {
						return [];
					}
				}
			}
		}

		// 2. Remaining seeds at random.
		$unassigned_teams = [];
		foreach ( $teams as $t ) {
			if ( ! isset( $assigned_ids[ $t->id ] ) ) {
				$unassigned_teams[] = $t;
			}
		}
		$this->shuffle( $unassigned_teams );

		$available_seeds = [];
		$team_count      = count( $teams );
		for ( $i = 1; $i <= $team_count; $i++ ) {
			if ( ! isset( $seeds[ $i ] ) ) {
				$available_seeds[] = $i;
			}
		}
		if ( count( $unassigned_teams ) > count( $available_seeds ) ) {
			return [];
		}
		foreach ( $unassigned_teams as $idx => $team ) {
			$seeds[ $available_seeds[ $idx ] ] = $team;
		}

		return $seeds;
	}

	/**
	 * Seed that meets `seed` in the first round of the rotation.
	 *
	 * @param int $seed
	 * @param int $n
	 * @return int|null
	 */
	private function get_week1_opponent( int $seed, int $n ): ?int {
		$week1 = $this->get_circle_rotation( $n )[0] ?? [];
		foreach ( $week1 as $pair ) {
			if ( $pair[0] === $seed ) {
				return $pair[1];
			}
			if ( $pair[1] === $seed ) {
				return $pair[0];
			}
		}
		return null;
	}

	/**
	 * Replace byes in under-populated divisions with extra same-division matches.
	 *
	 * @param array<int, Schedule_Division_DTO> $divisions
	 * @param array                             $best_schedule    Modified in place.
	 * @param array<int, int>                   $best_home_counts Modified in place.
	 */
	private function add_bye_replacement_matches( array $divisions, array &$best_schedule, array &$best_home_counts ): void {
		// Meetings between pairs: "lowId_highId" => [ id => home count ].
		$meeting_counts = [];
		foreach ( $best_schedule as $div_data ) {
			foreach ( $div_data['weeks'] as $matches ) {
				foreach ( $matches as $match ) {
					/** @var Scheduled_Match_DTO $match */
					if ( $match->is_bye || null === $match->home_id || null === $match->away_id ) {
						continue;
					}
					$key = $this->meeting_key( $match->home_id, $match->away_id );
					if ( ! isset( $meeting_counts[ $key ] ) ) {
						$meeting_counts[ $key ] = [
							$match->home_id => 0,
							$match->away_id => 0,
						];
					}
					$meeting_counts[ $key ][ $match->home_id ]++;
				}
			}
		}

		$under_populated_div_ids = [];
		foreach ( $divisions as $div_id => $division ) {
			if ( count( $division->teams ) <= ( $this->teams_per_division - 1 ) ) {
				$under_populated_div_ids[] = $div_id;
			}
		}
		if ( empty( $under_populated_div_ids ) ) {
			return;
		}

		for ( $week = 1; $week <= $this->total_weeks; $week++ ) {
			// Teams already in a real match this week.
			$already_playing_team_ids = [];
			foreach ( $best_schedule as $div_data ) {
				foreach ( $div_data['weeks'][ $week ] ?? [] as $match ) {
					if ( ! $match->is_bye ) {
						if ( null !== $match->home_id ) {
							$already_playing_team_ids[ $match->home_id ] = true;
						}
						if ( null !== $match->away_id ) {
							$already_playing_team_ids[ $match->away_id ] = true;
						}
					}
				}
			}

			// Teams with a bye this week in under-populated divisions.
			$teams_with_byes = [];
			foreach ( $under_populated_div_ids as $under_div_id ) {
				foreach ( $best_schedule[ $under_div_id ]['weeks'][ $week ] ?? [] as $idx => $match ) {
					if ( $match->is_bye && null !== $match->bye_team_id && ! isset( $already_playing_team_ids[ $match->bye_team_id ] ) ) {
						$team_data = $divisions[ $under_div_id ]->find_team( $match->bye_team_id );
						if ( null === $team_data ) {
							continue;
						}
						$teams_with_byes[] = [
							'div_id'    => $under_div_id,
							'match_idx' => $idx,
							'team_data' => $team_data,
						];
					}
				}
			}

			$this->shuffle( $teams_with_byes );
			$to_process      = $teams_with_byes;
			$removed_indexes = [];
			while ( count( $to_process ) >= 2 ) {
				$t1_info = array_shift( $to_process );
				foreach ( $to_process as $i => $t2_info ) {
					// Only pair teams from the same division.
					if ( $t1_info['div_id'] !== $t2_info['div_id'] ) {
						continue;
					}
					/** @var Schedule_Team_DTO $home */
					$home = $t1_info['team_data'];
					/** @var Schedule_Team_DTO $away */
					$away = $t2_info['team_data'];
					$key  = $this->meeting_key( $home->id, $away->id );

					$pair_counts    = $meeting_counts[ $key ] ?? [
						$home->id => 0,
						$away->id => 0,
					];
					$total_meetings = array_sum( $pair_counts );

					// At most three meetings per pair and at most two at home for either side (2+1 / 1+2).
					if ( $total_meetings < 3 && $pair_counts[ $home->id ] < 2 ) {
						array_splice( $to_process, $i, 1 );

						$new_match = Scheduled_Match_DTO::fixture(
							$home,
							$away,
							$this->match_date_for( $home, $week ),
							$this->team_resources[ $home->id ] ?? 'N/A',
							'Extra Match',
							true,
						);

						// Replace the home team's bye with the match and drop the away team's bye
						// (both are in the same division, so a single entry represents the fixture).
						$best_schedule[ $t1_info['div_id'] ]['weeks'][ $week ][ $t1_info['match_idx'] ] = $new_match;
						$removed_indexes[ $t2_info['div_id'] ][]                                        = $t2_info['match_idx'];

						$best_home_counts[ $home->id ] = ( $best_home_counts[ $home->id ] ?? 0 ) + 1;
						$pair_counts[ $home->id ]++;
						$meeting_counts[ $key ] = $pair_counts;

						$already_playing_team_ids[ $home->id ] = true;
						$already_playing_team_ids[ $away->id ] = true;
						break;
					}
				}
			}

			foreach ( $removed_indexes as $div_id => $indexes ) {
				foreach ( $indexes as $idx ) {
					unset( $best_schedule[ $div_id ]['weeks'][ $week ][ $idx ] );
				}
				$best_schedule[ $div_id ]['weeks'][ $week ] = array_values( $best_schedule[ $div_id ]['weeks'][ $week ] );
			}
		}

		$this->sort_schedule( $best_schedule );
	}

	/**
	 * Sort leagues and weeks by key and matches within a week by date (nulls last).
	 *
	 * @param array $schedule Modified in place.
	 */
	private function sort_schedule( array &$schedule ): void {
		ksort( $schedule );
		foreach ( $schedule as &$div_data ) {
			if ( ! isset( $div_data['weeks'] ) ) {
				$div_data['weeks'] = [];
			}
			ksort( $div_data['weeks'] );
			foreach ( $div_data['weeks'] as &$matches ) {
				usort(
					$matches,
					static fn( Scheduled_Match_DTO $a, Scheduled_Match_DTO $b ): int => strcmp( $a->date ?? '9999-99-99', $b->date ?? '9999-99-99' )
				);
			}
			unset( $matches );
		}
		unset( $div_data );
	}

	/**
	 * Match date for a home team in a week: the team's fixed date, or the week start
	 * offset to the team's match day.
	 *
	 * @param Schedule_Team_DTO $home
	 * @param int               $week
	 * @return string|null
	 */
	private function match_date_for( Schedule_Team_DTO $home, int $week ): ?string {
		return $home->match_day_date ?? $this->calculate_match_date( $week, $home->match_day );
	}

	/**
	 * Week start date advanced to the day named in `slot` (Monday = week start).
	 *
	 * @param int    $week
	 * @param string $slot
	 * @return string|null
	 */
	private function calculate_match_date( int $week, string $slot ): ?string {
		$week_start = $this->match_dates[ $week - 1 ] ?? null;
		if ( empty( $week_start ) ) {
			return null;
		}
		$offset = 0;
		foreach ( self::DAY_OFFSETS as $day => $val ) {
			if ( false !== stripos( $slot, $day ) ) {
				$offset = $val;
				break;
			}
		}
		if ( 0 === $offset ) {
			return $week_start;
		}
		try {
			return ( new DateTimeImmutable( $week_start ) )->modify( '+' . $offset . ' days' )->format( 'Y-m-d' );
		} catch ( Exception ) {
			return null;
		}
	}

	/**
	 * @param array<int, Schedule_Division_DTO> $divisions
	 * @param int                               $team_id
	 * @return int|null
	 */
	private function find_division_id_by_team_id( array $divisions, int $team_id ): ?int {
		foreach ( $divisions as $id => $division ) {
			if ( null !== $division->find_team( $team_id ) ) {
				return $id;
			}
		}
		return null;
	}

	/**
	 * @param array<int, Schedule_Division_DTO> $divisions
	 * @return array<int, array{league_id: int, league_name: string, weeks: array}>
	 */
	private function empty_schedule( array $divisions ): array {
		$schedule = [];
		foreach ( $divisions as $div_id => $division ) {
			$schedule[ $div_id ] = [
				'league_id'   => $div_id,
				'league_name' => $division->league_name,
				'weeks'       => [],
			];
		}
		return $schedule;
	}

	/**
	 * @param array<int, Schedule_Division_DTO> $divisions
	 * @return array<int, int>
	 */
	private function zero_home_counts( array $divisions ): array {
		$counts = [];
		foreach ( $divisions as $division ) {
			foreach ( $division->teams as $team ) {
				$counts[ $team->id ] = 0;
			}
		}
		return $counts;
	}

	private function meeting_key( int $a, int $b ): string {
		return min( $a, $b ) . '_' . max( $a, $b );
	}

	private function timed_out( float $start_time ): bool {
		return ( ( $this->clock )() - $start_time ) > $this->timeout;
	}

	/**
	 * @param array $items Shuffled in place via the injected closure.
	 */
	private function shuffle( array &$items ): void {
		( $this->shuffle_fn )( $items );
	}
}
