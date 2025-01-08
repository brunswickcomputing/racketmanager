<?php
/**
 * Championship object
 *
 * @package Racketmanager/Classes
 */

namespace Racketmanager;

/**
* Implement Championship mode
*
* @author   Kolja Schleich
* @author  Paul Moffat
* @package  RacketManager
* @subpackage Racketmanager_Championship
*/
final class Racketmanager_Championship extends RacketManager {
	/**
	 * League ID
	 *
	 * @var int
	 */
	public $league_id = 0;

	/**
	 * Preliminary groups
	 *
	 * @var array
	 */
	public $groups = array();

	/**
	 * Number of preliminary groups
	 *
	 * @var int
	 */
	public $num_group = 0;

	/**
	 * Number of teams per group
	 *
	 * @var int
	 */
	public $team_per_group = 0;

	/**
	 * Number of teams to advance to final rounds
	 *
	 * @var int
	 */
	public $num_advance = 0;

	/**
	 * Number of final rounds
	 *
	 * @var int
	 */
	public $num_rounds = 0;

	/**
	 * Number of teams in first round
	 *
	 * @var int
	 */
	public $num_teams_first_round = 0;

	/**
	 * Final keys indexed by round
	 *
	 * @var array
	 */
	private $keys = array();

	/**
	 * Finals indexed by key
	 *
	 * @var array
	 */
	public $finals = array();

	/**
	 * Current final key
	 *
	 * @var array
	 */
	public $current_final = '';

	/**
	 * Array of final team names
	 *
	 * @var array
	 */
	public $final_teams = array();

	/**
	 * Image of cup icon
	 *
	 * @var string
	 */
	public $cup_icon = '';
	/**
	 * Number of teams per group
	 *
	 * @var int
	 */
	public $teams_per_group;
	/**
	 * Number of groups
	 *
	 * @var int
	 */
	public $num_groups;
	/**
	 * Number of teams
	 *
	 * @var int
	 */
	public $num_teams;
	/**
	 * Is consolation
	 *
	 * @var boolean
	 */
	public $is_consolation;
	/**
	 * Number of seeds
	 *
	 * @var int
	 */
	public $num_seeds;
	/**
	 * Initialize Championship Mode
	 *
	 * @param object $league league object.
	 * @param array  $settings array of settings.
	 */
	public function __construct( $league, $settings ) {
		$this->league_id      = $league->id;
		$this->is_consolation = false;
		if ( ! empty( $league->event->primary_league ) && $this->league_id !== $league->event->primary_league ) {
			$this->is_consolation = true;
		}
		if ( isset( $settings['groups'] ) && is_array( $settings['groups'] ) ) {
			$this->groups = $settings['groups'];
		}
		$this->teams_per_group = isset( $settings['teams_per_group'] ) ? intval( $settings['teams_per_group'] ) : 4;
		$this->num_groups      = count( $this->groups );
		if ( $this->num_groups > 0 ) {
			$this->num_advance           = isset( $settings['num_advance'] ) ? $settings['num_advance'] : 0;
			$this->num_teams_first_round = $this->num_groups * $this->num_advance;
			$this->num_rounds            = log( $this->num_teams_first_round, 2 );
		} else {
			$num_teams       = $league->num_teams_total;
			$this->num_teams = $num_teams;
			if ( $this->is_consolation ) {
				$primary_league              = get_league( $league->event->primary_league );
				$this->num_teams             = 0;
				$this->num_rounds            = $primary_league->championship->num_rounds - 1;
				$this->num_teams_first_round = pow( 2, $this->num_rounds );
				$this->num_advance           = $this->num_teams_first_round;
			} else {
				$completed_matches = $league->get_matches(
					array(
						'final'            => true,
						'count'            => true,
						'season'           => $league->current_season['name'],
						'reset_query_args' => true,
					)
				);
				$this->num_advance = pow( 2, $league->current_season['num_match_days'] );
				if ( $league->event->competition->is_active && $num_teams ) {
					$use_teams = true;
				} elseif ( $num_teams > $this->num_advance ) {
					$use_teams = true;
				} else {
					$use_teams = false;
				}
				if ( $use_teams ) {
					$this->num_rounds            = ceil( log( $num_teams, 2 ) );
					$this->num_teams_first_round = pow( 2, $this->num_rounds );
				} else {
					$this->num_teams_first_round = $this->num_advance;
					$this->num_rounds            = $league->current_season['num_match_days'];
				}
			}
		}
		if ( $this->is_consolation ) {
			$this->num_seeds = 0;
		} elseif ( $league->num_teams_total <= 10 ) {
			$this->num_seeds = 2;
		} elseif ( $league->num_teams_total <= 20 ) {
			$this->num_seeds = 4;
		} elseif ( $league->num_teams_total <= 40 ) {
			$this->num_seeds = 8;
		} elseif ( $league->num_teams_total <= 80 ) {
			$this->num_seeds = 16;
		} elseif ( $league->num_teams_total <= 132 ) {
			$this->num_seeds = 32;
		} else {
			$this->num_seeds = 0;
		}
		$num_teams = 2;
		$i         = $this->num_rounds;
		while ( $num_teams <= $this->num_teams_first_round ) {
			$finalkey                  = $this->get_final_key( $num_teams );
			$num_matches               = $num_teams / 2;
			$is_final                  = ( 'final' === $finalkey ) ? true : false;
			$this->finals[ $finalkey ] = array(
				'key'         => $finalkey,
				'is_final'    => $is_final,
				'name'        => $this->get_final_name( $finalkey ),
				'num_matches' => $num_matches,
				'num_teams'   => $num_teams,
				'colspan'     => ( $this->num_teams_first_round / 2 >= 4 ) ? ceil( 4 / $num_matches ) : ceil( ( $this->num_teams_first_round / 2 ) / $num_matches ),
				'round'       => $i,
			);

			// Separately add match for third place.
			if ( 2 === $num_teams && ( isset( $settings['match_place3'] ) && 1 === $settings['match_place3'] ) ) {
				$finalkey                  = 'third';
				$this->finals[ $finalkey ] = array(
					'key'         => $finalkey,
					'name'        => $this->get_final_name( $finalkey ),
					'num_matches' => $num_matches,
					'num_teams'   => $num_teams,
					'colspan'     => ( $this->num_teams_first_round / 2 >= 4 ) ? ceil( 4 / $num_matches ) : ceil( ( $this->num_teams_first_round / 2 ) / $num_matches ),
					'round'       => $i,
				);
			}

			$this->keys[ $i ] = $finalkey;

			--$i;
			$num_teams = $num_teams * 2;
		}
		$this->set_current_final();
		$this->set_final_teams();

		$this->cup_icon = '<img style="vertical-align: middle;" src="' . RACKETMANAGER_URL . 'admin/icons/cup.png" />';
	}
	/**
	 * Get groups
	 *
	 * @return array
	 */
	public function get_groups() {
		return $this->groups;
	}

	/**
	 * Get final key
	 *
	 * @param int $round round name.
	 * @return string
	 */
	public function get_final_keys( $round = false ) {
		if ( $round ) {
			if ( isset( $this->keys[ $round ] ) ) {
				return $this->keys[ $round ];
			} else {
				return false;
			}
		} else {
			return $this->keys;
		}
	}

	/**
	 * Get final data
	 *
	 * @param int $key final key.
	 * @return mixed
	 */
	public function get_finals( $key = false ) {
		if ( 'current' === $key ) {
			$key = $this->current_final;
		}
		if ( $key ) {
			return $this->finals[ $key ];
		} else {
			return $this->finals;
		}
	}

	/**
	 * Get name of final depending on number of teams
	 *
	 * @param string $key final key.
	 * @return the name of the round
	 */
	public function get_final_name( $key = false ) {
		if ( empty( $key ) ) {
			$key = $this->current_final;
		}
		if ( ! empty( $key ) ) {
			if ( 'final' === $key ) {
				$round = __( 'Final', 'racketmanager' );
			} elseif ( 'third' === $key ) {
				$round = __( 'Third Place', 'racketmanager' );
			} elseif ( 'semi' === $key ) {
				$round = __( 'Semi Final', 'racketmanager' );
			} elseif ( 'quarter' === $key ) {
				$round = __( 'Quarter Final', 'racketmanager' );
			} else {
				$tmp = explode( '-', $key );
				/* translators: %d: round number of teams in round */
				$round = sprintf( __( 'Round of %d', 'racketmanager' ), $tmp[1] );
			}
			return $round;
		}
	}

	/**
	 * Get key of final depending on number of teams
	 *
	 * @param int $num_teams number of teams in round.
	 * @return string key
	 */
	private function get_final_key( $num_teams ) {
		if ( 2 === $num_teams ) {
			$key = 'final';
		} elseif ( 4 === $num_teams ) {
			$key = 'semi';
		} elseif ( 8 === $num_teams ) {
			$key = 'quarter';
		} else {
			$key = 'last-' . $num_teams;
		}
		return $key;
	}

	/**
	 * Set current final key
	 *
	 * @param string $final_round final reference.
	 */
	private function set_current_final( $final_round = false ) {
		if ( isset( $_GET['final'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$key = sanitize_text_field( wp_unslash( $_GET['final'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( $final_round ) {
			$key = htmlspecialchars( $final_round );
		} else {
			$key = $this->get_final_keys( 1 );
		}
		$this->current_final = $key;
	}

	/**
	 * Get current final key
	 *
	 * @return string
	 */
	public function get_current_final_key() {
		return $this->current_final;
	}

	/**
	 * Set general names for final rounds
	 */
	private function set_final_teams() {
		// Final Rounds.
		foreach ( $this->get_finals() as $k => $data ) {
			$this->final_teams[ $k ] = array();

			if ( $data['round'] > 1 ) {
				// get data of previous round.
				$final = $this->get_finals( $this->get_final_keys( $data['round'] - 1 ) );
				if ( isset( $final['num_matches'] ) ) {
					for ( $x = 1; $x <= $final['num_matches']; $x++ ) {
						if ( 'third' === $k ) {
							/* translators: %1$s: round %2$d: match */
							$title = sprintf( __( 'Loser %1$s %2$d', 'racketmanager' ), $final['name'], $x );
							$key   = '2_' . $final['key'] . '_' . $x;
						} else {
							/* translators: %1$s: round %2$d: match */
							$title = sprintf( __( 'Winner %1$s %2$d', 'racketmanager' ), $final['name'], $x );
							$key   = '1_' . $final['key'] . '_' . $x;
						}

						$this->final_teams[ $k ][ $key ] = (object) array(
							'id'    => $key,
							'title' => $title,
							'home'  => 0,
						);
					}
				}
			} elseif ( ! empty( $this->groups ) ) {
				foreach ( $this->groups as $group ) {
					for ( $a = 1; $a <= $this->num_advance; $a++ ) {
						$this->final_teams[ $k ][ $a . '_' . $group ] = (object) array(
							'id'    => $a . '_' . $group,
							/* translators: %1$d: team rank %2$s: group */
							'title' => sprintf( __( '%1$d. Group %2$s', 'racketmanager' ), $a, $group ),
							'home'  => 0,
						);
					}
				}
			} else {
				$num_teams = $this->num_teams_first_round;
				for ( $a = 1; $a <= $num_teams; $a++ ) {
					$this->final_teams[ $k ][ $a . '_' ] = (object) array(
						'id'    => $a . '_',
						/* translators: $d: rank number */
						'title' => sprintf( __( 'Team Rank %d', 'racketmanager' ), $a ),
						'home'  => 0,
					);
				}
			}
		}
	}

	/**
	 * Get final team names
	 *
	 * @param string $final_round final reference.
	 * @return array
	 */
	public function get_final_teams( $final_round ) {
		if ( isset( $this->final_teams[ $final_round ] ) ) {
			return $this->final_teams[ $final_round ];
		} else {
			return null;
		}
	}

	/**
	 * Update final rounds results
	 *
	 * @param array  $matches array of matches.
	 * @param array  $home_points home points.
	 * @param array  $away_points away points.
	 * @param array  $custom custom.
	 * @param int    $round round.
	 * @param string $season season.
	 */
	public function update_final_results( $matches, $home_points, $away_points, $custom, $round, $season ) {
		global $racketmanager;

		$league = get_league( $this->league_id );
		$league->set_finals( true );
		$num_matches = $league->update_match_results( $matches, $home_points, $away_points, $custom, $season, $round );

		if ( $round < $this->num_rounds ) {
			$this->proceed( $this->get_final_keys( $round ), $this->get_final_keys( $round + 1 ), $round );
		}
		/* translators: %d: number of matches */
		$racketmanager->set_message( sprintf( __( 'Updated Results of %d matches', 'racketmanager' ), $num_matches ) );
	}

	/**
	 * Start final rounds
	 */
	private function start_final_rounds() {
		$updates = false;
		if ( is_admin() && current_user_can( 'update_results' ) ) {
			$league        = get_league( $this->league_id );
			$multiple_legs = false;
			$round_name    = $this->get_final_keys( 1 );
			$match_args    = array(
				'final'            => $round_name,
				'limit'            => false,
				'match_day'        => -1,
				'reset_query_args' => true,
			);
			// get first round matches.
			if ( ! empty( $league->current_season['homeAway'] ) && ( 'true' === $league->current_season['homeAway'] || true === $league->current_season['homeAway'] ) ) {
				$multiple_legs     = true;
				$match_args['leg'] = 1;
			}
			$matches_list = array();
			$matches      = $league->get_matches( $match_args );
			foreach ( $matches as $match ) {
				$matches_list[] = $match->id;
				if ( '-1' === $match->home_team ) {
					$home['team'] = -1;
					$home_team    = array( 'id' => -1 );
				} elseif ( strpos( $match->home_team, '_' ) !== false ) {
					$home      = explode( '_', $match->home_team );
					$home      = array(
						'rank'  => $home[0],
						'group' => isset( $home[1] ) ? $home[1] : '',
					);
					$home_team = $league->get_league_teams(
						array(
							'rank'             => $home['rank'],
							'group'            => $home['group'],
							'reset_query_args' => true,
						)
					);
					if ( $home_team ) {
						$home['team']         = $home_team[0]->id;
						$match->home_team     = $home['team'];
						$match->teams['home'] = $league->get_team_dtls( $home_team[0]->id );
					} else {
						$home['team'] = -1;
						$home_team    = array( 'id' => -1 );
					}
				} else {
					$home_team = '';
				}
				if ( '-1' === $match->away_team ) {
					$away['team'] = -1;
					$away_team    = array( 'id' => -1 );
				} elseif ( strpos( $match->away_team, '_' ) !== false ) {
					$away      = explode( '_', $match->away_team );
					$away      = array(
						'rank'  => $away[0],
						'group' => isset( $away[1] ) ? $away[1] : '',
					);
					$away_team = $league->get_league_teams(
						array(
							'rank'             => $away['rank'],
							'group'            => $away['group'],
							'reset_query_args' => true,
						)
					);
					if ( $away_team ) {
						$away['team']         = $away_team[0]->id;
						$match->away_team     = $away['team'];
						$match->teams['away'] = $league->get_team_dtls( $away_team[0]->id );
					} else {
						$away['team'] = -1;
						$away_team    = array( 'id' => -1 );
					}
				} else {
					$away_team = '';
				}
				if ( $home_team && $away_team ) {
					$this->set_teams( $match, $home['team'], $away['team'] );
					$updates = true;
				}
			}
			if ( $matches_list ) {
				if ( $multiple_legs ) {
					foreach ( $matches_list as $match_id ) {
						$match = get_match( $match_id );
						if ( $match ) {
							if ( $match->linked_match ) {
								$matches_list[] = $match->linked_match;
							}
						}
					}
				}
				$this->update_final_results( $matches_list, array(), array(), array(), 1, $league->current_season );
			}
		}
		return $updates;
	}
	/**
	 * Set teams for match function
	 *
	 * @param object $match match object.
	 * @param object $home home team array.
	 * @param object $away away team array.
	 * @return void
	 */
	private function set_teams( $match, $home, $away ) {
		$match = get_match( $match );
		$match = $match->set_teams( $home, $away );
		if ( is_numeric( $match->home_team ) && is_numeric( $match->away_team ) ) {
			$match->notify_next_match_teams();
		}
		if ( ! empty( $match->linked_match ) ) {
			$linked_match = get_match( $match->linked_match );
			$linked_match = $linked_match->set_teams( $home, $away );
			if ( is_numeric( $linked_match->home_team ) && is_numeric( $linked_match->away_team ) ) {
				$linked_match->notify_next_match_teams();
			}
		}
	}
	/**
	 * Proceed to next final round
	 *
	 * @param string $current current round name.
	 * @param string $next next round name.
	 * @param int    $round round number.
	 * @return void
	 */
	private function proceed( $current, $next, $round ) {
		$legs       = false;
		$league     = get_league( $this->league_id );
		$match_args = array(
			'final' => $next,
			'limit' => false,
		);
		if ( ! empty( $league->current_season['homeAway'] ) ) {
			$legs = true;
			if ( 'final' !== $next ) {
				$match_args['leg'] = 1;
			}
		}
		$matches = $league->get_matches( $match_args );
		foreach ( $matches as $match ) {
			$update = true;
			$home   = explode( '_', $match->home_team );
			$away   = explode( '_', $match->away_team );
			if ( is_array( $home ) && is_array( $away ) ) {
				if ( $legs ) {
					$winner_col = 'winner_id_tie';
					$loser_col  = 'loser_id_tie';
				} else {
					$winner_col = 'winner_id';
					$loser_col  = 'loser_id';
				}
				if ( isset( $home[1] ) ) {
					$col  = ( '1' === $home[0] ) ? $winner_col : $loser_col;
					$home = array(
						'col'      => $col,
						'finalkey' => $home[1],
						'no'       => $home[2],
					);
				} else {
					$home['no'] = 0;
				}
				if ( isset( $away[1] ) ) {
					$col  = ( '1' === $away[0] ) ? $winner_col : $loser_col;
					$away = array(
						'col'      => $col,
						'finalkey' => $away[1],
						'no'       => $away[2],
					);
				} else {
					$away['no'] = 0;
				}
				// get matches of current round.
				$match_args = array(
					'final'   => $current,
					'limit'   => false,
					'orderby' => array(
						'id' => 'ASC',
					),
				);
				if ( $legs ) {
					$match_args['leg'] = 2;
				}
				$prev      = $league->get_matches( $match_args );
				$home_team = 0;
				$away_team = 0;
				if ( isset( $prev[ $home['no'] - 1 ] ) ) {
					$prev_home = $prev[ $home['no'] - 1 ];
					$home_team = $prev_home->{$home['col']};
				}
				if ( isset( $prev[ $away['no'] - 1 ] ) ) {
					$prev_away = $prev[ $away['no'] - 1 ];
					$away_team = $prev_away->{$away['col']};
				}
				if ( empty( $home_team ) && empty( $away_team ) ) {
					$update = false;
				}
				if ( $update ) {
					$this->set_teams( $match, $home_team, $away_team );
					if ( isset( $league->event->primary_league ) && $league->event->primary_league === $league->id ) {
						if ( $round < 3 ) {
							if ( ! empty( $prev_home ) ) {
								$this->set_consolation_team( $prev_home, $current, $league );
							}
							if ( ! empty( $prev_away ) ) {
								$this->set_consolation_team( $prev_away, $current, $league );
							}
						}
					}
					// Set winners on final.
					if ( 'third' === $next ) {
						$match     = $league->get_matches(
							array(
								'final'   => 'final',
								'limit'   => false,
								'orderby' => array(
									'id' => 'ASC',
								),
							)
						);
						$match     = $match[0];
						$home_team = $prev_home->loser_id;
						$away_team = $prev_away->loser_id;
						$match->set_teams( $home_team, $away_team );
					}
				}
			}
		}
	}
	/**
	 * Set consolation teams function
	 *
	 * @param object $match match.
	 * @param string $round round name.
	 * @param object $league league.
	 * @return void
	 */
	private function set_consolation_team( $match, $round, $league ) {
		if ( empty( $match->loser_id ) ) {
			return;
		}
		if ( $match->is_walkover ) {
			$team_switch = '-1';
		} else {
			$team_switch                     = $match->loser_id;
			$match_array                     = array();
			$match_array['team_id']          = $match->loser_id;
			$match_array['final']            = 'all';
			$match_array['reset_query_args'] = true;
			$matches                         = $league->get_matches( $match_array );
			if ( count( $matches ) === 2 ) {
				if ( $matches[0]->id === $match->id ) {
					$first_match = $matches[1];
				} else {
					$first_match = $matches[0];
				}
				if ( '-1' !== $first_match->home_team && '-1' !== $first_match->away_team ) {
					$team_switch = '-1';
				}
			}
		}
		$team_ref = '2_' . $round . '_' . $match->id;
		$event    = get_event( $league->event->id );
		if ( $event ) {
			$event_leagues = $event->get_leagues( array( 'consolation' => true ) );
			if ( $event_leagues ) {
				foreach ( $event_leagues as $event_league ) {
					$consolation_league = get_league( $event_league );
					if ( '-1' !== $team_switch ) {
						$switch_teams = $consolation_league->get_league_teams(
							array(
								'team_id'          => $team_switch,
								'reset_query_args' => true,
							)
						);
						if ( ! $switch_teams ) {
							$consolation_league->add_team( $team_switch, $consolation_league->current_season['name'] );
						}
					}
					$consolation_teams = $consolation_league->get_league_teams(
						array(
							'team_name'        => $team_ref,
							'reset_query_args' => true,
						)
					);
					if ( $consolation_teams ) {
						$consolation_team    = $consolation_teams[0];
						$consolation_matches = $consolation_league->get_matches(
							array(
								'team_id' => $consolation_team->id,
								'final'   => 'all',
							)
						);
						if ( $consolation_matches ) {
							foreach ( $consolation_matches as $consolation_match ) {
								if ( $consolation_match->home_team === $consolation_team->id ) {
									$this->set_teams( $consolation_match, $team_switch, null );
								} elseif ( $consolation_match->away_team === $consolation_team->id ) {
									$this->set_teams( $consolation_match, null, $team_switch );
								}
							}
						}
					}
				}
			}
		}
	}
	/**
	 * Handle administration panel
	 *
	 * @param object $league league object.
	 */
	public function handle_admin_page( $league = null ) {
		global $racketmanager, $tab;
		$league = get_league( $league );
		$season = $league->get_season();
		if ( isset( $_POST['action'] ) ) {
			$action = sanitize_text_field( wp_unslash( $_POST['action'] ) );
			if ( 'startFinals' === $action ) {
				if ( isset( $_POST['racketmanager_proceed_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_proceed_nonce'] ) ), 'racketmanager_championship_proceed' ) ) {
					if ( current_user_can( 'update_results' ) ) {
						$updates = $this->start_final_rounds( $league->id );
						if ( $updates ) {
							$racketmanager->set_message( __( 'First round started', 'racketmanager' ) );
						} else {
							$racketmanager->set_message( __( 'First round not started', 'racketmanager' ), true );
						}
						$tab = 'finalresults'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					} else {
						$racketmanager->set_message( __( 'You do not have sufficient permissions to access this page.', 'racketmanager' ), true );
					}
				} else {
					$racketmanager->set_message( __( 'Security token invalid', 'racketmanager' ), true );
				}
				$racketmanager->printMessage();
			} elseif ( 'updateFinalResults' === $action ) {
				if ( isset( $_POST['racketmanager_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_update-finals' ) ) {
					if ( current_user_can( 'update_results' ) ) {
						$custom      = isset( $_POST['custom'] ) ? $_POST['custom'] : array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
						$matches     = isset( $_POST['matches'] ) ? $_POST['matches'] : array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
						$home_points = isset( $_POST['home_points'] ) ? $_POST['home_points'] : array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
						$away_points = isset( $_POST['away_points'] ) ? $_POST['away_points'] : array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
						$round       = isset( $_POST['round'] ) ? intval( $_POST['round'] ) : null;
						$season      = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
						$this->update_final_results( $matches, $home_points, $away_points, $custom, $round, $season );
					} else {
						$racketmanager->set_message( __( 'You do not have sufficient permissions to access this page.', 'racketmanager' ), true );
					}
				} else {
					$racketmanager->set_message( __( 'Security token invalid', 'racketmanager' ), true );
				}
			}
			$racketmanager->printMessage();
		}
		$class = 'alternate';
		if ( count( $this->groups ) > 0 ) {
			$league->set_group( $this->groups[0] );
		}

		$tab = 'finalresults'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( empty( $tab ) && isset( $_REQUEST['league-tab'] ) ) {
			$tab = sanitize_text_field( wp_unslash( $_REQUEST['league-tab'] ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
		if ( isset( $_REQUEST['final'] ) ) {
			$final = sanitize_text_field( wp_unslash( $_REQUEST['final'] ) );
		}
		return $tab;
	}
	/**
	 * Display administration panel
	 */
	public function display_admin_page() {
		global $racketmanager, $league, $season, $tab;

		if ( ! is_admin() || ! current_user_can( 'view_leagues' ) ) {
			$racketmanager->set_message( __( 'You do not have sufficient permissions to access this page.', 'racketmanager' ), true );
			$racketmanager->printMessage();
			return;
		}
		$league = get_league( $league );
		$this->handle_admin_page( $league );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		include_once RACKETMANAGER_PATH . 'admin/championship.php';
	}
}
