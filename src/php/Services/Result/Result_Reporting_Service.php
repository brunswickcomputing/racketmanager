<?php
/**
 * Result Reporting Service
 *
 * @package Racketmanager\Services\Result
 */

namespace Racketmanager\Services\Result;

use Racketmanager\Domain\Racketmanager_Match;
use Racketmanager\Domain\Scoring\Set_Score;
use stdClass;

/**
 * Class Result_Reporting_Service
 */
class Result_Reporting_Service {

	/**
	 * Report result for a match
	 *
	 * @param Racketmanager_Match $match            The match to report.
	 * @param string|null         $competition_code competition code (optional).
	 *
	 * @return object|null
	 */
	public function report_result( Racketmanager_Match $match, ?string $competition_code = null ): ?object {
		global $racketmanager;
		$result = null;

		if ( empty( $competition_code ) ) {
			$competition_season = empty( $match->league->event->competition->get_season_by_name( $match->season ) ) ? null : $match->league->event->competition->get_season_by_name( $match->season );
			$competition_code   = empty( $competition_season['competition_code'] ) ? $match->league->event->competition->competition_code : $competition_season['competition_code'];
			$event_season       = empty( $match->league->event->get_season_by_name( $match->season ) ) ? null : $match->league->event->get_season( $match->season );
			$grade              = $event_season['grade'] ?? $match->league->event->competition->settings['grade'];
		} else {
			// Need a grade even if code is provided? Original code didn't set it if code was NOT empty,
			// but it's used later. Let's see original logic.
			// Actually original logic:
			// if ( empty( $competition_code ) ) { ... sets $competition_code and $grade ... }
			// if ( ! empty( $competition_code ) ) { ... uses $grade ... }
			// If $competition_code was passed as argument, $grade would be undefined.
			// So I should probably always set $grade if possible.
			$event_season = empty( $match->league->event->get_season_by_name( $match->season ) ) ? null : $match->league->event->get_season( $match->season );
			$grade        = $event_season['grade'] ?? $match->league->event->competition->settings['grade'] ?? null;
		}

		if ( ! empty( $competition_code ) ) {
			$result                   = new stdClass();
			$result->tournament       = $racketmanager->site_name . ' ' . $match->league->event->competition->name;
			$result->code             = $competition_code;
			$result->organiser        = '';
			$result->venue            = '';
			$result->event_name       = $match->league->event->name;
			$result->grade            = $grade;
			$result->event_end_date   = $match->league->event->competition->date_end;
			$result->event_start_date = $match->league->event->competition->date_start;
			$age_group                = match ( $match->league->event->age_limit ) {
				8, 9, 10, 11, 12, 14, 16, 18, 21               => $match->league->event->age_limit . ' & Under',
				30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85 => $match->league->event->age_limit . ' & Over',
				default                                        => 'Open',
			};
			$result->age_group  = $age_group;
			$result->event_type = 'Singles';
			if ( 'D' === substr( $match->league->event->type, 1, 1 ) ) {
				$result->event_type = 'Doubles';
			}
			if ( str_starts_with( $match->league->event->type, 'M' ) ) {
				$result->gender = 'Male';
			} elseif ( str_starts_with( $match->league->event->type, 'W' ) ) {
				$result->gender = 'Female';
			} else {
				$result->gender = 'Mixed';
			}
			$result->draw_name = $match->league->title;
			if ( 'league' === $match->league->event->competition->type ) {
				$result->draw_type  = 'Round Robin';
				$result->draw_stage = 'MD - Main draw';
				$result->draw_size  = $match->league->num_teams_total;
				$result->round      = 'RR' . $match->match_day;
			} else {
				$result->draw_type = 'Elimination';
				if ( $match->league_id === $match->league->event->primary_league ) {
					$result->draw_stage = 'MD - Main draw';
				} else {
					$result->draw_stage = 'CD - Consolation draw';
				}
				$result->draw_size = $match->league->championship->num_teams_first_round;
				$result->round     = match ( $match->final_round ) {
					'final'   => 'F',
					'semi'    => 'SF',
					'quarter' => 'QF',
					'last-16' => 'R16',
					'last-32' => 'R32',
					'last-64' => 'R64',
					default   => 'RR1',
				};
			}
			$result->matches = array();
			if ( $match->league->num_rubbers ) {
				if ( ! $match->is_cancelled && ! $match->is_shared && ! $match->is_withdrawn ) {
					$rubbers = $match->get_rubbers();
					foreach ( $rubbers as $rubber ) {
						if ( ! $rubber->is_walkover && ! $rubber->is_shared && ! empty( $rubber->winner_id ) && ! empty( $rubber->loser_id ) ) {
							if ( $rubber->is_invalid ) {
								$score_home = 0;
								$score_away = 0;
								foreach ( $rubber->sets as $set ) {
									if ( $set['player1'] > $set['player2'] ) {
										++$score_home;
									} elseif ( $set['player2'] > $set['player1'] ) {
										++$score_away;
									}
								}
								if ( $score_home > $score_away ) {
									$winner_id = $match->home_team;
								} elseif ( $score_away > $score_home ) {
									$winner_id = $match->away_team;
								} else {
									$winner_id = null;
								}
							} else {
								$winner_id = $rubber->winner_id;
							}
							$result_match        = new stdClass();
							$result_match->match = $rubber->id;
							if ( (string) $winner_id === (string) $match->home_team ) {
								$winning_team   = 'home';
								$winning_player = 'player1';
								$losing_team    = 'away';
								$losing_player  = 'player2';
							} else {
								$winning_team   = 'away';
								$winning_player = 'player2';
								$losing_team    = 'home';
								$losing_player  = 'player1';
							}
							$result_match->winner_name   = $rubber->players[ $winning_team ]['1']->display_name;
							$result_match->winner_lta_no = $rubber->players[ $winning_team ]['1']->btm;
							$result_match->loser_name    = $rubber->players[ $losing_team ]['1']->display_name;
							$result_match->loser_lta_no  = $rubber->players[ $losing_team ]['1']->btm;
							if ( 'D' === substr( $match->league->event->type, 1, 1 ) ) {
								$result_match->winnerpartner        = $rubber->players[ $winning_team ]['2']->display_name;
								$result_match->winnerpartner_lta_no = $rubber->players[ $winning_team ]['2']->btm;
								$result_match->loserpartner         = $rubber->players[ $losing_team ]['2']->display_name;
								$result_match->loserpartner_lta_no  = $rubber->players[ $losing_team ]['2']->btm;
							}
							$result_match->score      = '';
							$result_match->score_code = '';
							if ( $rubber->is_retired ) {
								$result_match->score_code = 'Retired';
							}
							$result_match->match_date = mysql2date( 'Y-m-d', $match->match_date );
							$result_match             = $this->report_result_scores( $result_match, $rubber->sets, $winning_player, $losing_player );
							$result->matches[]        = $result_match;
						}
					}
				}
			} else {
				$result_match = new stdClass();
				if ( ! $match->is_walkover && (string) '-1' !== (string) $match->home_team && (string) '-1' !== (string) $match->away_team ) {
					$result_match->match = $match->id;
					if ( (string) $match->winner_id === (string) $match->home_team ) {
						$winning_team   = 'home';
						$winning_player = 'player1';
						$losing_team    = 'away';
						$losing_player  = 'player2';
					} else {
						$winning_team   = 'away';
						$winning_player = 'player2';
						$losing_team    = 'home';
						$losing_player  = 'player1';
					}
					$result_match->winner_name          = $match->teams[ $winning_team ]->players['1']->display_name;
					$result_match->winner_lta_no        = $match->teams[ $winning_team ]->players['1']->btm;
					$result_match->loser_name           = $match->teams[ $losing_team ]->players['1']->display_name;
					$result_match->loser_lta_no         = $match->teams[ $losing_team ]->players['1']->btm;
					$result_match->winnerpartner        = '';
					$result_match->winnerpartner_lta_no = '';
					$result_match->loserpartner         = '';
					$result_match->loserpartner_lta_no  = '';
					if ( 'D' === substr( $match->league->event->type, 1, 1 ) ) {
						$result_match->winnerpartner        = $match->teams[ $winning_team ]->players['2']->display_name;
						$result_match->winnerpartner_lta_no = $match->teams[ $winning_team ]->players['2']->btm;
						$result_match->loserpartner         = $match->teams[ $losing_team ]->players['2']->display_name;
						$result_match->loserpartner_lta_no  = $match->teams[ $losing_team ]->players['2']->btm;
					}
					$result_match->score      = '';
					$result_match->match_date = mysql2date( 'Y-m-d', $match->match_date );
					$result_match             = $this->report_result_scores( $result_match, $match->sets, $winning_player, $losing_player );
					$result_match->score_code = '';
					if ( $match->is_retired ) {
						$result_match->score_code = 'R';
					} elseif ( $match->is_walkover || empty( $result_match->score ) ) {
						$result_match->score_code = 'W';
					} elseif ( $match->is_shared || $match->is_cancelled ) {
						$result_match->score_code = 'N';
					}
					$result->matches[] = $result_match;
				}
			}
		}

		return $result;
	}

	/**
	 * Produce scores for reporting results
	 *
	 * @param object $result_match   match result object.
	 * @param array  $sets           sets.
	 * @param string $winning_player winning player reference.
	 * @param string $losing_player  losing player reference.
	 *
	 * @return object updated result_match object.
	 */
	private function report_result_scores( object $result_match, array $sets, string $winning_player, string $losing_player ): object {
		for ( $s = 1; $s <= 5; $s++ ) {
			$team1set = 'set' . $s . 'team1';
			$team2set = 'set' . $s . 'team2';
			$tiebreak = 'tiebreak' . $s;

			$set = $sets[ $s ] ?? null;
			if ( $set instanceof Set_Score ) {
				$p1 = 'player1' === $winning_player ? $set->get_home_games() : $set->get_away_games();
				$p2 = 'player1' === $losing_player ? $set->get_home_games() : $set->get_away_games();
				$tb = 'player1' === $winning_player ? $set->get_home_tiebreak() : $set->get_away_tiebreak();

				if ( $s > 1 ) {
					$result_match->score .= ' ';
				}

				$result_match->score .= $p1 . '-' . $p2;
				if ( ! empty( $tb ) ) {
					$result_match->score    .= '(' . $tb . ')';
					$result_match->$tiebreak = $tb;
				} else {
					$result_match->$tiebreak = '';
				}

				$result_match->$team1set = $p1;
				$result_match->$team2set = $p2;
			} elseif ( is_array( $set ) && ( ! empty( $set[ $winning_player ] ) || ! empty( $set[ $losing_player ] ) ) ) {
				if ( $s > 1 ) {
					$result_match->score .= ' ';
				}
				$match_tiebreak = false;
				if ( ( isset( $set['settype'] ) && 'MTB' === $set['settype'] ) || ( 3 === $s && '1' === (string) $set[ $winning_player ] && '0' === (string) $set[ $losing_player ] ) ) {
					$result_match->score .= '[';
					$match_tiebreak       = true;
				}
				if ( $match_tiebreak && ( empty( $set['settype'] ) || 'MTB' !== $set['settype'] ) ) {
					$set[ $winning_player ] = 10;
					$set[ $losing_player ]  = 8;
				}
				if ( '7' === (string) $set[ $winning_player ] && '6' === (string) $set[ $losing_player ] && empty( $set['tiebreak'] ) ) {
					$set['tiebreak'] = 5;
				}
				$result_match->score .= $set[ $winning_player ] . '-' . $set[ $losing_player ];
				if ( ! empty( $set['tiebreak'] ) ) {
					$result_match->score    .= '(' . $set['tiebreak'] . ')';
					$result_match->$tiebreak = $set['tiebreak'];
				} else {
					$result_match->$tiebreak = '';
				}
				if ( $match_tiebreak ) {
					$result_match->score .= ']';
				}
				$result_match->$team1set = $set[ $winning_player ];
				$result_match->$team2set = $set[ $losing_player ];
			} else {
				$result_match->$team1set = '';
				$result_match->$team2set = '';
				$result_match->$tiebreak = '';
			}
		}

		return $result_match;
	}
}
