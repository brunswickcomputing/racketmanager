<?php
/**
 * Template for list of teams matches
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

if ( empty( $matches_key ) ) {
	$matches_key = null;
}
if ( empty( $current_club ) ) {
	$current_club = null;
}
if ( empty( $league->team->id ) ) {
	$current_team = null;
} else {
	$current_team = $league->team->id;
}
if ( ! isset( $show_header ) ) {
	$show_header = true;
	if ( 'league' === $matches_key ) {
		$show_header = false;
	}
}
if ( ! isset( $by_date ) ) {
	$by_date = false;
}
$highlight_match = false;
foreach ( $matches as $match ) {
	$user_can_update_array = $racketmanager->is_match_update_allowed( $match->teams['home'], $match->teams['away'], $match->league->event->competition->type, $match->confirmed );
	$user_can_update       = $user_can_update_array[0];
	$match_link            = $match->link;
	$match_status_class    = null;
	$match_status_text     = null;
	$score_class           = null;
	$match_pending         = false;
	if ( empty( $match->winner_id ) ) {
		$score_class   = 'is-not-played';
		$match_pending = true;
	} elseif ( ! empty( $current_club ) || ! empty( $current_team ) ) {
		$opponents = array( 'home', 'away' );
		foreach ( $opponents as $opponent ) {
			if ( ( ! empty( $current_club ) && $match->teams[ $opponent ]->club->id === $current_club ) || ( ! empty( $current_team ) && $match->teams[ $opponent ]->id === $current_team ) ) {
				$highlight_match = true;
				if ( $match->teams[ $opponent ]->id === $match->winner_id ) {
					$match_status_class = 'winner';
					$match_status_text  = 'W';
				} elseif ( $match->teams[ $opponent ]->id === $match->loser_id ) {
					$match_status_class = 'loser';
					$match_status_text  = 'L';
				} elseif ( '-1' === $match->loser_id ) {
					$match_status_class = 'tie';
					$match_status_text  = 'T';
				} else {
					$match_status_class = '';
					$match_status_text  = '';
				}
			}
		}
	}
	?>
	<ul class="match-group">
		<li class="match-group__item">
			<div class="match--team-match match">
				<?php
				if ( $show_header ) {
					?>
					<div class="match__header match__header--up">
						<div class="match__header-title">
							<div class="match__header-title-main">
								<span class="nav--link">
									<span class="nav-link__value"><?php echo esc_html( $match->league->title ); ?></span>
								</span>
							</div>
						</div>
					</div>
					<?php
				}
				?>
				<?php
				if ( is_numeric( $match->home_team ) && $match->home_team >= 1 && is_numeric( $match->away_team ) && $match->away_team >= 1 ) {
					?>
					<a class="team-match__wrapper" href="<?php echo esc_html( $match_link ); ?>">
					<?php
				} else {
					?>
					<div class="team-match__wrapper" href="<?php echo esc_html( $match_link ); ?>">
					<?php
				}
				?>
				<div class="match__header">
					<span class="match__header-title">
						<?php
						if ( ! empty( $match->final_round ) ) {
							?>
						<span><?php echo esc_html( $match->league->championship->get_final_name( $match->final_round ) ); ?></span>
							<?php
						} elseif ( ! empty( $match->match_day ) ) {
							?>
						<span><?php echo esc_html__( 'Match Day', 'racketmanager' ) . ' ' . esc_html( $match->match_day ); ?></span>
							<?php
						}
						if ( ! empty( $match->leg ) ) {
							?>
							<span>&nbsp;&#8226&nbsp;<?php echo esc_html__( 'Leg', 'racketmanager' ) . ' ' . esc_html( $match->leg ); ?></span>
							<?php
						}
						?>
						<?php
						if ( ! $by_date ) {
							?>
							&nbsp;&#8226;&nbsp;
							<span>
								<time
									datetime="<?php echo esc_attr( $match->date ); ?>"><?php echo esc_html( mysql2date( 'j. F Y', the_match_date() ) ); ?></time>
							</span>
							<?php
						}
						?>
					</span>
					<?php
					if ( ! $match_pending && $highlight_match ) {
						?>
						<span class="match__status <?php echo esc_attr( $match_status_class ); ?>"><?php echo esc_attr( $match_status_text ); ?></span>
						<?php
					}
					?>
				</div>
				<div class="match__body">
					<div class="team-match">
						<div class="team-match__name is-team-1">
							<span class="nav--link">
								<span class="nav-link__value">
									<?php
									if ( ! empty( $match->teams['home']->status ) && 'W' === $match->teams['home']->status ) {
										$title_text = $match->teams['home']->title . ' ' . __( 'has withdrawn', 'racketmanager' );
										?>
										<s aria-label="<?php echo esc_attr( $title_text ); ?>" data-bs-toggle="tooltip" data-bs-placement="left" title="<?php echo esc_attr( $title_text ); ?>">
										<?php
									}
									?>
									<?php echo esc_html( $match->teams['home']->title ); ?>
									<?php
									if ( ! empty( $match->teams['home']->status ) && 'W' === $match->teams['home']->status ) {
										?>
										</s> 
										<?php
									}
									?>
								</span>
							</span>
						</div>
						<div class="score <?php echo esc_attr( $score_class ); ?>">
							<?php
							if ( $match_pending ) {
								?>
								<time datetime="<?php echo esc_attr( $match->date ); ?>"><?php the_match_time(); ?></time>
								<?php
							} else {
								?>
								<span class="is-team-1"><?php echo esc_html( sprintf( '%g', $match->home_points ) ); ?></span>
								<span class="score-separator">-</span>
								<span class="is-team-2"><?php echo esc_html( sprintf( '%g', $match->away_points ) ); ?></span>
								<?php
							}
							?>
						</div>
						<div class="team-match__name is-team-2">
							<span class="nav--link">
								<span class="nav-link__value">
									<?php
									if ( ! empty( $match->teams['away']->status ) && 'W' === $match->teams['away']->status ) {
										$title_text = $match->teams['away']->title . ' ' . __( 'has withdrawn', 'racketmanager' );
										?>
										<s aria-label="<?php echo esc_attr( $title_text ); ?>" data-bs-toggle="tooltip" data-bs-placement="left" title="<?php echo esc_attr( $title_text ); ?>">
										<?php
									}
									?>
									<?php echo esc_html( $match->teams['away']->title ); ?>
									<?php
									if ( ! empty( $match->teams['away']->status ) && 'W' === $match->teams['away']->status ) {
										?>
										</s> 
										<?php
									}
									?>
								</span>
							</span>
						</div>
					</div>
				</div>
				<?php
				if ( is_numeric( $match->home_team ) && $match->home_team >= 1 && is_numeric( $match->away_team ) && $match->away_team >= 1 ) {
					?>
					</a>
					<?php
				} else {
					?>
					</div>
					<?php
				}
				?>
				<?php
				if ( $user_can_update && empty( $match->confirmed ) ) {
					$match_link_result = $match_link . 'result/';
					?>
					<div class="match__button">
						<a href="<?php echo esc_url( $match_link_result ); ?>" class="btn match__btn">
							<i class="racketmanager-svg-icon">
								<?php racketmanager_the_svg( 'icon-pencil' ); ?>
							</i>
						</a>
					</div>
					<?php
				}
				?>
			</div>
		</li>
	</ul>
	<?php
}
