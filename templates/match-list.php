<?php
/**
 * Template for match list when multiple matches found for show match screen
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

global $racketmanager;
?>
	<div id="match-header" class="team-match-header module module--dark module--card row">
		<div class="module__content">
			<div class="module-container">
				<div class="text-center">
					<a href="/<?php echo esc_attr( $league->event->competition->type ); ?>s/<?php echo esc_attr( seo_url( $league->event->name ) ); ?>/<?php echo esc_attr( $season ); ?>/">
						<span class="nav-link__value"><?php echo esc_html( $league->event->name ); ?></span>
					</a>
					<div class="text-center mb-3">
						<?php
						if ( ! empty( $round ) ) {
							?>
							<span><?php echo esc_html( $league->championship->get_final_name( $round ) ); ?></span>
							<?php
						} elseif ( ! empty( $match_day ) ) {
							?>
							<span><?php echo esc_html__( 'Match Day', 'racketmanager' ) . ' ' . esc_html( $match_day ); ?></span>
							<?php
						}
						?>
					</div>
				</div>
				<div class="team-match">
					<div class="media no-score">
						<div class="media__wrapper">
							<div class="media__content">
								<h2 class="team-match__name is-team-1" title="<?php echo esc_html( ucwords( $home_team ) ); ?>">
									<a href="" class="nav--link">
										<span class="nav-link__value">
											<?php echo esc_html( ucwords( $home_team ) ); ?>
										</span>
									</a>
								</h2>
							</div>
						</div>
					</div>
					<div class="media media--reverse no-score">
						<div class="media__wrapper">
							<div class="media__content">
								<h2 class="team-match__name is-team-2" title="<?php echo esc_html( ucwords( $away_team ) ); ?>">
									<a href="" class="nav--link">
										<span class="nav-link__value">
											<?php echo esc_html( ucwords( $away_team ) ); ?>
										</span>
									</a>
								</h2>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="module module--card">
		<div class="module__banner">
			<h3 class="module__title">
				<?php esc_html_e( 'Matches', 'racketmanager' ); ?>
			</h4>
		</div>
		<div class="module__content">
			<div class="module-container">
				<?php
				foreach ( $matches as $match ) {
					if ( ! empty( $match->leg ) ) {
						$match_link = $match->link;
						if ( $action ) {
							$match_link .= $action . '/';
						}
					}
					?>
					<div class="match-row row justify-content-center">
						<div class="col-6 col-md-2"><time datetime="<?php echo esc_attr( $match->date ); ?>"><?php echo esc_html( mysql2date( $racketmanager->date_format, the_match_date() ) ); ?></time></div>
						<?php
						if ( ! empty( $match->leg ) ) {
							?>
							<div class="col-6 col-md-2">
								<a style="display: flex;" href="<?php echo esc_attr( $match_link ); ?>">
									<?php echo esc_html__( 'Leg', 'racketmanager' ) . ' ' . esc_html( $match->leg ); ?>
								</a>
							</div>
							<?php
						}
						?>
					</div>
					<?php
				}
				?>
			</div>
		</div>
	</div>
</div>
