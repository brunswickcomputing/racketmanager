<?php
/**
 * Template for match header
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

if ( empty( $match->winner_id ) ) {
	$score_class   = 'is-not-played';
	$match_pending = true;
} else {
	$score_class   = '';
	$match_pending = false;
}
if ( 'false' === $edit_mode ) {
	$edit_mode = false;
}
$user_can_update_array = $racketmanager->is_match_update_allowed( $match->teams['home'], $match->teams['away'], $match->league->event->competition->type, $match->confirmed );
$user_can_update       = $user_can_update_array[0];
$user_type             = $user_can_update_array[1];
$user_team             = $user_can_update_array[2];
$allow_schedule_match  = false;
$allow_switch_match    = false;
$allow_amend_score     = false;
if ( $match_pending ) {
	if ( $user_can_update ) {
		if ( 'admin' === $user_type || 'matchsecretary' === $user_type || 'captain' === $user_type ) {
			if ( 'admin' === $user_type || 'both' === $user_team || 'home' === $user_team ) {
				$allow_schedule_match = true;
			}
		}
		if ( 'admin' === $user_type || ( 'matchsecretary' === $user_type && ( 'both' === $user_team || 'home' === $user_team ) ) ) {
			if ( 'false' === $match->league->event->seasons[ $match->season ]['homeAway'] ) {
				$allow_switch_match = true;
			}
		}
	}
} elseif ( 'P' === $match->confirmed ) {
	if ( $user_can_update ) {
		if ( 'admin' === $user_type || 'matchsecretary' === $user_type || 'captain' === $user_type ) {
			if ( 'admin' === $user_type || 'both' === $user_team || 'home' === $user_team ) {
				$allow_amend_score = true;
			}
		}
	}
} elseif ( 'admin' === $user_type ) {
	$allow_amend_score = true;
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
					$team_ref = empty( $match->custom['walkover'] ) ? null : $match->custom['walkover'];
					if ( $team_ref ) {
						$team = empty( $match->teams[ $team_ref ] ) ? null : $match->teams[ $team_ref ];
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
					if ( ! empty( $match->$original_date ) ) {
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
									if ( 'W' === $match->teams['home']->status ) {
										$title_text = $match->teams['home']->title . ' ' . __( 'has withdrawn', 'racketmanager' );
										?>
										<s aria-label="<?php echo esc_attr( $title_text ); ?>" data-bs-toggle="tooltip" data-bs-placement="left" title="<?php echo esc_attr( $title_text ); ?>">
										<?php
									}
									?>
									<?php echo esc_html( $match->teams['home']->title ); ?>
									<?php
									if ( 'W' === $match->teams['home']->status ) {
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
									if ( 'W' === $match->teams['away']->status ) {
										$title_text = $match->teams['away']->title . ' ' . __( 'has withdrawn', 'racketmanager' );
										?>
										<s aria-label="<?php echo esc_attr( $title_text ); ?>" data-bs-toggle="tooltip" data-bs-placement="left" title="<?php echo esc_attr( $title_text ); ?>">
										<?php
									}
									?>
									<?php echo esc_html( $match->teams['away']->title ); ?>
									<?php
									if ( 'W' === $match->teams['away']->status ) {
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
		if ( ! $match_pending ) {
			?>
			<div class="text-center">
				<?php esc_html_e( 'Start', 'racketmanager' ); ?>: <time datetime="<?php echo esc_attr( $match->date ); ?>"><?php the_match_time(); ?></time>
			</div>
			<?php
		}
		if ( $edit_mode && 'false' !== $edit_mode ) {
			?>
			<div class="text-center mt-2">
				<a href="" class="nav__link btn btn-outline" id="matchStatusButton" onclick="Racketmanager.matchStatusModal(event, '<?php echo esc_attr( $match->id ); ?>')">
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
