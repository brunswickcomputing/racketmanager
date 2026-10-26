<?php
/**
 * Player_Query_Service class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Services
 */

declare( strict_types=1 );

namespace Racketmanager\Services;

use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Statistics_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use RuntimeException;
use function Racketmanager\get_event;
use function Racketmanager\get_league;

/**
 * Service for querying player matches, competitions, and raw match statistics.
 */
class Player_Query_Service {
	private ?Player_Statistics_Repository_Interface $statistics_repository;
	private ?Competition_Repository_Interface $competition_repository;
	private ?Fixture_Repository_Interface $fixture_repository;
	private ?Event_Repository_Interface $event_repository;
	private ?Rubber_Repository_Interface $rubber_repository;

	public function __construct(
		?Player_Statistics_Repository_Interface $statistics_repository = null,
		?Competition_Repository_Interface $competition_repository = null,
		?Fixture_Repository_Interface $fixture_repository = null,
		?Event_Repository_Interface $event_repository = null,
		?Rubber_Repository_Interface $rubber_repository = null,
		private Event_Query_Service|\Closure|null $event_query_service = null
	) {
		$this->statistics_repository  = $statistics_repository;
		$this->competition_repository = $competition_repository;
		$this->fixture_repository     = $fixture_repository;
		$this->event_repository       = $event_repository;
		$this->rubber_repository      = $rubber_repository;
	}

	private function get_event_query_service(): Event_Query_Service {
		if ( $this->event_query_service instanceof \Closure ) {
			$this->event_query_service = ( $this->event_query_service )();
		}
		if ( null === $this->event_query_service ) {
			$this->event_query_service = new Event_Query_Service( $this->event_repository, $this->fixture_repository, $this->rubber_repository );
		}

		return $this->event_query_service;
	}

	private function get_statistics_repository(): Player_Statistics_Repository_Interface {
		if ( $this->statistics_repository ) {
			return $this->statistics_repository;
		}

		throw new RuntimeException( 'Player_Query_Service requires a Player_Statistics_Repository_Interface.' );
	}

	/**
	 * Normalise a legacy search argument (false / '' / null mean "no filter").
	 *
	 * @param mixed $value
	 * @return string|null
	 */
	private function filter_value( mixed $value ): ?string {
		if ( false === $value || null === $value || '' === $value ) {
			return null;
		}

		return (string) $value;
	}

	private function get_competition_repository(): Competition_Repository_Interface {
		if ( $this->competition_repository ) {
			return $this->competition_repository;
		}

		throw new RuntimeException( 'Player_Query_Service requires a Competition_Repository_Interface.' );
	}

	private function get_fixture_repository(): Fixture_Repository_Interface {
		if ( $this->fixture_repository ) {
			return $this->fixture_repository;
		}

		throw new RuntimeException( 'Player_Query_Service requires a Fixture_Repository_Interface.' );
	}

	/**
	 * Get matches for a player.
	 *
	 * @param Player $player
	 * @param object|null $grouping
	 * @param string|null $season
	 * @param string $match_source
	 * @param int|null $period
	 * @param string|null $type
	 * @return array
	 */
	public function get_matches( Player $player, ?object $grouping, ?string $season, string $match_source, ?int $period = null, ?string $type = null ): array {
		$statistics = array();
		if ( 'league' === $match_source ) {
			$league  = $grouping ? get_league( $grouping ) : null;
			$matches = $league ? \Racketmanager\get_league_query_service()->get_matches( $league,
				array(
					'season'    => $season,
					'player'    => $player->id,
					'match_day' => false,
					'final'     => 'all',
					'orderby'   => array(
						'date' => 'ASC',
					),
				)
			) : array();
		} elseif ( 'event' === $match_source ) {
			$event   = $grouping ? get_event( $grouping ) : null;
			$matches = $event ? $this->get_event_query_service()->get_matches(
				$event,
				array(
					'season'  => $season,
					'player'  => $player->id,
					'orderby' => array(
						'date'      => 'ASC',
						'league_id' => 'DESC',
					),
				)
			) : array();
		} elseif ( 'competition' === $match_source ) {
			$competition_repository = $this->get_competition_repository();
			if ( $grouping instanceof Competition ) {
				$competition = $grouping;
			} elseif ( $grouping ) {
				$competition = $competition_repository->find_by_id( (int) ( $grouping->id ?? $grouping ) );
			} else {
				$competition = null;
			}
			$matches     = $competition ? $competition_repository->find_matches(
				(int) $competition->get_id(),
				array(
					'season'  => $season,
					'player'  => $player->id,
					'orderby' => array(
						'date'      => 'ASC',
						'league_id' => 'DESC',
					),
				)
			) : array();
		} elseif ( 'all' === $match_source ) {
			$matches = $this->get_fixture_repository()->find_player_matches(
				(int) $player->id,
				array(
					'season'  => $season,
					'time'    => $period,
					'type'    => $type,
					'status'  => 'Y',
					'orderby' => array(
						'date' => 'ASC',
					),
				)
			);
		} else {
			$matches = array();
		}
		$opponents_pt = array( 'player1', 'player2' );
		$opponents    = array( 'home', 'away' );
		$stats_ref    = $statistics;
		$league_ref   = 0;
		foreach ( $matches as $match ) {
			$match_league = method_exists( $match, 'get_league' ) ? $match->get_league() : ( $match->league ?? null );
			if ( 'all' === $match_source ) {
				if ( ! empty( $match_league->is_championship ) ) {
					$league_no = 1;
				} else {
					$league_title = explode( ' ', $match_league->title ?? '' );
					$league_no    = end( $league_title );
				}
				$age_limit    = $match_league->event->age_limit ?? 'open';
				$rubber_order = empty( $match_league->event->reverse_rubbers ) ? 'true' : 'false';
				$league_ref   = $league_no . '-' . ( $match_league->num_rubbers ?? '' ) . '-' . $age_limit . '-' . $rubber_order;
			} else {
				$league_ref = 0;
			}
			if ( 'competition' === $match_source || 'event' === $match_source ) {
				$key = ( $match_league && isset( $match_league->title ) ) ? $match_league->title : ( ( $match_league && method_exists( $match_league, 'get_name' ) ) ? $match_league->get_name() : ( $match->league->title ?? '' ) );
				if ( false === array_key_exists( $key, $player->matches ) ) {
					$player->matches[ $key ]           = array();
					$player->matches[ $key ]['league'] = $match_league;
					if ( is_object( $player->matches[ $key ]['league'] ) ) {
						$player->matches[ $key ]['league']->season = $match->season ?? ( method_exists( $match, 'get_season' ) ? $match->get_season() : null );
					}
				}
				$player->matches[ $key ]['matches'][] = $match;
			} else {
				$player->matches[] = $match;
			}
			if ( ! empty( $match->rubbers ) && is_array( $match->rubbers ) ) {
				foreach ( $match->rubbers as $rubber ) {
					$player_team = null;
					$player_ref  = null;
					$winner       = null;
					$loser        = null;
					if ( ! empty( $rubber->winner_id ) ) {
						if ( (string) $rubber->winner_id === (string) $match->home_team ) {
							$winner = 'home';
							$loser  = 'away';
						} elseif ( (string) $rubber->winner_id === (string) $match->away_team ) {
							$winner = 'away';
							$loser  = 'home';
						}
					}
					$match_type          = strtolower( substr( $rubber->type ?? '', 1, 1 ) );
					$rubber_players      = array();
					$rubber_players['1'] = array();
					if ( 'd' === $match_type ) {
						$rubber_players['2'] = array();
					}
					foreach ( $opponents as $opponent ) {
						foreach ( $rubber_players as $p => $rubber_player ) {
							if ( isset( $rubber->players[ $opponent ][ $p ]->display_name ) && $rubber->players[ $opponent ][ $p ]->display_name === $player->display_name ) {
								$player_team = $opponent;
								if ( 'home' === $player_team ) {
									$player_ref = 'player1';
								} else {
									$player_ref = 'player2';
								}
								break 2;
							}
						}
					}
					if ( $winner === $player_team ) {
						$player_team_status = 'winner';
					} elseif ( $loser === $player_team ) {
						$player_team_status = 'loser';
					} else {
						$player_team_status = 'draw';
					}
					if ( ! isset( $stats_ref[ $league_ref ][ $rubber->title ]['played'][ $player_team_status ] ) ) {
						$stats_ref[ $league_ref ][ $rubber->title ]['played'][ $player_team_status ] = 0;
					}
					++$stats_ref[ $league_ref ][ $rubber->title ]['played'][ $player_team_status ];
					$sets = ! empty( $rubber->custom['sets'] ) ? $rubber->custom['sets'] : array();
					foreach ( $sets as $set ) {
						if ( isset( $set['player1'] ) && '' !== $set['player1'] && isset( $set['player2'] ) && '' !== $set['player2'] ) {
							if ( $set['player1'] > $set['player2'] ) {
								if ( 'player1' === $player_ref ) {
									$stat_ref = 'winner';
								} else {
									$stat_ref = 'loser';
								}
							} elseif ( 'player1' === $player_ref ) {
								$stat_ref = 'loser';
							} else {
								$stat_ref = 'winner';
							}
							if ( ! isset( $stats_ref[ $league_ref ][ $rubber->title ]['sets'][ $stat_ref ] ) ) {
								$stats_ref[ $league_ref ][ $rubber->title ]['sets'][ $stat_ref ] = 0;
							}
							++$stats_ref[ $league_ref ][ $rubber->title ]['sets'][ $stat_ref ];
							foreach ( $opponents_pt as $opponent ) {
								if ( isset( $set[ $opponent ] ) && is_numeric( $set[ $opponent ] ) ) {
									if ( $player_ref === $opponent ) {
										if ( ! isset( $stats_ref[ $league_ref ][ $rubber->title ]['games']['winner'] ) ) {
											$stats_ref[ $league_ref ][ $rubber->title ]['games']['winner'] = 0;
										}
										$stats_ref[ $league_ref ][ $rubber->title ]['games']['winner'] += (int) $set[ $opponent ];
									} else {
										if ( ! isset( $stats_ref[ $league_ref ][ $rubber->title ]['games']['loser'] ) ) {
											$stats_ref[ $league_ref ][ $rubber->title ]['games']['loser'] = 0;
										}
										$stats_ref[ $league_ref ][ $rubber->title ]['games']['loser'] += (int) $set[ $opponent ];
									}
								}
							}
						}
					}
				}
			}
		}
		if ( $league_ref ) {
			$player->statistics = $stats_ref;
		} else {
			$player->statistics = $stats_ref[0] ?? array();
		}
		return $player->matches;
	}

	/**
	 * Get competitions for player function.
	 *
	 * @param Player $player
	 * @param array|string $args search parameters.
	 * @param Competition_Service|null $competition_service
	 * @return array
	 */
	public function get_competitions( Player $player, array|string $args = array(), ?Competition_Service $competition_service = null ): array {
		$defaults     = array(
			'type'   => false,
			'season' => false,
		);
		$args         = array_merge( $defaults, (array) $args );
		$player_id    = (int) ( $player->id ?? $player->ID ?? 0 );
		$competitions = $this->get_statistics_repository()->find_competitions_played(
			$player_id,
			$this->filter_value( $args['type'] ),
			$this->filter_value( $args['season'] )
		);
		$i            = 0;
		foreach ( $competitions as $competition ) {
			if ( $competition_service ) {
				$competition_dtl = $competition_service->get_competition( (int) $competition->id );
			} else {
				$competition_dtl = $this->get_competition_repository()->find_by_id( (int) $competition->id );
			}
			if ( $competition_dtl ) {
				$competition_dtl->season = (int) $competition->season;
				$competition_season      = $competition_dtl->seasons->get( (string) $competition->season );
				if ( null !== $competition_season ) {
					$competition_dtl->date_start = $competition_season['date_start'] ?? null;
					$competition_dtl->date_end   = $competition_season['date_end'] ?? null;
					$competitions[ $i ]          = $competition_dtl;
				} else {
					unset( $competitions [ $i ] );
				}
			}
			++$i;
		}
		return array_values( $competitions );
	}

	/**
	 * Get stats for teams for player function
	 *
	 * @param Player $player
	 * @param array|string $args search parameters.
	 * @return array
	 */
	public function get_stats_teams( Player $player, array|string $args = array() ): array {
		$defaults  = array(
			'season' => false,
		);
		$args      = array_merge( $defaults, (array) $args );
		$player_id = (int) ( $player->ID ?? $player->id ?? 0 );

		return $this->get_statistics_repository()->find_team_stats( $player_id, $this->filter_value( $args['season'] ) );
	}

	/**
	 * Get stats for players for player function
	 *
	 * @param Player $player
	 * @param array|string $args search parameters.
	 * @return array
	 */
	public function get_stats_players( Player $player, array|string $args = array() ): array {
		$defaults  = array(
			'season' => false,
		);
		$args      = array_merge( $defaults, (array) $args );
		$player_id = (int) ( $player->ID ?? $player->id ?? 0 );

		return $this->get_statistics_repository()->find_player_stats( $player_id, $this->filter_value( $args['season'] ) );
	}
}
