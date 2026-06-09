<?php

namespace Racketmanager\admin;

class Admin_Competition_Scheduler {
	private int $teams_per_division;
	private int $total_weeks;
	private bool $is_home_away;
	private array $match_dates;
	private array $team_resources = [];
	private array $resource_pairings = [];
	private float $timeout = 60.0;
	private int $max_retries = 375550;

	public function __construct( $teams_per_division = 6, $is_home_away = false, $match_dates = [] ) {
		$this->teams_per_division = (int) $teams_per_division;
		$this->is_home_away       = (bool) $is_home_away;
		$this->match_dates        = (array) $match_dates;
		$this->total_weeks        = $this->is_home_away ? ( $this->teams_per_division - 1 ) * 2 : $this->teams_per_division - 1;
	}

	public function build_competition_schedule( $divisions ): array {
		$start_time              = microtime( true );
		$this->team_resources    = [];
		$this->resource_pairings = [];

		// Ensure the division array is not empty
		if ( empty( $divisions ) ) {
			return [
				'retry'     => 0,
				'schedule'  => [],
				'conflicts' => [],
				'alerts'    => [],
				'is_valid'  => true
			];
		}

		// 1. GLOBAL RESOURCE MAPPING
		$this->map_physical_courts( $divisions );
		$ranked_divisions = $this->rank_divisions( $divisions );
		$rotation         = $this->get_circle_rotation( $this->teams_per_division );
		$half_weeks       = $this->teams_per_division - 1;

		$best_schedule    = [];
		$best_home_counts = [];
		foreach ( $divisions as $div_id => $div_data ) {
			$best_schedule[ $div_id ] = [
				'league_id'   => $div_id,
				'league_name' => $div_data['league_name'],
				'weeks'       => []
			];
			foreach ( $div_data['teams'] as $team ) {
				$best_home_counts[ $team['id'] ] = 0;
			}
		}
		$best_conflicts = null; // Use null to indicate no best yet
		$best_valid     = false;

		// 2. RETRY LOOP FOR SEED ASSIGNMENT
		for ( $retry = 0; $retry < $this->max_retries; $retry ++ ) {
			$division_seeds            = [];
			$alerts                    = [];
			$court_usage               = [];
			$current_retry_home_counts = [];

			// Initialize home counts for all teams to 0 for this retry
			foreach ( $divisions as $div_id => $div_data ) {
				foreach ( $div_data['teams'] as $team ) {
					$current_retry_home_counts[ $team['id'] ] = 0;
				}
			}

			// 3. GENERATE SCHEDULE FROM SEEDS
			$current_schedule = [];
			foreach ( $divisions as $div_id => $div_data ) {
				$current_schedule[ $div_id ] = [
					'league_id'   => $div_id,
					'league_name' => $div_data['league_name'],
					'weeks'       => []
				];
			}

			// 2.0 Shuffle resource pairings to change allocation order on each retry
			$shuffled_resources = $this->resource_pairings;
			// Use uksort with random to shuffle keys while maintaining associations if needed, 
			// but we just need to iterate in random order.
			$res_keys = array_keys( $shuffled_resources );
			shuffle( $res_keys );

			// 2.1 INITIAL SEEDING FOR SHARED RESOURCES
			foreach ( $res_keys as $res_id ) {
				$teams = $shuffled_resources[ $res_id ];
				if ( count( $teams ) < 2 ) {
					continue;
				}

				// Randomly decide which team is T1 and which is T2
				if ( rand( 0, 1 ) ) {
					$t1_info = $teams[0];
					$t2_info = $teams[1];
				} else {
					$t1_info = $teams[1];
					$t2_info = $teams[0];
				}

				$div1_id = $this->find_division_id_by_team_id( $divisions, $t1_info['id'] );
				$div2_id = $this->find_division_id_by_team_id( $divisions, $t2_info['id'] );

				if ( $div1_id && $div2_id ) {
					$n    = $this->teams_per_division;
					$half = $n / 2;

					// Shuffle seeds to try different combinations on retry
					$seeds_to_try = range( 1, $half );
					shuffle( $seeds_to_try );

					foreach ( $seeds_to_try as $x ) {
						$seed_t1 = $x;
						$seed_t2 = $x + $half;

						if ( ! isset( $division_seeds[ $div1_id ][ $seed_t1 ] ) &&
						     ! isset( $division_seeds[ $div2_id ][ $seed_t2 ] ) ) {

							$division_seeds[ $div1_id ][ $seed_t1 ] = $this->find_team_by_id( $divisions[ $div1_id ]['teams'], $t1_info['id'] );
							$division_seeds[ $div2_id ][ $seed_t2 ] = $this->find_team_by_id( $divisions[ $div2_id ]['teams'], $t2_info['id'] );
							break;
						}
					}
				}
			}

			// 2.2 FINALIZE REMAINING SEEDS FOR EACH DIVISION
			$failed_to_seed = false;
			$div_ids        = array_keys( $ranked_divisions );
			shuffle( $div_ids );

			foreach ( $div_ids as $div_id ) {
				$div_data                  = $ranked_divisions[ $div_id ];
				$division_seeds[ $div_id ] = $this->finalize_seeds( $div_data['teams'], $this->teams_per_division, $division_seeds[ $div_id ] ?? [] );
				if ( count( $division_seeds[ $div_id ] ) < count( $div_data['teams'] ) ) {
					$failed_to_seed = true;
					// error_log( "RM_DEBUG: Retry $retry: Failed to seed division $div_id (" . $div_data['league_name'] . ")" );
					break;
				}
			}

			if ( $failed_to_seed ) {
				continue;
			}

			// 2.3 DERBY SEEDING (Same club in same division)
			// Actually, finalize_seeds already handles derbies now. 
			// But wait, the previous solution said it refactored it to be prioritized.
			// Let's check finalize_seeds.

			$conflicts = [];

			for ( $week = 1; $week <= $this->total_weeks; $week ++ ) {
				$week_in_rotation = ( ( $week - 1 ) % $half_weeks );
				$is_second_half   = $week > $half_weeks;

				foreach ( $divisions as $div_id => $div_data ) {
					$seeds = $division_seeds[ $div_id ] ?? [];
					if ( empty( $seeds ) ) {
						continue;
					}
					foreach ( $rotation[ $week_in_rotation ] as $pair ) {
						$p1 = $pair[0];
						$p2 = $pair[1];

						if ( $is_second_half ) {
							$temp = $p1;
							$p1   = $p2;
							$p2   = $temp;
						}

						$t1 = $seeds[ $p1 ] ?? null;
						$t2 = $seeds[ $p2 ] ?? null;

						if ( ! $t1 || ! $t2 ) {
							$real = $t1 ? : $t2;
							if ( $real ) {
								$current_schedule[ $div_id ]['weeks'][ $week ][] = [
									'team'    => $real['name'],
									'team_id' => $real['id'],
									'is_bye'  => true,
									'date'    => $this->match_dates[ $week - 1 ] ?? null
								];
							}
							continue;
						}

						// Determine Home/Away (Deterministic based on Seeds)
						$home = $t1;
						$away = $t2;
						$res  = $this->team_resources[ $home['id'] ];

						// Check for Court Conflict
						if ( isset( $court_usage[ $res ][ $week ] ) ) {
							$conflicts[] = [
								'week'     => $week,
								'division' => $div_data['league_name'],
								'court'    => $res,
								'home'     => $home['name'],
								'with'     => $court_usage[ $res ][ $week ]
							];
						}

						$court_usage[ $res ][ $week ]                    = $home['name'];
						$current_retry_home_counts[ $home['id'] ]        = ( $current_retry_home_counts[ $home['id'] ] ?? 0 ) + 1;
						$current_schedule[ $div_id ]['weeks'][ $week ][] = [
							'home'     => $home['name'],
							'home_id'  => $home['id'],
							'away'     => $away['name'],
							'away_id'  => $away['id'],
							'slot'     => $home['preferred_slot'],
							'time'     => $home['match_time'],
							'location' => $home['location'],
							'resource' => $res,
							'is_bye'   => false,
							'date'     => $home['match_day_date'] ?? $this->calculate_match_date( $week, $home['match_day'] )
						];
					}
				}
			}

			// 4. SORT AND STORE BEST SO FAR
			foreach ( $current_schedule as $sort_div_id => &$sort_div_data ) {
				ksort( $sort_div_data['weeks'] );
				foreach ( $sort_div_data['weeks'] as $sort_week => &$sort_matches ) {
					usort( $sort_matches, function ( $a, $b ) {
						$d1 = $a['date'] ?? '9999-99-99';
						$d2 = $b['date'] ?? '9999-99-99';

						return strcmp( $d1, $d2 );
					} );
				}
				unset( $sort_matches );
			}
			unset( $sort_div_data );

			if ( empty( $conflicts ) ) {
				$best_schedule    = $current_schedule;
				$best_conflicts   = [];
				$best_home_counts = $current_retry_home_counts;
				$best_valid       = true;
				break; // Perfect schedule found!
			}

			if ( $best_conflicts === null || count( $conflicts ) <= count( $best_conflicts ) ) {
				$best_schedule    = $current_schedule;
				$best_conflicts   = $conflicts;
				$best_home_counts = $current_retry_home_counts;
			}

			if ( ( microtime( true ) - $start_time ) > $this->timeout ) {
				break;
			}
		}

		if ( $best_conflicts === null ) {
			$best_conflicts = [];
		}

		ksort( $best_schedule );
		foreach ( $best_schedule as $final_id => $final_data_to_sort ) {
			if ( ! isset( $final_data_to_sort['weeks'] ) ) {
				$best_schedule[ $final_id ]['weeks'] = [];
			}
			ksort( $best_schedule[ $final_id ]['weeks'] );
			foreach ( $best_schedule[ $final_id ]['weeks'] as $week_num => &$matches_list ) {
				usort( $matches_list, function ( $a, $b ) {
					$d1 = $a['date'] ?? '9999-99-99';
					$d2 = $b['date'] ?? '9999-99-99';

					return strcmp( $d1, $d2 );
				} );
			}
			unset( $matches_list );
		}

		// 5. GENERATE BYE-REPLACEMENT MATCHES FOR UNDER-POPULATED DIVISIONS
		$under_populated_div_ids = [];
		$meeting_counts          = []; // Track meetings between teams (T1_ID_T2_ID => [T1_home_count, T2_home_count])

		// 5.1 Initialize meeting counts from regular schedule
		foreach ( $best_schedule as $meeting_div_id => $meeting_div_data ) {
			foreach ( $meeting_div_data['weeks'] as $meeting_week => $meeting_matches ) {
				foreach ( $meeting_matches as $meeting_match ) {
					if ( empty( $meeting_match['is_bye'] ) && ! empty( $meeting_match['home_id'] ) && ! empty( $meeting_match['away_id'] ) ) {
						$t1_id = $meeting_match['home_id'];
						$t2_id = $meeting_match['away_id'];
						$ids   = [ $t1_id, $t2_id ];
						sort( $ids );
						$meeting_key = $ids[0] . '_' . $ids[1];
						if ( ! isset( $meeting_counts[ $meeting_key ] ) ) {
							$meeting_counts[ $meeting_key ] = [
								$ids[0] => 0,
								$ids[1] => 0
							];
						}
						// Increment home count for the actual home team in this pair
						$meeting_counts[ $meeting_key ][ $t1_id ] ++;
					}
				}
			}
		}

		// 5.2 Identify under-populated divisions
		$under_populated_div_ids = [];
		foreach ( $divisions as $div_id_check => $div_data_check ) {
			if ( count( $div_data_check['teams'] ) <= ( $this->teams_per_division - 1 ) ) {
				$under_populated_div_ids[] = $div_id_check;
			}
		}

		if ( ! empty( $under_populated_div_ids ) ) {
			for ( $week = 1; $week <= $this->total_weeks; $week ++ ) {
				$teams_with_byes = [];
				
				// Identify which teams actually HAVE a bye this week and aren't already playing
				$already_playing_team_ids = [];
				foreach ( $best_schedule as $s_div_id => $s_div_data ) {
					if ( isset( $s_div_data['weeks'][ $week ] ) ) {
						foreach ( $s_div_data['weeks'][ $week ] as $m ) {
							if ( empty( $m['is_bye'] ) ) {
								if ( ! empty( $m['home_id'] ) ) {
									$already_playing_team_ids[ $m['home_id'] ] = true;
								}
								if ( ! empty( $m['away_id'] ) ) {
									$already_playing_team_ids[ $m['away_id'] ] = true;
								}
							}
						}
					}
				}

				foreach ( $under_populated_div_ids as $under_div_id ) {
					if ( ! isset( $best_schedule[ $under_div_id ]['weeks'][ $week ] ) ) {
						continue;
					}
					foreach ( $best_schedule[ $under_div_id ]['weeks'][ $week ] as $idx => $match ) {
						if ( ! empty( $match['is_bye'] ) && ! empty( $match['team_id'] ) ) {
							$tid = $match['team_id'];
							// ONLY if they aren't already scheduled for a real match this week
							if ( ! isset( $already_playing_team_ids[ $tid ] ) ) {
								$teams_with_byes[] = [
									'div_id'    => $under_div_id,
									'match_idx' => $idx,
									'team_id'   => $tid,
									'team_name' => $match['team'],
									'team_data' => $this->find_team_by_id( $divisions[ $under_div_id ]['teams'], $tid )
								];
							}
						}
					}
				}

				// Pair up the teams with byes
				shuffle( $teams_with_byes );
				$to_process = $teams_with_byes;
				while ( count( $to_process ) >= 2 ) {
					$t1_info = array_shift( $to_process );
					$found   = false;
					foreach ( $to_process as $i => $t2_info ) {
						// CRITICAL: ONLY pair if they are from the SAME division
						if ( $t1_info['div_id'] !== $t2_info['div_id'] ) {
							continue;
						}

						$home_team = $t1_info['team_data'];
						$away_team = $t2_info['team_data'];

						// Ensure they haven't met more than 3 times, and home counts follow 2+1/1+2 logic
						$ids = [ $home_team['id'], $away_team['id'] ];
						sort( $ids );
						$meeting_key = $ids[0] . '_' . $ids[1];

						$current_pair_counts = $meeting_counts[ $meeting_key ] ?? [
							$ids[0] => 0,
							$ids[1] => 0
						];

						$total_meetings = array_sum( $current_pair_counts );

						if ( $total_meetings < 3 && $current_pair_counts[ $home_team['id'] ] < 2 ) {
							array_splice( $to_process, $i, 1 );
							$found = true;

							$home = $home_team;
							$away = $away_team;

							$new_match = [
								'home'              => $home['name'],
								'home_id'           => $home['id'],
								'away'              => $away['name'],
								'away_id'           => $away['id'],
								'slot'              => $home['preferred_slot'],
								'time'              => $home['match_time'],
								'location'          => 'Extra Match',
								'resource'          => $this->team_resources[ $home['id'] ] ?? 'N/A',
								'is_bye'            => false,
								'is_cross_division' => true,
								'date'              => $home['match_day_date'] ?? $this->calculate_match_date( $week, $home['match_day'] )
							];

							// Replace byes in both divisions
							$best_schedule[ $t1_info['div_id'] ]['weeks'][ $week ][ $t1_info['match_idx'] ] = $new_match;
							$best_schedule[ $t2_info['div_id'] ]['weeks'][ $week ][ $t2_info['match_idx'] ] = $new_match;

							// Update home counts and meeting counts
							$best_home_counts[ $home['id'] ] = ( $best_home_counts[ $home['id'] ] ?? 0 ) + 1;
							
							if ( ! isset( $meeting_counts[ $meeting_key ] ) ) {
								$meeting_counts[ $meeting_key ] = [
									$ids[0] => 0,
									$ids[1] => 0
								];
							}
							$meeting_counts[ $meeting_key ][ $home['id'] ] ++;
							
							// Mark both as playing so we don't pick them again for this week
							$already_playing_team_ids[ $home['id'] ] = true;
							$already_playing_team_ids[ $away['id'] ] = true;
							break;
						}
					}
				}
			}
		}

		// 6. EQUITY COMPROMISE DETECTION (Post-check)
		foreach ( $divisions as $check_div_id => $check_div_data ) {
			foreach ( $check_div_data['teams'] as $check_team ) {
				$count            = $best_home_counts[ $check_team['id'] ] ?? 0;
				$max_allowed_home = count( $check_div_data['teams'] ) - 1;
				if ( $count > $max_allowed_home ) {
					$alerts[] = [
						'type'      => 'equity_compromise',
						'team_name' => $check_team['name'],
						'division'  => $check_div_data['league_name'],
						'count'     => $count,
						'ideal'     => $this->total_weeks / 2
					];
				}
			}
		}

			return [
				'retry'             => $retry + 1,
				'schedule'          => $best_schedule,
				'conflicts'         => $best_conflicts,
				'alerts'            => $alerts ?? [],
				'resource_pairings' => $this->resource_pairings,
				'is_valid'          => $best_valid,
				'total_weeks'       => $this->total_weeks
			];
	}

	private function map_physical_courts( $all_divisions ): void {
		$grouped_teams = [];
		foreach ( $all_divisions as $div_id => $div_data ) {
			foreach ( $div_data['teams'] as $team ) {
				$key                     = $team['club_id'] . '_' . $team['preferred_slot'];
				$grouped_teams[ $key ][] = [
					'team'     => $team,
					'div_name' => $div_data['league_name']
				];
			}
		}

		foreach ( $grouped_teams as $key => $entries ) {
			// Sort teams within each slot by event_id to encourage interleaving of genders
			// Interleaving logic: if we have Mens (E1), Mens (E1), Ladies (E2), Ladies (E2)
			// Sorted: E1, E1, E2, E2 -> Pairs: (E1, E1), (E2, E2) - BAD
			// We want: E1, E2, E1, E2 -> Pairs: (E1, E2), (E1, E2) - GOOD
			$by_event = [];
			foreach ( $entries as $entry ) {
				$e_id                = $entry['team']['event_id'] ?? 0;
				$by_event[ $e_id ][] = $entry;
			}

			$interleaved = [];
			$max_count   = max( array_map( 'count', $by_event ) );
			for ( $i = 0; $i < $max_count; $i ++ ) {
				foreach ( $by_event as $e_id => $event_teams ) {
					if ( isset( $event_teams[ $i ] ) ) {
						$interleaved[] = $event_teams[ $i ];
					}
				}
			}

			foreach ( $interleaved as $idx => $entry ) {
				$team                                = $entry['team'];
				$res                                 = $key . "_C" . (int) floor( $idx / 2 );
				$this->team_resources[ $team['id'] ] = $res;
				$this->resource_pairings[ $res ][]   = [
					'id'       => $team['id'],
					'name'     => $team['name'],
					'division' => $entry['div_name']
				];
			}
		}
	}

	private function rank_divisions( $divisions ): array {
		$club_demand = [];
		foreach ( $divisions as $div_data ) {
			foreach ( $div_data['teams'] as $t ) {
				$club_demand[ $t['club_id'] ] = ( $club_demand[ $t['club_id'] ] ?? 0 ) + 1;
			}
		}
		$scores = [];
		foreach ( $divisions as $id => $div_data ) {
			$s = 0;
			foreach ( $div_data['teams'] as $t ) {
				$s += $club_demand[ $t['club_id'] ];
			}
			$scores[ $id ] = $s;
		}
		arsort( $scores );
		$ordered = [];
		foreach ( $scores as $id => $v ) {
			$ordered[ $id ] = $divisions[ $id ];
		}

		return $ordered;
	}

	private function get_circle_rotation( $n ): array {
		if ( $n % 2 !== 0 ) {
			$n ++;
		}
		$teams_range = range( 1, $n );
		$half        = (int) ( $n / 2 );
		$indexes     = $teams_range;
		$rounds      = [];

		for ( $round_num = 1; $round_num < $n; $round_num ++ ) {
			$fixtures = [];
			$start    = 0;

			// In even round the highest index is home
			if ( $round_num % 2 === 0 ) {
				$pos        = $indexes[ $n - 1 ] - 1;
				$home       = $teams_range[ $pos ];
				$pos        = $indexes[0] - 1;
				$away       = $teams_range[ $pos ];
				$fixtures[] = [ $home, $away ];
				++ $start;
			}
			for ( $i = $start; $i < $half; $i ++ ) {
				$pos        = $indexes[ $i ] - 1;
				$home       = $teams_range[ $pos ];
				$pos        = $indexes[ $n - 1 - $i ] - 1;
				$away       = $teams_range[ $pos ];
				$fixtures[] = [ $home, $away ];
			}
			$rounds[] = $fixtures;

			// Prepare next round's indexes (as in Schedule_Round_Robin)
			// Remove and save the constant index (which is at the end of the list in this implementation)
			$constant = array_splice( $indexes, $n - 1, 1 )[0];
			// Move the first half of the list to the end of it
			$first_half = array_splice( $indexes, 0, $half );
			$indexes    = array_merge( $indexes, $first_half );
			// Add the constant index
			$indexes[] = $constant;
		}

		return $rounds;
	}

	private function find_division_id_by_team_id( $divisions, $team_id ): int|string|null {
		foreach ( $divisions as $id => $data ) {
			foreach ( $data['teams'] as $team ) {
				if ( $team['id'] == $team_id ) {
					return $id;
				}
			}
		}

		return null;
	}

	private function find_team_by_id( $teams, $team_id ): mixed {
		foreach ( $teams as $team ) {
			if ( $team['id'] == $team_id ) {
				return $team;
			}
		}

		return null;
	}

	private function finalize_seeds( $teams, $n, $existing_seeds = [] ): array {
		$seeds        = $existing_seeds;
		$club_ids     = array_column( $teams, 'club_id' );
		$club_counts  = array_count_values( $club_ids );
		$assigned_ids = [];

		foreach ( $seeds as $t ) {
			$assigned_ids[ $t['id'] ] = true;
		}

		// 1. Handle Derby (Teams from same club in division)
		$derby_club_ids = array_keys( $club_counts, 2 );
		if ( ! empty( $derby_club_ids ) ) {
			shuffle( $derby_club_ids );
			foreach ( $derby_club_ids as $derby_club_id ) {
				$derby_teams = [];
				foreach ( $teams as $t ) {
					if ( $t['club_id'] == $derby_club_id ) {
						$derby_teams[] = $t;
					}
				}
				shuffle( $derby_teams );

				$d1_seed = null;
				$d2_seed = null;
				foreach ( $seeds as $seed => $t ) {
					if ( $t['id'] == $derby_teams[0]['id'] ) {
						$d1_seed = $seed;
					}
					if ( $t['id'] == $derby_teams[1]['id'] ) {
						$d2_seed = $seed;
					}
				}

				if ( ! $d1_seed && ! $d2_seed ) {
					// Preferred seeds for Round 1 match: (1, N)
					if ( ! isset( $seeds[1] ) && ! isset( $seeds[ $n ] ) ) {
						$seeds[1]                              = $derby_teams[0];
						$seeds[ $n ]                           = $derby_teams[1];
						$assigned_ids[ $derby_teams[0]['id'] ] = true;
						$assigned_ids[ $derby_teams[1]['id'] ] = true;
					} else {
						// Try to find ANY pair that meets in Week 1 if 1/N is taken
						$found_any   = false;
						$week1_pairs = $this->get_circle_rotation( $n )[0];
						shuffle( $week1_pairs );
						foreach ( $week1_pairs as $pair ) {
							if ( ! isset( $seeds[ $pair[0] ] ) && ! isset( $seeds[ $pair[1] ] ) ) {
								$seeds[ $pair[0] ]                     = $derby_teams[0];
								$seeds[ $pair[1] ]                     = $derby_teams[1];
								$assigned_ids[ $derby_teams[0]['id'] ] = true;
								$assigned_ids[ $derby_teams[1]['id'] ] = true;
								$found_any                             = true;
								break;
							}
						}
						if ( ! $found_any ) {
							return [];
						}
					}
				} // If only one is assigned, the other MUST be the Round 1 opponent
				elseif ( $d1_seed && ! $d2_seed ) {
					$target = $this->get_week1_opponent( $d1_seed, $n );
					if ( $target && ! isset( $seeds[ $target ] ) ) {
						$seeds[ $target ]                      = $derby_teams[1];
						$assigned_ids[ $derby_teams[1]['id'] ] = true;
					} else {
						return []; // Failure to find valid seed for derby
					}
				} elseif ( ! $d1_seed && $d2_seed ) {
					$target = $this->get_week1_opponent( $d2_seed, $n );
					if ( $target && ! isset( $seeds[ $target ] ) ) {
						$seeds[ $target ]                      = $derby_teams[0];
						$assigned_ids[ $derby_teams[0]['id'] ] = true;
					} else {
						return []; // Failure
					}
				}
			}
		}

		// 2. Complete remaining seeds randomly
		$unassigned_teams = [];
		foreach ( $teams as $t ) {
			if ( ! isset( $assigned_ids[ $t['id'] ] ) ) {
				$unassigned_teams[] = $t;
			}
		}
		shuffle( $unassigned_teams );

		$available_seeds = [];
		for ( $i = 1; $i <= count( $teams ); $i ++ ) {
			if ( ! isset( $seeds[ $i ] ) ) {
				$available_seeds[] = $i;
			}
		}

		if ( count( $unassigned_teams ) > count( $available_seeds ) ) {
			return []; // Impossible
		}

		foreach ( $unassigned_teams as $idx => $team ) {
			$seeds[ $available_seeds[ $idx ] ] = $team;
		}

		return $seeds;
	}

	private function get_week1_opponent( int $seed, int $n ): ?int {
		$rotation = $this->get_circle_rotation( $n );
		$week1    = $rotation[0];
		foreach ( $week1 as $pair ) {
			if ( $pair[0] == $seed ) {
				return $pair[1];
			}
			if ( $pair[1] == $seed ) {
				return $pair[0];
			}
		}

		return null;
	}


	private function calculate_match_date( int $week, string $slot ): ?string {
		$week_start = $this->match_dates[ $week - 1 ] ?? null;
		if ( ! $week_start ) {
			return null;
		}

		$days = [
			'Monday'    => 0,
			'Tuesday'   => 1,
			'Wednesday' => 2,
			'Thursday'  => 3,
			'Friday'    => 4,
			'Saturday'  => 5,
			'Sunday'    => 6,
		];

		$offset = 0;
		foreach ( $days as $day => $val ) {
			if ( stripos( $slot, $day ) !== false ) {
				$offset = $val;
				break;
			}
		}

		if ( $offset === 0 ) {
			return $week_start;
		}

		return date( 'Y-m-d', strtotime( "$week_start +$offset days" ) );
	}

}