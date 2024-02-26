<?php
/**
 * Template for match header
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
		<div class="module__content">
			<div class="module-container">
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
					<div class="text-center mb-3">
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
						<span><time datetime="<?php echo esc_attr( $match->date ); ?>"><?php echo esc_html( mysql2date( 'j. F Y', the_match_date() ) ); ?></time></span>
					</div>
				</div>
				<div class="team-match">
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
											<?php echo esc_html( $match->teams['home']->title ); ?>
										</span>
									</a>
								</h2>
							</div>
						</div>
					</div>
					<?php
					if ( empty( $match->winner_id ) ) {
						$score_class   = 'is-not-played';
						$match_pending = true;
					} else {
						$score_class   = '';
						$match_pending = false;
					}
					?>
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
											<?php echo esc_html( $match->teams['away']->title ); ?>
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
