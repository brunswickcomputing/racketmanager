<?php

namespace Racketmanager\admin;

class Admin_Competition_Scheduler {
	private int $teams_per_league;
	private int $total_weeks;
	private bool $is_home_away;
	private array $match_dates;
	private array $team_resources = [];
	private array $resource_pairings = [];
	private float $timeout = 60.0;
	private int $max_retries = 375550;

	public function __construct( $teams_per_league = 6, $is_home_away = false, $match_dates = [] ) {
		$this->teams_per_league = (int) $teams_per_league;
		$this->is_home_away     = (bool) $is_home_away;
		$this->match_dates      = (array) $match_dates;
		$this->total_weeks      = $this->is_home_away ? ( $this->teams_per_league - 1 ) * 2 : $this->teams_per_league - 1;
	}

	public function build_competition_schedule( $leagues ): array {
		$start_time              = microtime( true );
		$this->team_resources    = [];
		$this->resource_pairings = [];

		// Ensure the league array is not empty
		if ( empty( $leagues ) ) {
			return [
				'retry'             => 0,
				'schedule'          => [],
				'conflicts'         => [],
				'alerts'            => [],
				'resource_pairings' => [],
				'is_valid'          => true,
				'total_weeks'       => $this->total_weeks,
			];
		}

		// Early validation: detect duplicate team IDs within any league
		foreach ( $leagues as $league_id => $league_data ) {
			$seen_team_ids = [];
			foreach ( $league_data['teams'] as $team ) {
				$tid = $team['id'];
				if ( isset( $seen_team_ids[ $tid ] ) ) {
					return [
						'retry'             => 0,
						'schedule'          => [],
						'conflicts'         => [],
						'alerts'            => [
							[
								'type'      => 'duplicate_team',
								'team_id'   => $team['id'],
								'league'    => $league_data['league_name'],
								'message'   => "League '{$league_data['league_name']}' contains duplicate team ID '{$team['id']}'",
							],
						],
						'resource_pairings' => [],
						'is_valid'          => false,
						'total_weeks'       => $this->total_weeks,
					];
				}
				$seen_team_ids[ $tid ] = true;
			}
		}

		// 1. GLOBAL RESOURCE MAPPING
		$this->map_physical_courts( $leagues );
		$ranked_leagues = $this->rank_leagues( $leagues );
		$rotation       = $this->get_circle_rotation( $this->teams_per_league );
		$half_weeks     = $this->teams_per_league - 1;

		$best_schedule    = [];
		$best_home_counts = [];
		foreach ( $leagues as $league_id => $league_data ) {
			$best_schedule[ $league_id ] = [
				'league_id'   => $league_id,
				'league_name' => $league_data['league_name'],
				'weeks'       => []
			];
			foreach ( $league_data['teams'] as $team ) {
				$best_home_counts[ $league_id ][ $team['id'] ] = 0;
			}
		}
		$best_conflicts = null; // Use null to indicate no best yet
		$best_valid     = false;

		// 2. RETRY LOOP FOR SEED ASSIGNMENT
		for ( $retry = 0; $retry < $this->max_retries; $retry ++ ) {
			$league_seeds              = [];
			$alerts                    = [];
			$court_usage               = [];
			$current_retry_home_counts = [];

			// Initialize home counts for all teams to 0 for this retry
			foreach ( $leagues as $league_id => $league_data ) {
				foreach ( $league_data['teams'] as $team ) {
					$current_retry_home_counts[ $league_id ][ $team['id'] ] = 0;
				}
			}

			// 3. GENERATE SCHEDULE FROM SEEDS
			$current_schedule = [];
			foreach ( $leagues as $league_id => $league_data ) {
				$current_schedule[ $league_id ] = [
					'league_id'   => $league_id,
					'league_name' => $league_data['league_name'],
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

				$l1_id = $t1_info['league_id'] ?? null;
				$l2_id = $t2_info['league_id'] ?? null;

				if ( null !== $l1_id && null !== $l2_id ) {
					// Guard against duplicate seed assignments for identical team IDs within the same league
					if ( $l1_id === $l2_id && $t1_info['id'] === $t2_info['id'] ) {
						continue;
					}

					// Ensure teams aren't already seeded in their respective leagues
					$already_seeded_t1 = in_array( $t1_info['id'], array_column( $league_seeds[ $l1_id ] ?? [], 'id' ) );
					$already_seeded_t2 = in_array( $t2_info['id'], array_column( $league_seeds[ $l2_id ] ?? [], 'id' ) );
					if ( $already_seeded_t1 || $already_seeded_t2 ) {
						continue;
					}

					$team1 = $this->find_team_by_id( $leagues[ $l1_id ]['teams'], $t1_info['id'] );
					$team2 = $this->find_team_by_id( $leagues[ $l2_id ]['teams'], $t2_info['id'] );
					if ( ! $team1 || ! $team2 ) {
						continue;
					}

					$n    = $this->teams_per_league;
					$half = $n / 2;

					// Shuffle seeds to try different combinations on retry
					$seeds_to_try = range( 1, $half );
					shuffle( $seeds_to_try );

					foreach ( $seeds_to_try as $x ) {
						$seed_t1 = $x;
						$seed_t2 = $x + $half;

						if ( ! isset( $league_seeds[ $l1_id ][ $seed_t1 ] ) &&
						     ! isset( $league_seeds[ $l2_id ][ $seed_t2 ] ) ) {

							$league_seeds[ $l1_id ][ $seed_t1 ] = $team1;
							$league_seeds[ $l2_id ][ $seed_t2 ] = $team2;
							break;
						}
					}
				}
			}

			// 2.2 FINALIZE REMAINING SEEDS FOR EACH LEAGUE
			$failed_to_seed = false;
			$league_ids     = array_keys( $ranked_leagues );
			shuffle( $league_ids );

			foreach ( $league_ids as $league_id ) {
				$league_data                 = $ranked_leagues[ $league_id ];
				$league_seeds[ $league_id ] = $this->finalize_seeds( $league_data['teams'], $this->teams_per_league, $league_seeds[ $league_id ] ?? [] );
				if ( count( $league_seeds[ $league_id ] ) < count( $league_data['teams'] ) ) {
					$failed_to_seed = true;
					// error_log( "RM_DEBUG: Retry $retry: Failed to seed league $league_id (" . $league_data['league_name'] . ")" );
					break;
				}
			}

			if ( $failed_to_seed ) {
				continue;
			}

			// 2.3 DERBY SEEDING (Same club in same league)
			// Actually, finalize_seeds already handles derbies now.

			$conflicts = [];

			for ( $week = 1; $week <= $this->total_weeks; $week ++ ) {
				$week_in_rotation = ( ( $week - 1 ) % $half_weeks );
				$is_second_half   = $week > $half_weeks;

				foreach ( $leagues as $league_id => $league_data ) {
					$seeds = $league_seeds[ $league_id ] ?? [];
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
								$current_schedule[ $league_id ]['weeks'][ $week ][] = [
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
						$res  = $this->team_resources[ $league_id ][ $home['id'] ] ?? ( is_string( $this->team_resources[ $home['id'] ] ?? null ) ? $this->team_resources[ $home['id'] ] : 'N/A' );

						// Check for Court Conflict
						if ( isset( $court_usage[ $res ][ $week ] ) ) {
							$prev          = $court_usage[ $res ][ $week ];
							$prev_name     = is_array( $prev ) ? $prev['name'] : $prev;
							$prev_league   = is_array( $prev ) ? ( $prev['league'] ?? '' ) : '';
							$conflicts[]   = [
								'week'        => $week,
								'league'      => $league_data['league_name'],
								'court'       => $res,
								'home'        => $home['name'],
								'with'        => $prev_name,
								'with_league' => $prev_league,
							];
						} else {
							$court_usage[ $res ][ $week ] = [
								'name'   => $home['name'],
								'league' => $league_data['league_name'],
							];
						}

						$current_retry_home_counts[ $league_id ][ $home['id'] ] = ( $current_retry_home_counts[ $league_id ][ $home['id'] ] ?? 0 ) + 1;
						$current_schedule[ $league_id ]['weeks'][ $week ][] = [
							'home'     => $home['name'],
							'home_id'  => $home['id'],
							'away'     => $away['name'],
							'away_id'  => $away['id'],
							'slot'     => $home['preferred_slot'],
							'time'     => $home['match_time'],
							'location' => $home['location'] ?? '',
							'resource' => $res,
							'is_bye'   => false,
							'date'     => $home['match_day_date'] ?? $this->calculate_match_date( $week, $home['match_day'] )
						];
					}
				}
			}

			// 4. SORT AND STORE BEST SO FAR
			foreach ( $current_schedule as $sort_league_id => &$sort_league_data ) {
				ksort( $sort_league_data['weeks'] );
				foreach ( $sort_league_data['weeks'] as $sort_week => &$sort_matches ) {
					usort( $sort_matches, function ( $a, $b ) {
						$d1 = $a['date'] ?? '9999-99-99';
						$d2 = $b['date'] ?? '9999-99-99';

						return strcmp( $d1, $d2 );
					} );
				}
				unset( $sort_matches );
			}
			unset( $sort_league_data );

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

		// 5. GENERATE BYE-REPLACEMENT MATCHES FOR UNDER-POPULATED LEAGUES
		$meeting_counts = []; // Track meetings between teams scoped by league ([league_id][T1_ID_T2_ID] => [T1_home_count, T2_home_count])

		// 5.1 Initialize meeting counts from regular schedule scoped by league
		foreach ( $best_schedule as $meeting_league_id => $meeting_league_data ) {
			foreach ( $meeting_league_data['weeks'] as $meeting_week => $meeting_matches ) {
				foreach ( $meeting_matches as $meeting_match ) {
					if ( empty( $meeting_match['is_bye'] ) && ! empty( $meeting_match['home_id'] ) && ! empty( $meeting_match['away_id'] ) ) {
						$t1_id = $meeting_match['home_id'];
						$t2_id = $meeting_match['away_id'];
						$ids   = [ $t1_id, $t2_id ];
						sort( $ids );
						$meeting_key = $ids[0] . '_' . $ids[1];
						if ( ! isset( $meeting_counts[ $meeting_league_id ][ $meeting_key ] ) ) {
							$meeting_counts[ $meeting_league_id ][ $meeting_key ] = [
								$ids[0] => 0,
								$ids[1] => 0,
							];
						}
						// Increment home count for the actual home team in this pair
						$meeting_counts[ $meeting_league_id ][ $meeting_key ][ $t1_id ] ++;
					}
				}
			}
		}

		// 5.2 Identify under-populated leagues
		$under_populated_league_ids = [];
		foreach ( $leagues as $league_id_check => $league_data_check ) {
			if ( count( $league_data_check['teams'] ) <= ( $this->teams_per_league - 1 ) ) {
				$under_populated_league_ids[] = $league_id_check;
			}
		}

		if ( ! empty( $under_populated_league_ids ) ) {
			for ( $week = 1; $week <= $this->total_weeks; $week ++ ) {
				$teams_with_byes = [];

				// Identify teams already playing (scoped by league) and track occupied court resources & home teams
				$already_playing_team_ids     = [];
				$occupied_court_resources     = [];
				$teams_playing_home_this_week = [];

				foreach ( $best_schedule as $s_league_id => $s_league_data ) {
					if ( isset( $s_league_data['weeks'][ $week ] ) ) {
						foreach ( $s_league_data['weeks'][ $week ] as $m ) {
							if ( empty( $m['is_bye'] ) ) {
								if ( ! empty( $m['home_id'] ) ) {
									$already_playing_team_ids[ $s_league_id ][ $m['home_id'] ] = true;
									$teams_playing_home_this_week[ $m['home_id'] ]             = true;
								}
								if ( ! empty( $m['away_id'] ) ) {
									$already_playing_team_ids[ $s_league_id ][ $m['away_id'] ] = true;
								}
								if ( ! empty( $m['resource'] ) && $m['resource'] !== 'N/A' ) {
									$occupied_court_resources[ $m['resource'] ] = true;
								}
							}
						}
					}
				}

				foreach ( $under_populated_league_ids as $under_league_id ) {
					if ( ! isset( $best_schedule[ $under_league_id ]['weeks'][ $week ] ) ) {
						continue;
					}
					foreach ( $best_schedule[ $under_league_id ]['weeks'][ $week ] as $idx => $match ) {
						if ( ! empty( $match['is_bye'] ) && ! empty( $match['team_id'] ) ) {
							$tid = $match['team_id'];
							// ONLY if they aren't already scheduled for a real match this week in this league
							if ( ! isset( $already_playing_team_ids[ $under_league_id ][ $tid ] ) ) {
								$teams_with_byes[] = [
									'league_id' => $under_league_id,
									'match_idx' => $idx,
									'team_id'   => $tid,
									'team_name' => $match['team'],
									'team_data' => $this->find_team_by_id( $leagues[ $under_league_id ]['teams'], $tid )
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
					foreach ( $to_process as $i => $t2_info ) {
						// CRITICAL: ONLY pair if they are from the SAME league
						if ( $t1_info['league_id'] !== $t2_info['league_id'] ) {
							continue;
						}

						$league_id = $t1_info['league_id'];
						$team_a    = $t1_info['team_data'];
						$team_b    = $t2_info['team_data'];

						// Ensure they haven't met more than 3 times, and home counts follow 2+1/1+2 logic
						$ids = [ $team_a['id'], $team_b['id'] ];
						sort( $ids );
						$meeting_key = $ids[0] . '_' . $ids[1];

						$current_pair_counts = $meeting_counts[ $league_id ][ $meeting_key ] ?? [
							$ids[0] => 0,
							$ids[1] => 0,
						];

						$total_meetings = array_sum( $current_pair_counts );
						if ( $total_meetings >= 3 ) {
							continue;
						}

						$res_a = $this->team_resources[ $league_id ][ $team_a['id'] ] ?? ( is_string( $this->team_resources[ $team_a['id'] ] ?? null ) ? $this->team_resources[ $team_a['id'] ] : 'N/A' );
						$res_b = $this->team_resources[ $league_id ][ $team_b['id'] ] ?? ( is_string( $this->team_resources[ $team_b['id'] ] ?? null ) ? $this->team_resources[ $team_b['id'] ] : 'N/A' );

						// Determine hosting eligibility to prevent physically simultaneous home matches
						$can_a_host = ( $current_pair_counts[ $team_a['id'] ] < 2 )
							&& ( $res_a === 'N/A' || empty( $occupied_court_resources[ $res_a ] ) )
							&& empty( $teams_playing_home_this_week[ $team_a['id'] ] );

						$can_b_host = ( $current_pair_counts[ $team_b['id'] ] < 2 )
							&& ( $res_b === 'N/A' || empty( $occupied_court_resources[ $res_b ] ) )
							&& empty( $teams_playing_home_this_week[ $team_b['id'] ] );

						if ( ! $can_a_host && ! $can_b_host ) {
							continue;
						}

						if ( $can_a_host && $can_b_host ) {
							if ( $current_pair_counts[ $team_a['id'] ] <= $current_pair_counts[ $team_b['id'] ] ) {
								$home     = $team_a;
								$away     = $team_b;
								$home_res = $res_a;
							} else {
								$home     = $team_b;
								$away     = $team_a;
								$home_res = $res_b;
							}
						} elseif ( $can_a_host ) {
							$home     = $team_a;
							$away     = $team_b;
							$home_res = $res_a;
						} else {
							$home     = $team_b;
							$away     = $team_a;
							$home_res = $res_b;
						}

						array_splice( $to_process, $i, 1 );

						$new_match = [
							'home'            => $home['name'],
							'home_id'         => $home['id'],
							'away'            => $away['name'],
							'away_id'         => $away['id'],
							'slot'            => $home['preferred_slot'],
							'time'            => $home['match_time'],
							'location'        => ! empty( $home['location'] ) ? $home['location'] : 'Extra Match',
							'resource'        => $home_res,
							'is_bye'          => false,
							'is_cross_league' => false,
							'date'            => $home['match_day_date'] ?? $this->calculate_match_date( $week, $home['match_day'] )
						];

						// Replace byes in schedule: assign match to t1 index and remove t2 bye
						$best_schedule[ $league_id ]['weeks'][ $week ][ $t1_info['match_idx'] ] = $new_match;
						unset( $best_schedule[ $league_id ]['weeks'][ $week ][ $t2_info['match_idx'] ] );

						// Update home counts and meeting counts scoped by league
						$best_home_counts[ $league_id ][ $home['id'] ] = ( $best_home_counts[ $league_id ][ $home['id'] ] ?? 0 ) + 1;

						if ( ! isset( $meeting_counts[ $league_id ][ $meeting_key ] ) ) {
							$meeting_counts[ $league_id ][ $meeting_key ] = [
								$ids[0] => 0,
								$ids[1] => 0,
							];
						}
						$meeting_counts[ $league_id ][ $meeting_key ][ $home['id'] ] ++;

						// Mark both as playing in this league so we don't pick them again for this week
						$already_playing_team_ids[ $league_id ][ $home['id'] ] = true;
						$already_playing_team_ids[ $league_id ][ $away['id'] ] = true;

						// Mark court and home hosting as occupied to avoid simultaneous home matches
						if ( $home_res !== 'N/A' ) {
							$occupied_court_resources[ $home_res ] = true;
						}
						$teams_playing_home_this_week[ $home['id'] ] = true;
						break;
					}
				}
			}

			// Re-index weeks arrays in case bye elements were unset
			foreach ( $best_schedule as $reindex_league_id => &$reindex_league_data ) {
				if ( ! empty( $reindex_league_data['weeks'] ) ) {
					foreach ( $reindex_league_data['weeks'] as $reindex_week => &$reindex_matches ) {
						$reindex_matches = array_values( $reindex_matches );
					}
					unset( $reindex_matches );
				}
			}
			unset( $reindex_league_data );
		}

		// 6. EQUITY COMPROMISE DETECTION (Post-check)
		foreach ( $leagues as $check_league_id => $check_league_data ) {
			foreach ( $check_league_data['teams'] as $check_team ) {
				$count            = $best_home_counts[ $check_league_id ][ $check_team['id'] ] ?? 0;
				$max_allowed_home = count( $check_league_data['teams'] ) - 1;
				if ( $count > $max_allowed_home ) {
					$alerts[] = [
						'type'      => 'equity_compromise',
						'team_name' => $check_team['name'],
						'league'    => $check_league_data['league_name'],
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

	private function map_physical_courts( $all_leagues ): void {
		$grouped_teams = [];
		foreach ( $all_leagues as $league_id => $league_data ) {
			foreach ( $league_data['teams'] as $team ) {
				$key                     = $team['club_id'] . '_' . $team['preferred_slot'];
				$grouped_teams[ $key ][] = [
					'team'        => $team,
					'league_name' => $league_data['league_name'],
					'league_id'   => $league_id,
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
				$team                                                         = $entry['team'];
				$res                                                          = $key . "_C" . (int) floor( $idx / 2 );
				$this->team_resources[ $entry['league_id'] ][ $team['id'] ]   = $res;
				$this->resource_pairings[ $res ][]                            = [
					'id'        => $team['id'],
					'name'      => $team['name'],
					'league'    => $entry['league_name'],
					'league_id' => $entry['league_id'],
				];
			}
		}
	}

	private function rank_leagues( $leagues ): array {
		$club_demand = [];
		foreach ( $leagues as $league_data ) {
			foreach ( $league_data['teams'] as $t ) {
				$club_demand[ $t['club_id'] ] = ( $club_demand[ $t['club_id'] ] ?? 0 ) + 1;
			}
		}
		$scores = [];
		foreach ( $leagues as $id => $league_data ) {
			$s = 0;
			foreach ( $league_data['teams'] as $t ) {
				$s += $club_demand[ $t['club_id'] ];
			}
			$scores[ $id ] = $s;
		}
		arsort( $scores );
		$ordered = [];
		foreach ( $scores as $id => $v ) {
			$ordered[ $id ] = $leagues[ $id ];
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

		// 1. Handle Derby (Teams from same club in league)
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

	/**
	 * Format a scheduling conflict record for HTML display.
	 *
	 * @param array $error Conflict data containing week, home, league, with, with_league, and court.
	 * @return string HTML <li> element.
	 */
	public static function format_conflict_item( array $error ): string {
		$escape = function_exists( 'esc_html' )
			? 'esc_html'
			: static fn( $v ) => htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' );

		$week        = $escape( $error['week'] ?? '' );
		$home        = $escape( $error['home'] ?? '' );
		$league      = $escape( $error['league'] ?? '' );
		$with        = $escape( $error['with'] ?? '' );
		$with_raw    = $error['with_league'] ?? '';
		$with_league = ! empty( $with_raw ) ? ' (' . $escape( $with_raw ) . ')' : '';
		$court       = $escape( $error['court'] ?? '' );

		return '<li>Week ' . $week . ': ' . $home . ' (' . $league . ') and ' . $with . $with_league . ' clashing on Shared Court: ' . $court . '</li>';
	}

	/**
	 * Format a duplicate team alert record for HTML display.
	 *
	 * @param array $alert Duplicate team alert data containing message, league, and/or team_id.
	 * @return string HTML <li> element.
	 */
	public static function format_duplicate_alert_item( array $alert ): string {
		$escape = function_exists( 'esc_html' )
			? 'esc_html'
			: static fn( $v ) => htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' );

		$league_name = $alert['league'] ?? '';
		$msg         = ! empty( $alert['message'] )
			? $alert['message']
			: "League '" . $league_name . "' contains duplicate team ID '" . ( $alert['team_id'] ?? '' ) . "'";

		return '<li>' . $escape( $msg ) . '</li>';
	}

}