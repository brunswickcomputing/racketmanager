<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Standings;

use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\Stage;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Services\League_Service;
use Racketmanager\Util\Util;
use function Racketmanager\get_league_team;

/**
 * Service for calculating and managing competition standings (league tables).
 */
class Standings_Service {
	private ?League_Repository_Interface $league_repository = null;
	private ?League_Team_Repository_Interface $league_team_repository = null;
	private ?League_Service $league_service = null;
	private ?Fixture_Repository_Interface $fixture_repository = null;

	public function __construct(
		?League_Repository_Interface $league_repository = null,
		?League_Team_Repository_Interface $league_team_repository = null,
		?League_Service $league_service = null,
		?Fixture_Repository_Interface $fixture_repository = null
	) {
		$this->league_repository      = $league_repository;
		$this->league_team_repository = $league_team_repository;
		$this->league_service         = $league_service;
		$this->fixture_repository     = $fixture_repository;
	}

	private function get_fixture_repository(): Fixture_Repository_Interface {
		if ( null === $this->fixture_repository ) {
			global $racketmanager;
			$this->fixture_repository = isset( $racketmanager->container ) && $racketmanager->container->has( 'fixture_repository' )
				? $racketmanager->container->get( 'fixture_repository' )
				: new Fixture_Repository();
		}
		return $this->fixture_repository;
	}

	private function get_league_repository(): League_Repository_Interface {
		if ( null === $this->league_repository ) {
			global $racketmanager;
			$this->league_repository = isset( $racketmanager->container ) && $racketmanager->container->has( 'league_repository' )
				? $racketmanager->container->get( 'league_repository' )
				: new League_Repository();
		}
		return $this->league_repository;
	}

	private function get_league_service(): ?League_Service {
		if ( null === $this->league_service ) {
			global $racketmanager;
			$this->league_service = isset( $racketmanager->container ) && $racketmanager->container->has( 'league_service' )
				? $racketmanager->container->get( 'league_service' )
				: null;
		}
		return $this->league_service;
	}

	/**
	 * Calculate standings for a given stage and season.
	 *
	 * @param Stage $stage
	 * @param int $season
	 * @return array Standings data.
	 */
	public function calculate_standings( Stage $stage, int $season ): array {
		$league = $stage->get_league();
		if ( ! $league ) {
			return [];
		}
		$teams = $league->get_league_teams( array( 'season' => (string) $season ) );
		return $this->get_standings( $league, $teams, 0 );
	}

	/**
	 * Update the stored rankings for a set of teams.
	 *
	 * @param array $standings
	 * @return void
	 */
	public function update_rankings( array $standings ): void {
		$league_service = $this->get_league_service();
		if ( $league_service ) {
			$league_service->update_ranking( $standings );
		}
	}

	/**
	 * Get standings table for teams in a league.
	 *
	 * @param League $league
	 * @param array $teams
	 * @param int $match_day
	 * @param string $mode
	 * @return array
	 */
	public function get_standings( League $league, array $teams, int $match_day, string $mode = 'all' ): array {
		// hide status as it's meaningless.
		$league->standings['status'] = 0;

		// set basic match query args.
		$league->set_match_query_arg( 'standingstable', true );
		$league->set_match_query_arg( 'match_day', $match_day );
		$league->set_match_query_arg( 'final', '' );
		$league->set_match_query_arg( 'limit', false );
		$league->set_match_query_arg( 'mode', $mode );

		foreach ( $teams as $i => $team ) {
			$team       = get_league_team( $team );
			$match_args = array();

			// get only home matches.
			if ( 'home' === $mode ) {
				$match_args['home_team'] = $team->id;
			}
			// get only away matches.
			if ( 'away' === $mode ) {
				$match_args['away_team'] = $team->id;
			}
			// get all matches for given team.
			if ( 'all' === $mode ) {
				$match_args['team_id'] = $team->id;
			}
			// get matches up to given match day.
			if ( $match_day ) {
				$match_args['match_day'] = $match_day;
			}
			$match_args['confirmed'] = true;
			$match_args['status']    = array(
				'status_code' => 'Cancelled',
				'compare'     => 'not',
			);

			// initialize team standings data.
			$team->done_matches   = 0;
			$team->won_matches    = 0;
			$team->draw_matches   = 0;
			$team->lost_matches   = 0;
			$team->points_plus    = 0;
			$team->points_minus   = 0;
			$team->points_2_plus  = 0;
			$team->points_2_minus = 0;

			// get matches.
			$matches = $league->get_matches( $match_args );
			foreach ( $matches as $match ) {
				if ( '' !== $match->home_points && '' !== $match->away_points ) {
					$team->done_matches += 1;
				}
				if ( $match->winner_id === $team->id ) {
					$team->won_matches += 1;
				}
				if ( $match->loser_id === $team->id ) {
					$team->lost_matches += 1;
				}
				if ( -1 === $match->winner_id && -1 === $match->loser_id ) {
					$team->draw_matches += 1;
				}
			}
			$team->points         = $this->calculate_points( $league, $team, $matches );
			$team->points_plus    = $team->points['plus'];
			$team->points_minus   = $team->points['minus'];
			$team->points_2_plus  = 0;
			$team->points_2_minus = 0;
			$team->diff           = $team->points_2_plus - $team->points_2_minus;
			$team->custom['diff'] = $team->diff;
			$team->win_percent();
			$custom = $this->get_standings_data( $league, (int) $team->id, $team->custom, $matches );
			foreach ( $custom as $key => $value ) {
				$team->{$key} = $value;
			}
			$teams[ $i ] = $team;
		}

		/*
		* rank teams.
		*/
		$league_service = $this->get_league_service();
		if ( $league_service ) {
			$teams = $league_service->rank_teams_by_points( $teams );
			return $league_service->get_ranking( $league, $teams );
		}
		return $teams;
	}

	/**
	 * Rank teams in groups for a league.
	 *
	 * @param League $league
	 * @param false|string $season
	 * @return void
	 */
	public function set_teams_rank( League $league, false|string $season = false ): void {
		if ( ! isset( $season ) || false === $season ) {
			$season = $league->current_season;
		}
		$season = is_array( $season ) ? ( $season['name'] ?? '' ) : $season;

		// rank Teams in groups.
		$groups = ! empty( $league->groups ) ? explode( ';', $league->groups ) : array( '0' );
		foreach ( $groups as $group ) {
			$team_args = array( 'season' => $season );
			if ( ! empty( $group ) ) {
				$team_args['group'] = $group;
			}
			$teams = $league->get_league_teams( $team_args );

			if ( ! empty( $teams ) && isset( $league->event->competition->settings['team_ranking'] ) && 'auto' === $league->event->competition->settings['team_ranking'] ) {
				$league_service = $this->get_league_service();
				if ( $league_service ) {
					$teams = $league_service->rank_teams_by_points( $teams );
					$teams = $league_service->get_ranking( $league, $teams );
					$league_service->update_ranking( $teams );
				}
			}
		}
	}

	/**
	 * Update standings manually.
	 *
	 * @param League $league
	 * @param array $teams
	 * @param array $points
	 * @param array $matches
	 * @param array $custom
	 * @return void
	 */
	public function save_standings_manually( League $league, array $teams, array $points, array $matches, array $custom ): void {
		$season      = $league->current_season['name'] ?? '';
		$league_repo = $this->get_league_repository();

		foreach ( array_keys( $teams ) as $id ) {
			$league_repo->update_standings_manually(
				$league->id,
				$season,
				(int) $id,
				array(
					'points_plus'  => $points['points_plus'][ $id ] ?? 0,
					'points_minus' => $points['points_minus'][ $id ] ?? 0,
					'add_points'   => $points['add_points'][ $id ] ?? 0,
				),
				array(
					'num_done_matches' => $matches['num_done_matches'][ $id ] ?? 0,
					'num_won_matches'  => $matches['num_won_matches'][ $id ] ?? 0,
					'num_draw_matches' => $matches['num_draw_matches'][ $id ] ?? 0,
					'num_lost_matches' => $matches['num_lost_matches'][ $id ] ?? 0,
				)
			);
			wp_cache_flush();
		}

		// Update Teams Rank and Status if not set to manual ranking.
		if ( isset( $league->event->competition->settings['team_ranking'] ) && 'manual' !== $league->event->competition->settings['team_ranking'] ) {
			$this->set_teams_rank( $league, $season );
		}
	}

	/**
	 * Update standings for all teams in a league season.
	 *
	 * @param League $league
	 * @param string $season
	 * @return void
	 */
	public function update_standings( League $league, string $season ): void {
		// update Standings for each team.
		$league_teams = $league->get_league_teams(
			array(
				'season' => $season,
				'cache'  => false,
			)
		);
		foreach ( $league_teams as $league_team ) {
			$this->save_standings( $league, $league_team );
		}
		// Update Teams Rank and Status.
		$this->set_teams_rank( $league, $season );
	}

	/**
	 * Save standings for a single league team.
	 *
	 * @param League $league
	 * @param object $league_team
	 * @return void
	 */
	public function save_standings( League $league, object $league_team ): void {
		if ( 'manual' !== $league->point_rule ) {
			$league_team = get_league_team( $league_team );
			$league_team->get_num_done_matches();
			$league_team->get_num_won_matches();
			$league_team->get_num_draw_matches();
			$league_team->get_num_lost_matches();

			$league_team->points  = $this->calculate_points( $league, $league_team );
			$league_team->diff    = 0;
			$league_team->points_2 = array(
				'plus'  => 0,
				'minus' => 0,
			);
			// get custom team standings data.
			$league_team->custom = $this->get_standings_data( $league, (int) $league_team->id, $league_team->custom );

			$this->get_league_repository()->update_team_standings(
				(int) $league_team->league_id,
				(string) $league_team->season,
				(int) $league_team->id,
				array(
					'points_plus'    => $league_team->points['plus'],
					'points_minus'   => $league_team->points['minus'],
					'points_2_plus'  => $league_team->points_2['plus'],
					'points_2_minus' => $league_team->points_2['minus'],
					'done_matches'   => $league_team->done_matches,
					'won_matches'    => $league_team->won_matches,
					'draw_matches'   => $league_team->draw_matches,
					'lost_matches'   => $league_team->lost_matches,
					'diff'           => $league_team->diff,
					'custom'         => $league_team->custom,
				)
			);
			wp_cache_delete( $league_team->id, '$league_team' );
			wp_cache_delete( $league_team->league_id, 'leaguetable' );
		}
	}

	/**
	 * Calculate points for given team depending on point rule.
	 *
	 * @param League $league
	 * @param object $team
	 * @param false|array $matches
	 * @return array
	 */
	public function calculate_points( League $league, object $team, false|array $matches = false ): array {
		$team_id     = $team->id;
		$season      = $team->season;
		$point_rule  = $league->get_point_rule( $league->point_rule );
		$points      = array(
			'plus'  => 0,
			'minus' => 0,
		);
		if ( ! $matches ) {
			$matches = $league->get_matches(
				array(
					'team_id'          => $team->id,
					'match_day'        => -1,
					'limit'            => false,
					'season'           => $season,
					'cache'            => false,
					'reset_query_args' => true,
				)
			);
		}

		if ( $matches ) {
			$points_for     = 0;
			$points_against = 0;
			foreach ( $matches as $match ) {
				if ( strval( $match->home_team ) === strval( $team_id ) ) {
					$points_for     += $match->home_points;
					$points_against += $match->away_points;
				} elseif ( strval( $match->away_team ) === strval( $team_id ) ) {
					$points_for     += $match->away_points;
					$points_against += $match->home_points;
				}
			}
			$points['plus']  = $points_for;
			$points['minus'] = $points_against;
		} else {
			$forwin             = empty( $point_rule['forwin'] ) ? 0 : $point_rule['forwin'];
			$forwin_split       = empty( $point_rule['forwin_split'] ) ? 0 : $point_rule['forwin_split'];
			$forloss_split      = empty( $point_rule['forloss_split'] ) ? 0 : $point_rule['forloss_split'];
			$forshare           = empty( $point_rule['forshare'] ) ? 0 : $point_rule['forshare'];
			$forwalkover_rubber = empty( $point_rule['forwalkover_rubber'] ) ? 0 : $point_rule['forwalkover_rubber'];
			$walkover_penalty   = empty( $point_rule['forwalkover_match'] ) ? 0 : $point_rule['forwalkover_match'];
			$rubber_win         = empty( $point_rule['rubber_win'] ) ? 0 : $point_rule['rubber_win'];
			$rubber_draw        = empty( $point_rule['rubber_draw'] ) ? 0 : $point_rule['rubber_draw'];
			$matches_win        = empty( $point_rule['matches_win'] ) ? 0 : $point_rule['matches_win'];
			$matches_draw       = empty( $point_rule['matches_draw'] ) ? 0 : $point_rule['matches_draw'];
			$shared_match       = empty( $point_rule['shared_match'] ) ? 0 : $point_rule['shared_match'];
			$data               = $this->get_standings_data( $league, (int) $team_id, array(), $matches );
			if ( ! empty( $point_rule['match_result'] ) ) {
				if ( 'rubber_count' === $point_rule['match_result'] ) {
					$points['plus'] = $data['rubbers_won'] * $rubber_win + $data['rubbers_shared'] * $rubber_draw - ( $data['no_player'] * $forwalkover_rubber ) - $data['no_team'] * $walkover_penalty + $data['matches_shared'] * $shared_match;
				} elseif ( 'games' === $point_rule['match_result'] ) {
					$points['plus'] = $data['games_won'];
				}
			} else {
				$points['plus']  = $data['sets_won'] + ( $data['straight_set']['win'] * $forwin ) + ( $data['split_set']['win'] * $forwin_split ) + ( $data['split_set']['lost'] * $forloss_split ) + ( $data['sets_shared'] * $forshare ) - ( $data['no_player'] * $forwalkover_rubber ) - ( $data['no_team'] * $walkover_penalty ) + ( $data['matches_won'] * $matches_win ) + ( $data['matches_shared'] * $matches_draw );
				$points['minus'] = $data['sets_allowed'] + ( $data['straight_set']['lost'] * $forwin ) + ( $data['split_set']['win'] * $forloss_split ) + ( $data['split_set']['lost'] * $forwin_split ) + ( $data['sets_shared'] * $forshare );
			}
		}
		return $points;
	}

	/**
	 * Determine if two teams are tied based on criteria.
	 *
	 * @param object $team1
	 * @param object $team2
	 * @return bool
	 */
	public function is_tie( object $team1, object $team2 ): bool {
		$res = array(
			'primary'    => false,
			'sets_diff'  => false,
			'games_diff' => false,
			'sets_won'   => false,
		);

		$team1_points = $team1->points['plus'] ?? 0;
		$team2_points = $team2->points['plus'] ?? 0;
		if ( $team1_points === $team2_points ) {
			$res['primary'] = true;
		}

		$team1_sets_won     = $team1->sets_won ?? 0;
		$team1_sets_allowed = $team1->sets_allowed ?? 0;
		$team2_sets_won     = $team2->sets_won ?? 0;
		$team2_sets_allowed = $team2->sets_allowed ?? 0;

		if ( ( $team1_sets_won - $team1_sets_allowed ) === ( $team2_sets_won - $team2_sets_allowed ) ) {
			$res['sets_diff'] = true;
		}

		$team1_games_won     = $team1->games_won ?? 0;
		$team1_games_allowed = $team1->games_allowed ?? 0;
		$team2_games_won     = $team2->games_won ?? 0;
		$team2_games_allowed = $team2->games_allowed ?? 0;

		if ( ( $team1_games_won - $team1_games_allowed ) === ( $team2_games_won - $team2_games_allowed ) ) {
			$res['games_diff'] = true;
		}
		if ( $team1_sets_won === $team2_sets_won ) {
			$res['sets_won'] = true;
		}

		$res = array_values( array_unique( $res ) );

		if ( count( $res ) > 1 ) {
			return false;
		}

		return (bool) ( $res[0] ?? false );
	}

	/**
	 * Get custom standings data for a team.
	 *
	 * @param League $league
	 * @param int $team_id
	 * @param array $data
	 * @param array|false $matches
	 * @return array
	 */
	public function get_standings_data( League $league, int $team_id, array $data = array(), array|false $matches = array() ): array {
		$data['straight_set']   = array(
			'win'  => 0,
			'lost' => 0,
		);
		$data['split_set']      = $data['straight_set'];
		$data['games_allowed']  = 0;
		$data['games_won']      = 0;
		$data['sets_won']       = 0;
		$data['sets_allowed']   = 0;
		$data['sets_shared']    = 0;
		$data['no_player']      = 0;
		$data['no_team']        = 0;
		$data['rubbers_won']    = 0;
		$data['rubbers_shared'] = 0;
		$data['matches_won']    = 0;
		$data['matches_shared'] = 0;

		$walkover_sets  = $league->num_sets_to_win ?? 2;
		$set_type       = Util::get_set_type( $league->scoring ?? '' );
		$set_info       = Util::get_set_info( $set_type );
		$games_to_win   = $set_info->min_win ?? 6;
		$walkover_games = $walkover_sets * $games_to_win;

		$season = $league->get_season();

		if ( ! $matches ) {
			$matches = $league->get_matches(
				array(
					'season'           => $season,
					'team_id'          => $team_id,
					'final'            => '',
					'limit'            => false,
					'cache'            => false,
					'reset_query_args' => true,
				)
			);
		}
		foreach ( $matches as $match ) {
			$team_ref       = strval( $team_id ) === strval( $match->home_team ) ? 'home' : 'away';
			$team_ref_alt   = 'home' === $team_ref ? 'away' : 'home';
			$player_ref     = strval( $team_id ) === strval( $match->home_team ) ? 'player1' : 'player2';
			$player_ref_alt = 'player1' === $player_ref ? 'player2' : 'player1';
			$match          = is_object( $match ) && method_exists( $match, 'get_rubbers' ) ? $match : $this->get_fixture_repository()->find_by_id( is_numeric( $match ) ? (int) $match : (int) $match->id );
			if ( $match && ! empty( $match->winner_id ) && ! empty( $match->loser_id ) && ( $match->teams['home']->status ?? '' ) !== 'W' && ( $match->teams['away']->status ?? '' ) !== 'W' && empty( $match->is_cancelled ) ) {
				if ( ! empty( $match->status ) && 3 === $match->status ) {
					++$data['matches_shared'];
				}
				if ( ! empty( $league->num_rubbers ) ) {
					$rubbers_won    = 0;
					$rubbers_lost   = 0;
					$rubbers_shared = 0;
					$rubbers        = method_exists( $match, 'get_rubbers' ) ? $match->get_rubbers() : [];
					$walkovers      = 0;
					foreach ( $rubbers as $rubber ) {
						if ( empty( $rubber->is_walkover ) && empty( $rubber->is_shared ) ) {
							$num_sets    = count( $rubber->sets ?? [] );
							$set_retired = null;
							if ( ! empty( $rubber->is_retired ) || ! empty( $rubber->is_abandoned ) ) {
								for ( $s1 = $num_sets; $s1 > 0; $s1-- ) {
									if ( ( isset( $rubber->sets[ $s1 ]['player1'] ) && '' !== $rubber->sets[ $s1 ]['player1'] ) || ( isset( $rubber->sets[ $s1 ]['player2'] ) && '' !== $rubber->sets[ $s1 ]['player2'] ) ) {
										$set_retired = $s1;
										break;
									}
								}
							}
							for ( $j = 1; $j <= (int) $league->num_sets; $j++ ) {
								$set_type = Util::get_set_type( $league->scoring ?? '', null, $league->num_sets ?? 3, $j );
								if ( isset( $rubber->sets[ $j ]['player1'] ) && null !== $rubber->sets[ $j ]['player1'] ) {
									$set        = $rubber->sets[ $j ];
									$set_winner = null;
									if ( ! empty( $rubber->is_retired ) && $set_retired === $j ) {
										if ( ! empty( $rubber->custom['retired'] ) && $team_ref === $rubber->custom['retired'] ) {
											$set_winner = $team_ref_alt;
										} else {
											$set_winner = $team_ref;
										}
									}
									if ( ! empty( $rubber->is_abandoned ) && $set_retired === $j ) {
										$set_winner = 'abandoned';
									}
									if ( is_numeric( trim( (string) ( $set[ $player_ref_alt ] ?? '' ) ) ) ) {
										if ( 'MTB' === $set_type ) {
											if ( strval( $rubber->loser_id ?? '' ) === strval( $team_id ) ) {
												++$data['games_allowed'];
											}
										} else {
											$data['games_allowed'] += intval( $set[ $player_ref_alt ] );
										}
									}
									if ( is_numeric( trim( (string) ( $set[ $player_ref ] ?? '' ) ) ) ) {
										if ( 'MTB' === $set_type ) {
											if ( strval( $rubber->winner_id ?? '' ) === strval( $team_id ) ) {
												++$data['games_won'];
											}
										} else {
											$data['games_won'] += intval( $set[ $player_ref ] );
										}
									}
									if ( ( ( $set[ $player_ref ] ?? 0 ) > ( $set[ $player_ref_alt ] ?? 0 ) && empty( $set_winner ) ) || $team_ref === $set_winner ) {
										$data['sets_won'] += 1;
									} elseif ( ( ( $set[ $player_ref ] ?? 0 ) < ( $set[ $player_ref_alt ] ?? 0 ) && empty( $set_winner ) ) || $team_ref_alt === $set_winner ) {
										$data['sets_allowed'] += 1;
									} elseif ( 'S' === strtoupper( (string) ( $set[ $player_ref ] ?? '' ) ) || ! empty( $rubber->is_abandoned ) || ! empty( $rubber->is_retired ) ) {
										$data['sets_shared'] += 1;
									}
								} else {
									if ( ! empty( $rubber->is_abandoned ) || ! empty( $rubber->is_retired ) ) {
										$data['sets_shared'] += 1;
									}
								}
							}
						} elseif ( ! empty( $rubber->is_shared ) ) {
							$data['sets_shared'] += (int) $league->num_sets;
							++$data['rubbers_shared'];
							++$rubbers_shared;
						}
						if ( strval( $rubber->winner_id ?? '' ) === strval( $team_id ) || '-1' === strval( $rubber->winner_id ?? '' ) ) { // winning team.
							if ( strval( $rubber->winner_id ?? '' ) === strval( $team_id ) ) {
								++$data['rubbers_won'];
								++$rubbers_won;
							}
							if ( ! empty( $rubber->is_walkover ) ) {
								$data['sets_won']            += $walkover_sets;
								$data['games_won']           += $walkover_games;
								$data['straight_set']['win'] += 1;
							} elseif ( strval( $match->home_team ) === strval( $team_id ) ) {   // home team.
								if ( strval( $rubber->home_points ?? '' ) > '0' ) {
									if ( strval( $rubber->away_points ?? '' ) > '0' ) {
										$data['split_set']['win'] += 1;
									} else {
										$data['straight_set']['win'] += 1;
									}
								}
							} elseif ( strval( $rubber->away_points ?? '' ) > '0' ) { // away team.
								if ( strval( $rubber->home_points ?? '' ) > '0' ) {
									$data['split_set']['win'] += 1;
								} else {
									$data['straight_set']['win'] += 1;
								}
							}
						} elseif ( strval( $rubber->loser_id ?? '' ) === strval( $team_id ) ) { // losing team.
							++$rubbers_lost;
							if ( ! empty( $rubber->is_walkover ) ) {
								$data['sets_allowed']         += $walkover_sets;
								$data['games_allowed']        += $walkover_games;
								$data['no_player']            += 1;
								$data['straight_set']['lost'] += 1;
								++$walkovers;
							} elseif ( strval( $match->home_team ) === strval( $team_id ) ) {   // team loss.
								if ( strval( $rubber->home_points ?? '' ) > '0' ) {
									$data['split_set']['lost'] += 1;
								} else {
									$data['straight_set']['lost'] += 1;
								}
							} elseif ( strval( $rubber->away_points ?? '' ) > '0' ) { // team split set loss.
								$data['split_set']['lost'] += 1;
							} else {                                 // team straight set loss.
								$data['straight_set']['lost'] += 1;
							}
						}
					}
					if ( intval( $match->league->num_rubbers ?? $league->num_rubbers ) === $walkovers ) {
						$data['no_team'] += $walkovers;
					}
					if ( $rubbers_shared ) {
						if ( $rubbers_won === $rubbers_lost ) {
							++$data['matches_shared'];
						} elseif ( $rubbers_won > $rubbers_lost ) {
							++$data['matches_won'];
						}
					} elseif ( $rubbers_won > $rubbers_lost ) {
						++$data['matches_won'];
					}
				} else {
					$set_winner = null;
					for ( $j = 1; $j <= (int) $league->num_sets; $j++ ) {
						$set = $match->sets[ $j ] ?? [];
						if ( ( ( $set[ $player_ref ] ?? 0 ) > ( $set[ $player_ref_alt ] ?? 0 ) && empty( $set_winner ) ) || $team_ref === $set_winner ) {
							$data['sets_won'] += 1;
						} elseif ( ( ( $set[ $player_ref ] ?? 0 ) < ( $set[ $player_ref_alt ] ?? 0 ) && empty( $set_winner ) ) || $team_ref_alt === $set_winner ) {
							$data['sets_allowed'] += 1;
						} elseif ( 'S' === strtoupper( (string) ( $set[ $player_ref ] ?? '' ) ) ) {
							$data['sets_shared'] += 1;
						}
						$data['games_allowed'] += (int) ( $match->sets[ $j ][ $player_ref_alt ] ?? 0 );
						$data['games_won']     += (int) ( $match->sets[ $j ][ $player_ref ] ?? 0 );
					}
					if ( $league->num_sets > 1 && '' !== ( $match->sets[ $league->num_sets ]['player1'] ?? '' ) && '' !== ( $match->sets[ $league->num_sets ]['player2'] ?? '' ) ) {
						if ( strval( $match->winner_id ) === strval( $team_id ) ) {
							$data['split_set']['win'] += 1;
						} elseif ( strval( $match->loser_id ) === strval( $team_id ) ) {
							$data['split_set']['lost'] += 1;
						}
					} elseif ( strval( $match->winner_id ) === strval( $team_id ) ) {
						$data['straight_set']['win'] += 1;
					} elseif ( strval( $match->loser_id ) === strval( $team_id ) ) {
						$data['straight_set']['lost'] += 1;
					}
				}
			}
		}
		return $data;
	}
}
