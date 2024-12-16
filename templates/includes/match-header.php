<?php
/**
 * Template for match header
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

global $racketmanager;
if ( $match->is_pending ) {
	$score_class = 'is-not-played';
} else {
	$score_class = '';
}
if ( empty( $edit_mode ) || 'false' === $edit_mode ) {
	$edit_mode = false;
} else {
	$edit_mode = true;
}
$is_update_allowed        = $match->is_update_allowed();
$user_can_update          = $is_update_allowed->user_can_update;
$user_type                = $is_update_allowed->user_type;
$user_team                = $is_update_allowed->user_team;
$match_approval_mode      = $is_update_allowed->match_approval_mode;
$allow_schedule_match     = false;
$allow_switch_match       = false;
$allow_amend_score        = false;
$allow_reset_match_result = true;
$show_menu                = false;
if ( $match->is_pending ) {
	if ( $user_can_update ) {
		if ( ( 'admin' === $user_type || 'matchsecretary' === $user_type || 'captain' === $user_type ) && ( 'admin' === $user_type || 'both' === $user_team || 'home' === $user_team ) ) {
			$allow_schedule_match = true;
			$show_menu            = true;
		}
		if ( ( 'admin' === $user_type || ( 'matchsecretary' === $user_type && ( 'both' === $user_team || 'home' === $user_team ) ) ) && ( $match->league->event->seasons[ $match->season ]['homeAway'] ) ) {
			$allow_switch_match = true;
			$show_menu          = true;
		}
	}
} elseif ( 'admin' === $user_type ) {
	$allow_amend_score        = true;
	$allow_reset_match_result = true;
	$show_menu                = true;
} elseif ( 'P' === $match->confirmed ) {
	if ( $user_can_update && ! $match_approval_mode ) {
		$allow_amend_score = true;
		$show_menu         = true;
	}
}
?>
<div class="module__content">
	<div class="module-container">
		<?php
		if ( ! empty( $match->status ) ) {
			$match_status = RacketManager_Util::get_match_status( $match->status );
			$info_msg     = $match_status;
			switch ( $match->status ) {
				case 1:
					$team_ref_alt = empty( $match->custom['walkover'] ) ? null : $match->custom['walkover'];
					if ( $team_ref_alt ) {
						$team_ref = 'home' === $team_ref_alt ? 'away' : 'home';
						$team     = empty( $match->teams[ $team_ref ] ) ? null : $match->teams[ $team_ref ];
						if ( $team ) {
							$info_msg = $match_status . ' - ' . $team->title . ' ' . __( 'did not show', 'racketmanager' );
						}
					}
					break;
				case 2:
					break;
				case 3:
					break;
				case 4:
					break;
				case 5:
					if ( ! empty( $match->date_original ) ) {
						$info_msg = __( 'Match rescheduled from', 'racketmanager' ) . ' ' . mysql2date( 'j F Y H:i', $match->date_original );
					}
					break;
				default:
					break;
			}
			?>
			<div class="text-center">
				<span class="match__message match-warning" data-bs-toggle="tooltip" data-bs-title="<?php echo esc_attr( $info_msg ); ?>"><?php echo esc_html( $match_status ); ?></span>
			</div>
			<?php
		}
		?>
		<div class="text-center">
			<a href="/<?php echo esc_attr( $match->league->event->competition->type ); ?>s/<?php echo esc_attr( seo_url( $match->league->event->name ) ); ?>/<?php echo esc_attr( $match->season ); ?>/">
				<span class="nav-link__value"><?php echo esc_html( $match->league->event->name ); ?></span>
			</a>
			<?php
			if ( 'cup' !== $match->league->event->competition->type ) {
				?>
				&nbsp;&#8226;&nbsp;
				<a href="/<?php echo esc_attr( $match->league->event->competition->type ); ?>/<?php echo esc_attr( seo_url( $match->league->title ) ); ?>/<?php echo esc_attr( $match->season ); ?>/">
					<span class="nav-link__value"><?php echo esc_html( $match->league->title ); ?></span>
				</a>
				<?php
			}
			?>
			<div class="text-center">
				<?php
				if ( ! empty( $match->final_round ) ) {
					?>
					<span><?php echo esc_html( $match->league->championship->get_final_name( $match->final_round ) ); ?>&nbsp;&#8226</span>
					<?php
				} elseif ( ! empty( $match->match_day ) ) {
					?>
					<span><?php echo esc_html__( 'Match Day', 'racketmanager' ) . ' ' . esc_html( $match->match_day ); ?>&nbsp;&#8226</span>
					<?php
				}
				if ( ! empty( $match->leg ) ) {
					?>
					<span><?php echo esc_html__( 'Leg', 'racketmanager' ) . ' ' . esc_html( $match->leg ); ?>&nbsp;&#8226</span>
					<?php
				}
				?>
				<span><time datetime="<?php echo esc_attr( $match->date ); ?>"><?php echo esc_html( mysql2date( $racketmanager->date_format, the_match_date() ) ); ?></time></span>
			</div>
			<?php
			if ( ! empty( $match->date_original ) ) {
				?>
				<div class="text-center info-msg">
					<span>(<?php esc_html_e( 'Original scheduled time', 'racketmanager' ); ?>: <?php echo esc_html( mysql2date( 'j F Y H:i', $match->date_original ) ); ?>)</span>
			</div>
				<?php
			}
			?>
			<?php
			if ( is_user_logged_in() && ! $edit_mode && ( $show_menu ) ) {
				?>
				<div class="match__change">
					<div class="dropdown">
						<a class="nav-link" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
							<svg width="16" height="16" class="icon ">
								<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#pencil-fill' ); ?>"></use>
							</svg>
						</a>
						<ul class="dropdown-menu dropdown-menu-end">
							<?php
							if ( $allow_amend_score ) {
								$match_link = $match->link . 'result/';
								?>
								<li>
									<a class="dropdown-item" href="<?php echo esc_url( $match_link ); ?>">
										<?php esc_html_e( 'Adjust team score', 'racketmanager' ); ?>
									</a>
								</li>
								<?php
							}
							?>
							<?php
							if ( $allow_schedule_match ) {
								?>
								<li>
									<a class="dropdown-item" href="#schedule" onclick="Racketmanager.matchOptions(event, '<?php echo esc_attr( $match->id ); ?>', 'schedule_match')">
										<?php esc_html_e( '(Re)schedule match', 'racketmanager' ); ?>
									</a>
								</li>
								<?php
							}
							?>
							<?php
							if ( $allow_switch_match ) {
								?>
								<li>
									<a class="dropdown-item" href="#switch" onclick="Racketmanager.matchOptions(event, '<?php echo esc_attr( $match->id ); ?>', 'switch_home')">
										<?php esc_html_e( 'Switch home and away', 'racketmanager' ); ?>
									</a>
								</li>
								<?php
							}
							?>
						</ul>
					</div>
				</div>
				<?php
			}
			?>
		</div>
		<div class="team-match mt-3">
			<div class="media">
				<div class="media__wrapper">
					<div class="media__content">
						<?php
						if ( empty( $match->host ) ) {
							?>
							<div><?php esc_html_e( 'Home', 'racketmanager' ); ?></div>
							<?php
						}
						?>
						<h2 class="team-match__name is-team-1" title="<?php echo esc_html( $match->teams['home']->title ); ?>">
							<a href="/<?php echo esc_attr( $match->league->event->competition->type ); ?>/<?php echo esc_html( seo_url( $match->league->title ) ); ?>/<?php echo esc_attr( $match->season ); ?>/team/<?php echo esc_attr( seo_url( $match->teams['home']->title ) ); ?>/" class="nav--link">
								<span class="nav-link__value">
									<?php
									if ( $match->teams['home']->is_withdrawn ) {
										$title_text = $match->teams['home']->title . ' ' . __( 'has withdrawn', 'racketmanager' );
										?>
										<s aria-label="<?php echo esc_attr( $title_text ); ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_attr( $title_text ); ?>">
										<?php
									}
									?>
									<?php echo esc_html( $match->teams['home']->title ); ?>
									<?php
									if ( $match->teams['home']->is_withdrawn ) {
										?>
										</s>
										<?php
									}
									?>
								</span>
							</a>
						</h2>
					</div>
				</div>
			</div>
			<div class="score score--large <?php echo esc_attr( $score_class ); ?>">
				<?php
				if ( $match->is_pending ) {
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
			<div class="media media--reverse">
				<div class="media__wrapper">
					<div class="media__content">
						<?php
						if ( empty( $match->host ) ) {
							?>
							<div><?php esc_html_e( 'Away', 'racketmanager' ); ?></div>
							<?php
						}
						?>
						<h2 class="team-match__name is-team-2" title="<?php echo esc_html( $match->teams['away']->title ); ?>">
							<a href="/<?php echo esc_attr( $match->league->event->competition->type ); ?>/<?php echo esc_html( seo_url( $match->league->title ) ); ?>/<?php echo esc_attr( $match->season ); ?>/team/<?php echo esc_attr( seo_url( $match->teams['away']->title ) ); ?>/" class="nav--link">
								<span class="nav-link__value">
									<?php
									if ( $match->teams['away']->is_withdrawn ) {
										$title_text = $match->teams['away']->title . ' ' . __( 'has withdrawn', 'racketmanager' );
										?>
										<s aria-label="<?php echo esc_attr( $title_text ); ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_attr( $title_text ); ?>">
										<?php
									}
									?>
									<?php echo esc_html( $match->teams['away']->title ); ?>
									<?php
									if ( $match->teams['away']->is_withdrawn ) {
										?>
										</s>
										<?php
									}
									?>
								</span>
							</a>
						</h2>
					</div>
				</div>
			</div>
		</div>
		<?php
		if ( ! $match->is_pending ) {
			?>
			<div class="text-center">
				<?php esc_html_e( 'Start', 'racketmanager' ); ?>: <time datetime="<?php echo esc_attr( $match->date ); ?>"><?php the_match_time(); ?></time>
			</div>
			<?php
		}
		if ( $edit_mode && $user_can_update && ! $match_approval_mode ) {
			?>
			<div class="text-center mt-2">
				<a href="#status" class="nav__link btn btn-outline" id="matchStatusButton" onclick="Racketmanager.matchStatusModal(event, '<?php echo esc_attr( $match->id ); ?>')">
					<svg width="16" height="16" class="icon-plus nav-link__prefix">
						<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#plus-lg' ); ?>"></use>
					</svg>
					<span class="nav-link__value"><?php esc_html_e( 'Match status', 'racketmanager' ); ?></span>
				</a>

			</div>
			<?php
		}
		?>
	</div>
</div>
<div class="module__footer">
	<span class="module__footer-item">
		<strong class="module__footer-item-title"><?php esc_html_e( 'Rubbers', 'racketmanager' ); ?>: </strong>
		<?php
		if ( isset( $match->custom['stats']['rubbers'] ) ) {
			$match_stat = $match->custom['stats']['rubbers']['home'] . ' - ' . $match->custom['stats']['rubbers']['away'];
		} else {
			$match_stat = '0 - 0';
		}
		?>
		<span class="module__foooter-item-value"><?php echo esc_html( $match_stat ); ?></span>
	</span>
	<span class="module__footer-item">
		<strong class="module__footer-item-title"><?php esc_html_e( 'Sets', 'racketmanager' ); ?>: </strong>
		<?php
		if ( isset( $match->custom['stats']['sets'] ) ) {
			$match_stat = $match->custom['stats']['sets']['home'] . ' - ' . $match->custom['stats']['sets']['away'];
		} else {
			$match_stat = '0 - 0';
		}
		?>
		<span class="module__foooter-item-value"><?php echo esc_html( $match_stat ); ?></span>
	</span>
	<span class="module__footer-item">
		<strong class="module__footer-item-title"><?php esc_html_e( 'Games', 'racketmanager' ); ?>: </strong>
		<?php
		if ( isset( $match->custom['stats']['games'] ) ) {
			$match_stat = $match->custom['stats']['games']['home'] . ' - ' . $match->custom['stats']['games']['away'];
		} else {
			$match_stat = '0 - 0';
		}
		?>
		<span class="module__foooter-item-value"><?php echo esc_html( $match_stat ); ?></span>
	</span>
</div>
