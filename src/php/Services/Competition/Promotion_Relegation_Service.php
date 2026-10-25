<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Competition;

use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;

/**
 * Service for handling promotion and relegation workflows across leagues in an event.
 */
class Promotion_Relegation_Service {
	private League_Team_Repository_Interface $league_team_repository;
	private League_Repository_Interface $league_repository;
	private Event_Repository_Interface $event_repository;
	private Team_Repository_Interface $team_repository;

	public function __construct(
		League_Team_Repository_Interface $league_team_repository,
		League_Repository_Interface $league_repository,
		Event_Repository_Interface $event_repository,
		Team_Repository_Interface $team_repository
	) {
		$this->league_team_repository = $league_team_repository;
		$this->league_repository      = $league_repository;
		$this->event_repository       = $event_repository;
		$this->team_repository        = $team_repository;
	}

	/**
	 * Promote and relegate teams across leagues for an event season.
	 *
	 * @param Event $event Event entity.
	 * @param array $teams Array of team data objects.
	 * @param int $season Season year.
	 * @return bool True on success, false if event has no leagues or a team cannot be resolved.
	 */
	public function promote_and_relegate( Event $event, array $teams, int $season ): bool {
		$season_name        = (string) $season;
		$competition_season = $event->competition ? $event->competition->get_season_by_name( $season_name ) : null;

		$club_ids = array();
		foreach ( $teams as $team_dtls ) {
			$team = $this->team_repository->find_by_id( $team_dtls->team_id );
			if ( ! $team ) {
				return false;
			}
			$club_ids[ $team_dtls->team_id ] = $team->club_id;
		}

		$event_id = isset( $event->id ) ? (int) $event->id : 0;
		$leagues  = ! empty( $event->leagues ) ? $event->leagues : ( $event_id > 0 ? $this->event_repository->query_leagues( $event_id, array( 'season' => $season_name ) ) : array() );

		$loaded_leagues = array();
		foreach ( (array) $leagues as $league ) {
			if ( $league instanceof League ) {
				$loaded_leagues[] = $league;
			} elseif ( is_object( $league ) && isset( $league->id ) ) {
				$loaded = $this->league_repository->find_by_id( (int) $league->id );
				if ( $loaded instanceof League ) {
					$loaded_leagues[] = $loaded;
				}
			}
		}
		if ( empty( $loaded_leagues ) ) {
			return false;
		}
		$leagues = $loaded_leagues;

		$teams_prom_relg    = intval( $competition_season['teams_prom_relg'] ?? 0 );
		$teams_per_club     = intval( $competition_season['teams_per_club'] ?? 0 );
		$max_teams          = intval( $competition_season['max_teams'] ?? 0 );
		$lowest_promotion   = intval( $competition_season['lowest_promotion'] ?? 0 );
		$highest_relegation = $max_teams - $teams_prom_relg + 1;

		$curr_league_id = null;
		$curr_league    = null;
		$prev_league    = null;
		$next_league    = null;
		$num_entries    = count( $teams );
		$num_promoted   = 0;
		$num_relegated  = 0;
		$team_count     = 0;
		$new_league_id  = 0;
		$rank           = null;
		$count_club     = array();

		foreach ( $teams as $team_dtls ) {
			$status    = null;
			$team_id   = $team_dtls->team_id;
			$league_id = $team_dtls->league_id;

			if ( $league_id !== $curr_league_id ) {
				if ( empty( $rank ) ) {
					$prev_league = null;
					$curr_league = current( $leagues );
				} else {
					$prev_league = prev( $leagues );
					$curr_league = next( $leagues );
					if ( $prev_league instanceof League ) {
						$team_count = $this->count_active_teams( $prev_league, $season_name );
					}
				}
				$next_league    = next( $leagues );
				$curr_league_id = $league_id;
				$num_promoted   = 0;
				$num_relegated  = 0;
				$count_club     = array();
			}

			$club_id  = $club_ids[ $team_id ];
			$old_rank = intval( $team_dtls->old_rank );
			$rank     = intval( $team_dtls->rank );
			$table_id = intval( $team_dtls->table_id );

			if ( 'W' !== $team_dtls->status ) {
				$club_teams_curr = $curr_league instanceof League
					? $this->count_club_teams( $curr_league, $club_id, $season_name )
					: 0;

				if ( empty( $count_club[ $club_id ] ) ) {
					$count_club[ $club_id ] = 0;
				}
				$count_club[ $club_id ] += 1;

				if ( $club_teams_curr > $teams_per_club && $count_club[ $club_id ] === $teams_per_club ) {
					$new_league_id = $next_league instanceof League ? (int) $next_league->id : 0;
					$status        = 'RT';
					++$num_relegated;
				}

				if ( $num_relegated < $teams_prom_relg && $old_rank >= $highest_relegation ) {
					if ( $rank === $num_entries ) {
						$new_league_id = $curr_league instanceof League ? (int) $curr_league->id : 0;
						$status        = 'BT';
					} elseif ( intval( $curr_league->num_teams_total ?? 0 ) === $max_teams ) {
						$new_league_id = $next_league instanceof League ? (int) $next_league->id : 0;
						$status        = 'RB';
						++$num_relegated;
					} else {
						$pos           = $max_teams - $old_rank + 1;
						$new_league_id = $next_league instanceof League ? (int) $next_league->id : 0;
						$status        = 'R' . $pos;
						++$num_relegated;
					}
				} elseif ( intval( $curr_league->num_teams_total ?? 0 ) === $old_rank ) {
					$new_league_id = $curr_league instanceof League ? (int) $curr_league->id : 0;
					$status        = 'BT';
				} elseif ( 1 === $rank ) {
					$status        = 'C';
					$new_league_id = $curr_league instanceof League ? (int) $curr_league->id : 0;
				} elseif ( $prev_league instanceof League && $old_rank <= $lowest_promotion ) {
					if ( intval( $team_count ) !== $max_teams ) {
						$club_teams_prev = $this->count_club_teams( $prev_league, $club_id, $season_name );
						if ( $club_teams_prev < $teams_per_club ) {
							if ( ! empty( $prev_league->id ) ) {
								$status        = 'P' . $old_rank;
								$new_league_id = (int) $prev_league->id;
							}
							++$team_count;
							++$num_promoted;
						} elseif ( 1 === $old_rank ) {
							$status        = 'W' . $old_rank;
							$new_league_id = $curr_league instanceof League ? (int) $curr_league->id : 0;
						}
					}
				}
			}

			if ( $status ) {
				$this->league_team_repository->update_constitution( $table_id, $new_league_id, $status );
			}
		}

		return true;
	}

	/**
	 * Count active teams in a league for a season.
	 *
	 * Mirrors League::get_num_teams( 'active', false ).
	 */
	private function count_active_teams( League $league, string $season ): int {
		return (int) $this->league_repository->query_league_teams(
			(int) $league->id,
			array(
				'season'           => $league->current_season['name'] ?? $season,
				'active'           => true,
				'count'            => true,
				'club'             => false,
				'reset_query_args' => true,
			),
			true
		);
	}

	/**
	 * Count a club's teams in a league for a season.
	 *
	 * Mirrors League::get_league_teams( [ 'club' => ..., 'season' => ..., 'count' => true, 'cache' => false ] ).
	 */
	private function count_club_teams( League $league, mixed $club_id, string $season ): int {
		return (int) $this->league_repository->query_league_teams(
			(int) $league->id,
			array(
				'club'   => $club_id,
				'season' => $season,
				'count'  => true,
				'cache'  => false,
			),
			true
		);
	}
}
