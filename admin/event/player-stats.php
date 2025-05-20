<?php
/**
 * Event player statistics administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var object $event */
$season = $event->get_season_event();
if ( $event->is_championship ) {
	$heading = 'Round';
	if ( isset( $event->primary_league ) ) {
		$primary_league = get_league( $event->primary_league );
	} else {
		$leagues        = $event->get_leagues();
		$primary_league = get_league( array_key_first( $event->league_index ) );
	}
	$num_cols = $primary_league->championship->num_rounds;
	$rounds   = array();
	$i        = 1;
	foreach ( array_reverse( $primary_league->championship->get_finals() ) as $final ) {
		$rounds[ $i ] = $final;
		++ $i;
	}
} else {
	$heading  = __( 'Match Day', 'racketmanager' );
	$num_cols = $season['num_match_days'] ?? 0;
}
$clubs = $this->get_clubs();
if ( ! empty( $event->seasons ) ) { ?>
    <!-- Season Dropdown -->
    <div class="mb-3">
        <div class="row  justify-content-end">
            <div class="col-auto">
                <form action="admin.php" method="get" class="form-control">
                    <input type="hidden" name="page" value="racketmanager"/>
                    <input type="hidden" name="subpage" value="show-event"/>
                    <input type="hidden" name="event_id" value="<?php echo esc_html( $event->id ); ?>"/>
                    <label for="club_id"><?php esc_html_e( 'Affiliated Club', 'racketmanager' ); ?></label>
                    <select size="1" name="club_id" id="club_id">
                        <option><?php esc_html_e( 'Select club', 'racketmanager' ); ?></option>
						<?php
						foreach ( $clubs as $club ) {
							?>
                            <option value="<?php echo esc_html( $club->id ); ?>" <?php selected( $club->id, $club_id ); ?>>
								<?php echo esc_html( $club->name ); ?>
                            </option>
							<?php
						}
						?>
                    </select>
                    <label for="season" style="vertical-align: middle;"><?php esc_html_e( 'Season', 'racketmanager' ); ?></label>
                    <select size="1" name="season" id="season">
						<?php
						foreach ( $event->seasons as $event_season ) {
							?>
                            <option value="<?php echo esc_html( $event_season['name'] ); ?>" <?php selected( $event_season['name'], $season['name'] ); ?>>
								<?php echo esc_html( $event_season['name'] ); ?>
                            </option>
							<?php
						}
						?>
                    </select>
                    <input type="submit" name="statsseason" value="<?php esc_html_e( 'Show', 'racketmanager' ); ?>" class="btn btn-secondary"/>
                </form>
            </div>
        </div>
    </div>
<?php } ?>

<!-- View Player Stats -->
<div>
    <form id="player-stats-filter" method="post" action="">
        <table class="table table-striped" aria-describedby="<?php esc_html_e( 'Player statistics', 'racketmanager' ); ?>">
            <thead class="table-dark">
            <tr>
                <th><?php esc_html_e( 'Name', 'racketmanager' ); ?></th>
                <th></th>
				<?php
				$match_day_stats_dummy = array();
				for ( $day = 1; $day <= $num_cols; $day ++ ) {
					$match_day_stats_dummy[ $day ] = array();
					?>
                    <th class="matchday">
						<?php
						if ( $event->is_championship ) {
							echo esc_html( $rounds[ $day ]['name'] );
						} else {
							echo esc_html( $day );
						}
						?>
                    </th>
				<?php } ?>
            </tr>
            </thead>
            <tbody>
			<?php
			$playerstats = $event->get_player_stats(
				array(
					'season' => $season['name'] ?? false,
					'club'   => $club_id,
				)
			);
			if ( $playerstats ) {
				$class = '';
				foreach ( $playerstats as $playerstat ) {
					$class           = ( 'alternate' === $class ) ? '' : 'alternate';
					$match_day_stats = $match_day_stats_dummy;
					$prev_team_num   = 0;
					$play_down_count = 0;
					$prev_match_day  = 0;
					$i               = 0;
					$prev_round      = '';

					for ( $t = 1; $t < $num_cols; $t ++ ) {
						$teamplay[ $t ] = 0;
					}

					foreach ( $playerstat->matchdays as $match_day => $match ) {
						if ( ( $event->is_championship && ! $prev_round === $match->final_round ) || ( ! $event->is_championship && ! $prev_match_day === $match->match_day ) ) {
							$i = 0;
						}
						$team_num = substr( $match->team_title, - 1 );
						++ $teamplay[ $team_num ];

						if ( 0 === $prev_team_num ) {
							$play_dir = '';
						} elseif ( $team_num > $prev_team_num ) {
							if ( $teamplay[ $prev_team_num ] > 2 ) {
								$play_dir = 'playdownerr';
							} else {
								$play_dir = 'playdown';
							}
							++ $play_down_count;
						} else {
							$play_dir = '';
						}
						$prev_team_num = $team_num;

						if ( $match->match_winner === $match->team_id ) {
							$matchresult = __( 'Won', 'racketmanager' );
						} elseif ( $match->match_loser === $match->team_id ) {
							$matchresult = __( 'Lost', 'racketmanager' );
						} else {
							$matchresult = __( 'Drew', 'racketmanager' );
						}
						if ( $match->rubber_winner === $match->team_id ) {
							$rubberresult = __( 'Won', 'racketmanager' );
						} elseif ( $match->rubber_loser === $match->team_id ) {
							$rubberresult = __( 'Lost', 'racketmanager' );
						} else {
							$rubberresult = __( 'Drew', 'racketmanager' );
						}
						$player_line = array(
							'team'         => $match->team_title,
							'pair'         => $match->rubber_number,
							'matchresult'  => $matchresult,
							'rubberresult' => $rubberresult,
							'play_dir'     => $play_dir,
						);
						if ( $event->is_championship ) {
							$d                           = $primary_league->championship->get_finals( $match->final_round )['round'];
							$match_day_stats[ $d ][ $i ] = $player_line;
						} else {
							$match_day_stats[ $match->match_day ][ $i ] = $player_line;
						}
						$prev_match_day = $match->match_day;
						$prev_round     = $match->final_round;
						++ $i;
					}
					?>

                    <tr>
                        <td><?php echo esc_html( $playerstat->fullname ); ?></td>
                        <td title="Played Down">
							<?php
                            if ( ! 0 === $play_down_count ) {
                                echo esc_html( $play_down_count );
                            }
                            ?>
                        </td>
						<?php
						foreach ( $match_day_stats as $daystat ) {
							$day_show    = '';
							$match_title = '';
							$play_dir    = '';
							foreach ( $daystat as $stat ) {
								if ( isset( $stat['team'] ) ) {
									$match_title = $matchresult . ' match & ' . $rubberresult . ' rubber ';
									$play_dir    = $stat['play_dir'];
									$team        = $stat['team'];
									$pair        = $stat['pair'];
									$day_show    .= $team . '<br />Pair' . $pair . '<br />';
								}
							}
							if ( count( $daystat ) > 1 ) {
								$play_dir = 'playmulti';
							}
							?>
                            <td class="matchday <?php echo esc_html( $play_dir ); ?>" title="<?php echo esc_html( $match_title ); ?>">
								<?php
                                if ( $day_show ) {
                                    ?>
									<?php echo $day_show; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								    <?php
                                }
                                ?>
                            </td>
							<?php
						}
						$match_day_stats = $match_day_stats_dummy;
						?>
                    </tr>
				    <?php
                }
                ?>
			    <?php
            }
            ?>
            </tbody>
        </table>
    </form>
</div>
