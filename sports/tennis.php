<?php
/**
 * League_Tennis API: League_Tennis class
 *
 * @author Kolja Schleich
 * @package RacketManager
 * @subpackage League_Tennis
 */

add_filter( 'racketmanager_sports', 'racketmanager_sports_tennis' );
/**
 * add tennis to list
 *
 * @param array $sports
 * @return array
 */
function racketmanager_sports_tennis( $sports ) {
	$sports['tennis'] = __( 'Tennis', 'racketmanager' );
	return $sports;
}

class Competition_Tennis extends Competition {

	/**
	 * sports key
	 *
	 * @var string
	 */
	public $sport = 'tennis';

	/**
	 * default number of sets
	 *
	 * @var int
	 */
	public $num_sets = 3;

	/**
	 * default scoring
	 *
	 * @var int
	 */
	public $scoring = 'TB';

	/**
	 * load specific settings
	 *
	 * @param none
	 * @return void
	 */
	public function __construct( $competition ) {
		$this->fields_team['sets_won']      = array( 'label' => __( 'Sets Won', 'racketmanager' ) );
		$this->fields_team['sets_allowed']  = array( 'label' => __( 'Sets Lost', 'racketmanager' ) );
		$this->fields_team['sets_shared']   = array( 'label' => __( 'Sets Shared', 'racketmanager' ) );
		$this->fields_team['straight_set']  = array(
			'label' => __( 'Straight Set', 'racketmanager' ),
			'keys'  => array( 'win', 'lost' ),
		);
		$this->fields_team['split_set']     = array(
			'label' => __( 'Split Set', 'racketmanager' ),
			'keys'  => array( 'win', 'lost' ),
		);
		$this->fields_team['games_won']     = array( 'label' => __( 'Games Won', 'racketmanager' ) );
		$this->fields_team['games_allowed'] = array( 'label' => __( 'Games Lost', 'racketmanager' ) );

		parent::__construct( $competition );

		add_filter( 'racketmanager_point_rules_list', array( &$this, 'getPointRuleList' ) );
		add_filter( 'racketmanager_point_rules', array( &$this, 'getPointRules' ) );

		add_action( 'competition_settings_' . $this->sport, array( &$this, 'competitionSettings' ) );
	}
	/**
	 * get Point Rule list
	 *
	 * @param array $rules
	 * @return array
	 */
	public function getPointRuleList( $rules ) {
		$rules['tennis']       = __( 'Tennis', 'racketmanager' );
		$rules['tennisSummer'] = __( 'Tennis Summer', 'racketmanager' );

		return $rules;
	}

	/**
	 * get Point rules
	 *
	 * @param array $rules
	 * @return array
	 */
	public function getPointRules( $rules ) {
		$rules['tennis']       = array(
			'forwin'        => 1,
			'fordraw'       => 0,
			'forloss'       => 0,
			'forwin_split'  => 0,
			'forloss_split' => 0,
			'forshare'      => 0.5,
		);
		$rules['tennisSummer'] = array(
			'forwin'        => 0,
			'fordraw'       => 0,
			'forloss'       => 0,
			'forwin_split'  => 0,
			'forloss_split' => 0,
			'forshare'      => 0.5,
		);

		return $rules;
	}

	/**
	 * add league settings
	 *
	 * @param object $league
	 * @return void
	 */
	public function competitionSettings( $competition ) {

		$competition->num_sets    = isset( $competition->num_sets ) ? $competition->num_sets : '';
		$competition->num_rubbers = isset( $competition->num_rubbers ) ? $competition->num_rubbers : '';
		$competition->type        = isset( $competition->type ) ? $competition->type : '';
		$competition->scoring     = isset( $competition->scoring ) ? $competition->scoring : 'TB';
		$competition->offset      = isset( $competition->offset ) ? $competition->offset : '0';

		?>
		<div class="form-floating mb-3 col-12 col-xl-2">
			<input class="form-control" type='number' name='settings[num_sets]' id='num_sets' value='<?php echo $competition->num_sets; ?>' size='3' />
			<label for='num_sets'><?php _e( 'Number of Sets', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3 col-12 col-xl-2">
			<input class="form-control" type='number' name='settings[num_rubbers]' id='num_rubbers' value='<?php echo $competition->num_rubbers; ?>' size='3' />
			<label for='num_rubbers'><?php _e( 'Number of Rubbers', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3 col-12 col-xl-2">
			<select class="form-select" size='1' name='settings[competition_type]' id='competition_type'>
				<option value='WS' <?php echo $competition->type == 'WS' ? 'selected' : ''; ?>><?php _e( 'Ladies Singles', 'racketmanager' ); ?></option>
				<option value='WD' <?php echo $competition->type == 'WD' ? 'selected' : ''; ?>><?php _e( 'Ladies Doubles', 'racketmanager' ); ?></option>
				<option value='MS' <?php echo $competition->type == 'MS' ? 'selected' : ''; ?>><?php _e( 'Mens Singles', 'racketmanager' ); ?></option>
				<option value='MD' <?php echo $competition->type == 'MD' ? 'selected' : ''; ?>><?php _e( 'Mens Doubles', 'racketmanager' ); ?></option>
				<option value='XD' <?php echo $competition->type == 'XD' ? 'selected' : ''; ?>><?php _e( 'Mixed Doubles', 'racketmanager' ); ?></option>
				<option value='LD' <?php echo $competition->type == 'LD' ? 'selected' : ''; ?>><?php _e( 'The League', 'racketmanager' ); ?></option>
			</select>
			<label for='competition_type'><?php _e( 'Type', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3 col-12 col-xl-2">
			<select class="form-select" size='1' name='settings[scoring]' id='scoring'>
				<option value='F4' <?php echo $competition->scoring == 'F4' ? 'selected' : ''; ?>><?php _e( 'Fast 4', 'racketmanager' ); ?></option>
				<option value='FM' <?php echo $competition->scoring == 'FM' ? 'selected' : ''; ?>><?php _e( 'Fast 4 with match tie break', 'racketmanager' ); ?></option>
				<option value='PR' <?php echo $competition->scoring == 'PR' ? 'selected' : ''; ?>><?php _e( 'Pro', 'racketmanager' ); ?></option>
				<option value='TB' <?php echo $competition->scoring == 'TB' ? 'selected' : ''; ?>><?php _e( 'Tie break', 'racketmanager' ); ?></option>
				<option value='TM' <?php echo $competition->scoring == 'TM' ? 'selected' : ''; ?>><?php _e( 'Tie break with match tie break', 'racketmanager' ); ?></option>
				<option value='TP' <?php echo $competition->scoring == 'TP' ? 'selected' : ''; ?>><?php _e( 'Tie break with tie break playoff', 'racketmanager' ); ?></option>
				<option value='MP' <?php echo $competition->scoring == 'MP' ? 'selected' : ''; ?>><?php _e( 'Tie break with match tie break playoff', 'racketmanager' ); ?></option>
			</select>
			<label for='scoring'><?php _e( 'Scoring Format', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3 col-12 col-xl-2">
			<select class="form-select" size='1' name='settings[offset]' id='offset'>
				<option value='0' <?php echo $competition->offset == '0' ? 'selected' : ''; ?>><?php _e( 'Week 0', 'racketmanager' ); ?></option>
				<option value='1' <?php echo $competition->offset == '1' ? 'selected' : ''; ?>><?php _e( 'Week 1', 'racketmanager' ); ?></option>
				<option value='2' <?php echo $competition->offset == '2' ? 'selected' : ''; ?>><?php _e( 'Week 2', 'racketmanager' ); ?></option>
			</select>
			<label for='offset'><?php _e( 'Offset week', 'racketmanager' ); ?></label>
		</div>

	<?php }

}

class League_Tennis extends League {

	/**
	 * sports key
	 *
	 * @var string
	 */
	public $sport = 'tennis';

	/**
	 * default number of sets
	 *
	 * @var int
	 */
	public $num_sets = 3;

	/**
	 * load specific settings
	 *
	 * @param none
	 * @return void
	 */
	public function __construct( $league ) {
		$this->fields_team['sets_won']      = array( 'label' => __( 'Sets Won', 'racketmanager' ) );
		$this->fields_team['sets_allowed']  = array( 'label' => __( 'Sets Lost', 'racketmanager' ) );
		$this->fields_team['sets_shared']   = array( 'label' => __( 'Sets Shared', 'racketmanager' ) );
		$this->fields_team['straight_set']  = array(
			'label' => __( 'Straight Set', 'racketmanager' ),
			'keys'  => array( 'win', 'lost' ),
		);
		$this->fields_team['split_set']     = array(
			'label' => __( 'Split Set', 'racketmanager' ),
			'keys'  => array( 'win', 'lost' ),
		);
		$this->fields_team['games_won']     = array( 'label' => __( 'Games Won', 'racketmanager' ) );
		$this->fields_team['games_allowed'] = array( 'label' => __( 'Games Lost', 'racketmanager' ) );

		parent::__construct( $league );

		add_filter( 'team_points_' . $this->sport, array( &$this, 'calculatePoints' ), 10, 4 );

		add_filter( 'racketmanager_matchtitle_' . $this->sport, array( &$this, 'matchTitle' ), 10, 3 );

		add_action( 'matchtable_header_' . $this->sport, array( &$this, 'displayMatchesHeader' ), 10, 0 );
		add_action( 'matchtable_columns_' . $this->sport, array( &$this, 'displayMatchesColumns' ) );
	}

	/**
	 * calculate Points: add match score
	 *
	 * @param array $points
	 * @param int   $team_id
	 * @param array $rule
	 * @param array $matches
	 * @return array
	 */
	public function calculatePoints( $points, $team_id, $rule, $matches ) {
		global $racketmanager;

		extract( $rule );
		$pointOptions    = $racketmanager->getOptions( 'player' );
		$data            = $this->getStandingsData( $team_id, array(), $matches );
		$points['plus']  = $data['sets_won'] + $data['straight_set']['win'] * $forwin + $data['split_set']['win'] * $forwin_split + $data['split_set']['lost'] * $forloss_split + $data['sets_shared'] * $forshare - $data['no_player'] * $pointOptions['walkover']['rubber'] - $data['no_team'] * $pointOptions['walkover']['match'];
		$points['minus'] = $data['sets_allowed'] + $data['straight_set']['lost'] * $forwin + $data['split_set']['win'] * $forloss_split + $data['split_set']['lost'] * $forwin_split + $data['sets_shared'] * $forshare;

		return $points;
	}

	/**
	 * rank Teams
	 *
	 * @param array $teams
	 * @return array of teams
	 */
	protected function rankTeams( $teams ) {

		foreach ( $teams as $key => $team ) {
			$team_sets_won     = isset( $team->sets_won ) ? $team->sets_won : 0;
			$team_sets_allowed = isset( $team->sets_allowed ) ? $team->sets_allowed : 0;
			if ( ! is_numeric( $team_sets_won ) ) {
				$team_sets_won = 0; }
			if ( ! is_numeric( $team_sets_allowed ) ) {
				$team_sets_allowed = 0; }
			$team_games_won     = isset( $team->games_won ) ? $team->games_won : 0;
			$team_games_allowed = isset( $team->games_allowed ) ? $team->games_allowed : 0;
			if ( ! is_numeric( $team_games_won ) ) {
				$team_games_won = 0; }
			if ( ! is_numeric( $team_games_allowed ) ) {
				$team_games_allowed = 0; }
			$points[ $key ]        = $team->points['plus'];
			$sets_diff[ $key ]     = $team_sets_won - $team_sets_allowed;
			$sets_won[ $key ]      = $team_sets_won;
			$sets_allowed[ $key ]  = $team_sets_allowed;
			$games_diff[ $key ]    = $team_games_won - $team_games_allowed;
			$games_won[ $key ]     = $team_games_won;
			$games_allowed[ $key ] = $team_games_allowed;
			$title[ $key ]         = $team->title;
		}
		array_multisort( $points, SORT_DESC, $sets_diff, SORT_DESC, $games_diff, SORT_DESC, $sets_won, SORT_DESC, $sets_allowed, SORT_ASC, $games_won, SORT_DESC, $games_allowed, SORT_ASC, $title, SORT_ASC, $teams );

		return $teams;
	}

	/**
	 * get standings data for given team
	 *
	 * @param int   $team_id
	 * @param array $data
	 * @return array number of runs for and against as assoziative array
	 */
	protected function getStandingsData( $team_id, $data = array(), $matches = false ) {
		global $league;

		$data['straight_set']  = $data['split_set'] = array(
			'win'  => 0,
			'lost' => 0,
		);
		$data['games_allowed'] = 0;
		$data['games_won']     = 0;
		$data['sets_won']      = 0;
		$data['sets_allowed']  = 0;
		$data['sets_shared']   = 0;
		$data['no_player']     = 0;
		$data['no_team']       = 0;

		$league = get_league( $this->id );
		$season = $league->getSeason();

		if ( ! $matches ) {
			$matches = $league->getMatches(
				array(
					'season'           => $season,
					'team_id'          => $team_id,
					'final'            => '',
					'limit'            => false,
					'cache'            => false,
					'home_points'      => 'not null',
					'away_points'      => 'not null',
					'reset_query_args' => true,
					'confirmed'        => true,
				)
			);
		}

		foreach ( $matches as $match ) {

			$index = ( $team_id == $match->home_team ) ? 'player2' : 'player1';
			$match = get_match( $match );

			if ( isset( $league->num_rubbers ) ) {
				$rubbers   = $match->getRubbers();
				$walkovers = array();
				$walkovers = 0;
				foreach ( $rubbers as $rubber ) {

					if ( $rubber->winner_id == $team_id ) {               // winning team
						if ( $match->home_team == $team_id ) {           // home team

							for ( $j = 1; $j <= $league->num_sets; $j++ ) {
								if ( $rubber->sets[ $j ]['player1'] != null ) {
									$set = $rubber->sets[ $j ];
									if ( is_numeric( $set['player2'] ) ) {
										$data['games_allowed'] += intval( $set['player2'] );
									}
									if ( is_numeric( $set['player1'] ) ) {
										$data['games_won'] += intval( $set['player1'] );
									}
									if ( $set['player1'] > $set['player2'] ) {
										$data['sets_won'] += 1;
									} elseif ( $set['player1'] < $set['player2'] ) {
										$data['sets_allowed'] += 1;
									} elseif ( strtoupper( $set['player1'] ) == 'S' ) {
										$data['sets_shared'] += 1;
									}
								}
							}
							if ( $rubber->away_points > '0' ) {          // away team got a set
								$data['split_set']['win'] += 1;
							} else {                                    // away team got no set
								$data['straight_set']['win'] += 1;
							}
						} else {                                        // away team
							for ( $j = 1; $j <= $league->num_sets; $j++ ) {
								if ( $rubber->sets[ $j ]['player1'] != null ) {
									$set = $rubber->sets[ $j ];
									if ( is_numeric( $set['player1'] ) ) {
										$data['games_allowed'] += intval( $set['player1'] );
									}
									if ( is_numeric( $set['player2'] ) ) {
										$data['games_won'] += intval( $set['player2'] );
									}
									if ( $set['player2'] > $set['player1'] ) {
										$data['sets_won'] += 1;
									} elseif ( $set['player2'] < $set['player1'] ) {
										$data['sets_allowed'] += 1;
									} elseif ( strtoupper( $set['player1'] ) == 'S' ) {
										$data['sets_shared'] += 1;
									}
								}
							}
							if ( $rubber->home_points > '0' ) {          // home team got a set
								$data['split_set']['win'] += 1;
							} else {                                    // home team got no set
								$data['straight_set']['win'] += 1;
							}
						}
					} elseif ( $rubber->loser_id == $team_id ) {          // losing team
						if ( $match->home_team == $team_id ) {            // home team
							if ( isset( $rubber->walkover ) && $rubber->walkover == 'away' ) {
								$walkovers ++;
								$data['no_player'] += 1;
							}

							if ( $rubber->home_points > '0' ) {           // home team got a set
								$data['split_set']['lost'] += 1;
							} else {                                    // home team got no set
								$data['straight_set']['lost'] += 1;
							}
							for ( $j = 1; $j <= $league->num_sets; $j++ ) {
								if ( $rubber->sets[ $j ]['player1'] != null ) {
									$data['games_allowed'] += intval( $rubber->sets[ $j ]['player2'] );
									$data['games_won']     += intval( $rubber->sets[ $j ]['player1'] );
									if ( $rubber->sets[ $j ]['player1'] > $rubber->sets[ $j ]['player2'] ) {
										$data['sets_won'] += 1;
									} elseif ( $rubber->sets[ $j ]['player1'] < $rubber->sets[ $j ]['player2'] ) {
										$data['sets_allowed'] += 1;
									} elseif ( strtoupper( $rubber->sets[ $j ]['player1'] ) == 'S' ) {
										$data['sets_shared'] += 1;
									}
								}
							}
						} else {                                        // away team
							if ( isset( $rubber->walkover ) && $rubber->walkover == 'home' ) {
								$walkovers ++;
								$data['no_player'] += 1;
							}
							if ( $rubber->away_points > '0' ) {           // away team got a set
								$data['split_set']['lost'] += 1;
							} else {                                    // away team got no set
								$data['straight_set']['lost'] += 1;
							}
							for ( $j = 1; $j <= $league->num_sets; $j++ ) {
								if ( $rubber->sets[ $j ]['player1'] != null ) {
									$data['games_allowed'] += intval( $rubber->sets[ $j ]['player1'] );
									$data['games_won']     += intval( $rubber->sets[ $j ]['player2'] );
									if ( $rubber->sets[ $j ]['player2'] > $rubber->sets[ $j ]['player1'] ) {
										$data['sets_won'] += 1;
									} elseif ( $rubber->sets[ $j ]['player2'] < $rubber->sets[ $j ]['player1'] ) {
										$data['sets_allowed'] += 1;
									} elseif ( strtoupper( $rubber->sets[ $j ]['player1'] ) == 'S' ) {
										$data['sets_shared'] += 1;
									}
								}
							}
						}
					} elseif ( $rubber->winner_id == -1 ) {                                     // drawn rubber
						if ( $match->home_team == $team_id ) {           // home team

							for ( $j = 1; $j <= $league->num_sets; $j++ ) {
								if ( $rubber->sets[ $j ]['player1'] != null ) {
									if ( isset( $rubber->sets[ $j ]['player2'] ) && is_numeric( $rubber->sets[ $j ]['player2'] ) ) {
										$data['games_allowed'] += $rubber->sets[ $j ]['player2'];
										$data['games_won']     += $rubber->sets[ $j ]['player1'];
									}
									if ( $rubber->sets[ $j ]['player1'] > $rubber->sets[ $j ]['player2'] ) {
										$data['sets_won'] += 1;
									} elseif ( $rubber->sets[ $j ]['player1'] < $rubber->sets[ $j ]['player2'] ) {
										$data['sets_allowed'] += 1;
									} elseif ( strtoupper( $rubber->sets[ $j ]['player1'] ) == 'S' ) {
										$data['sets_shared'] += 1;
									}
								}
							}
							if ( $rubber->away_points > '0' ) {          // away team got a set
								$data['split_set']['win'] += 1;
							} else {                                    // away team got no set
								$data['straight_set']['win'] += 1;
							}
						} else {                                        // away team
							for ( $j = 1; $j <= $league->num_sets; $j++ ) {
								if ( $rubber->sets[ $j ]['player1'] != null ) {
									if ( isset( $rubber->sets[ $j ]['player1'] ) && is_numeric( $rubber->sets[ $j ]['player1'] ) ) {
										$data['games_allowed'] += $rubber->sets[ $j ]['player1'];
										$data['games_won']     += $rubber->sets[ $j ]['player2'];
									}
									if ( $rubber->sets[ $j ]['player2'] > $rubber->sets[ $j ]['player1'] ) {
										$data['sets_won'] += 1;
									} elseif ( $rubber->sets[ $j ]['player2'] < $rubber->sets[ $j ]['player1'] ) {
										$data['sets_allowed'] += 1;
									} elseif ( strtoupper( $rubber->sets[ $j ]['player1'] ) == 'S' ) {
										$data['sets_shared'] += 1;
									}
								}
							}
							if ( $rubber->home_points > '0' ) {          // home team got a set
								$data['split_set']['win'] += 1;
							} else {                                    // home team got no set
								$data['straight_set']['win'] += 1;
							}
						}
					}
				}
				if ( $walkovers == $match->league->num_rubbers ) {
					$data['no_team'] = $walkovers;
				}
			} else {
				// First check for Split Set, else it's straight set
				if ( $match->sets[ $league->num_sets ]['player1'] != '' && $match->sets[ $league->num_sets ]['player2'] != '' ) {
					if ( $match->winner_id == $team_id ) {
						$data['split_set']['win'] += 1;
						for ( $j = 1; $j <= $league->num_sets - 1; $j++ ) {
							$data['games_allowed'] += $match->sets[ $j ][ $index ];
						}
					} elseif ( $match->loser_id == $team_id ) {
						$data['split_set']['lost'] += 1;
						for ( $j = 1; $j <= $league->num_sets - 1; $j++ ) {
							$data['games_allowed'] += $match->sets[ $j ][ $index ];
						}
						$data['games_allowed'] += 1;
					}
				} else {
					if ( $match->winner_id == $team_id ) {
						$data['straight_set']['win'] += 1;
						for ( $j = 1; $j <= $league->num_sets - 1; $j++ ) {
							$data['games_allowed'] += $match->sets[ $j ][ $index ];
						}
					} elseif ( $match->loser_id == $team_id ) {
						$data['straight_set']['lost'] += 1;
						for ( $j = 1; $j <= $league->num_sets - 1; $j++ ) {
							$data['games_allowed'] += $match->sets[ $j ][ $index ];
						}
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Filter match title for double matches
	 *
	 * @param object $match
	 * @param array  $teams
	 * @param string $title
	 * @return string
	 */
	public function matchTitle( $title, $match, $teams ) {
		if ( $match->home_team == -1 ) {
			$homeTeam = 'Bye';

		} else {
			$homeTeam = $teams[ $match->home_team ]['title'];
		}

		if ( $match->away_team == -1 ) {
			$awayTeam = 'Bye';
		} else {
			$awayTeam = $teams[ $match->away_team ]['title'];
		}

		return sprintf( '%s - %s', $homeTeam, $awayTeam );
	}

	/**
	 * display Table Header for Match Administration
	 *
	 * @param none
	 * @return void
	 */
	public function displayMatchesHeader() {
		global $league;
		$league = get_league( $league );
		if ( isset( $league->num_rubbers ) && $league->num_rubbers > 0 ) {
			echo '<th>' . __( 'Rubbers', 'racketmanager' ) . '</th>';
		} else {
			echo '<th colspan="' . $league->num_sets . '" style="text-align: center;">' . __( 'Sets', 'racketmanager' ) . '</th>';
		}
	}

	/**
	 * display Table columns for Match Administration
	 *
	 * @param object $match
	 * @return void
	 */
	public function displayMatchesColumns( $match ) {
		global $league;

		if ( empty( $league ) ) {
			$league = $match->league_id; }
		$league = get_league( $league );

		if ( isset( $league->num_rubbers ) && $league->num_rubbers > 0 ) {
			if ( ! is_numeric( $match->home_team ) || ! is_numeric( $match->away_team ) ) {
				echo '<td></td>';
			} else {
				echo '<td><input class="btn btn-secondary" id="' . $match->id . '" onclick="Racketmanager.showRubbers(this)" value="' . __( 'View Rubbers', 'racketmanager' ) . '"</a></td>';
			}
		} else {
			for ( $i = 1; $i <= $league->num_sets; $i++ ) {
				if ( ! isset( $match->sets[ $i ] ) ) {
					$match->sets[ $i ] = array(
						'player1' => '',
						'player2' => '',
					);
				}
				echo '<td><input class="points" type="text" size="2" id="set_' . $match->id . '_' . $i . '_player1" name="custom[' . $match->id . '][sets][' . $i . '][player1]" value="' . $match->sets[ $i ]['player1'] . '" /> : <input class="points" type="text" size="2" id="set_' . $match->id . '_' . $i . '_player2" name="custom[' . $match->id . '][sets][' . $i . '][player2]" value="' . $match->sets[ $i ]['player2'] . '" /></td>';
			}
		}
	}

	/**
	 * update match results and automatically calculate score
	 *
	 * @param match $match
	 * @return none
	 */
	protected function updateResults( $match ) {
		global $racketmanager;

		$match = get_match( $match );

		// exit if only one team is set
		if ( $match->home_team == -1 || $match->away_team == -1 ) {
			return $match;
		}

		if ( empty( $match->home_points ) && empty( $match->away_points ) ) {
			$score = array(
				'home' => '0',
				'away' => '0',
			);
			if ( isset( $match->league->num_rubbers ) && $match->league->num_rubbers > 0 ) {
				$rubbers = $match->getRubbers();

				foreach ( $rubbers as $rubber ) {
					if ( is_numeric( $rubber->home_points ) ) {
						$score['home'] += intval( $rubber->home_points ); }
					if ( is_numeric( $rubber->away_points ) ) {
						$score['away'] += intval( $rubber->away_points ); }
				}
			} else {
				foreach ( $match->sets as $set ) {
					if ( $set['player1'] != '' && $set['player2'] != '' ) {
						if ( $set['player1'] > $set['player2'] ) {
							$score['home'] += 1;
						} else {
							$score['away'] += 1;
						}
					}
				}
			}
			$match->home_points = $score['home'];
			$match->away_points = $score['away'];
			$match->getMatchResult( $match->home_points, $match->away_points );
		}

		return $match;
	}

	/**
	 * determine if two teams are tied based on
	 *
	 * 1) Primary points
	 * 2) sets difference
	 * 3) games difference
	 * 4) sets won
	 *
	 * @param LeagueTeam $team1
	 * @param LeagueTeam $team2
	 * @return boolean
	 */
	protected function isTie( $team1, $team2 ) {
		// initialize results array

		$res = array(
			'primary'    => false,
			'sets_diff'  => false,
			'games_diff' => false,
			'sets_won'   => false,
		);

		if ( $team1->points['plus'] == $team2->points['plus'] ) {
			$res['primary'] = true;
		}
		if ( ( $team1->sets_won - $team1->sets_allowed ) == ( $team2->sets_won - $team2->sets_allowed ) ) {
			$res['sets_diff'] = true;
		}
		if ( ( $team1->games_won - $team1->games_allowed ) == ( $team2->games_won - $team2->games_allowed ) ) {
			$res['sets_diff'] = true;
		}
		if ( $team1->sets_won == $team2->sets_won ) {
			$res['sets_won'] = true;
		}

		// get unique results
		$res = array_values( array_unique( $res ) );

		// more than one results, i.e. not tied
		if ( count( $res ) > 1 ) {
			return false;
		}

		return $res[0];
	}

}

?>
