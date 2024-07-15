<?php
/**
 * Build match entry for team
 *
 * @package Racketmanager/Templates
 */

namespace RacketManager;

$match_pending = false;
if ( empty( $match->winner_id ) ) {
	$match_pending = true;
}
?>
<div class="match match--team-match <?php echo empty( $selected_match ) ? '' : 'is-selected'; ?>">
	<?php
	if ( ! empty( $show_header ) ) {
		?>
		<div class="match__header match__header--up">
			<div class="match__header-title">
				<div class="match__header-title-main">
					<span>
						<?php echo esc_html( $match->league->title ); ?>
					</span>
				</div>
			</div>
		</div>
		<?php
	}
	?>
	<?php
	if ( is_numeric( $match->home_team ) && $match->home_team >= 1 && is_numeric( $match->away_team ) && $match->away_team >= 1 && ! empty( $match_link ) ) {
		?>
		<a class="team-match__wrapper" href="<?php echo esc_html( $match_link ); ?>">
		<?php
	} else {
		?>
		<div class="team-match__wrapper">
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
			if ( empty( $by_date ) ) {
				?>
				&nbsp;&#8226;&nbsp;
				<span>
					<time
						datetime="<?php echo esc_attr( $match->date ); ?>"><?php echo esc_html( mysql2date( 'j. F Y', $match->date ) ); ?></time>
				</span>
				<?php
			}
			?>
		</span>
		<?php
		if ( $match->status ) {
			$match_message = RacketManager_Util::get_match_status( $match->status );
			?>
			<span class="match__message match-warning"><?php echo esc_html( $match_message ); ?></span>
			<?php
		}
		?>
		<?php
		if ( ! $match_pending && ! empty( $highlight_match ) ) {
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
			<div class="score <?php echo empty( $score_class ) ? '' : esc_attr( $score_class ); ?>">
				<?php
				if ( $match_pending ) {
					if ( empty( $match->start_time ) ) {
						$score_filler = __( 'vs', 'racketmanager' );
					} else {
						$score_filler = $match->start_time;
					}
					?>
					<time datetime="<?php echo esc_attr( $match->date ); ?>"><?php echo esc_html( $score_filler ); ?></time>
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
	if ( is_numeric( $match->home_team ) && $match->home_team >= 1 && is_numeric( $match->away_team ) && $match->away_team >= 1 && ! empty( $match_link ) ) {
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
	if ( ! empty( $user_can_update ) && empty( $match->confirmed ) ) {
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
