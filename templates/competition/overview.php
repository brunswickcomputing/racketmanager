<?php
/**
 * Template for competition overview
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
	<div class="module module--card">
		<div class="module__banner">
			<h3 class="module__title"><?php esc_html_e( 'Overview', 'racketmanager' ); ?></h3>
		</div>
		<div class="module__content">
			<div class="module-container">
				<div class="container tournament-meta">
					<ul class="module tournament-meta__info">
						<?php
						if ( ! empty( $competition_season['venue_name'] ) ) {
							?>
							<li class="tournament-meta__info_block">
								<div class="text--low-opacity text--small">
									<?php esc_html_e( 'Venue', 'racketmanager' ); ?>
								</div>
								<div class="tournament-meta__title">
									<span class="meta-link">
										<span class="meta-link__value">
											<?php echo esc_html( $competition_season['venue_name'] ); ?>
										</span>
									</span>
								</div>
							</li>
							<?php
						}
						?>
						<li class="tournament-meta__info_block">
							<div class="text--low-opacity text--small">
								<?php esc_html_e( 'Events', 'racketmanager' ); ?>
							</div>
							<div class="tournament-meta__title">
								<span class="meta-link">
									<span class="meta-link__value">
										<?php echo esc_html( count( $competition->events ) ); ?>
									</span>
								</span>
							</div>
						</li>
						<li class="tournament-meta__info_block">
							<div class="text--low-opacity text--small">
								<?php esc_html_e( 'Entries', 'racketmanager' ); ?>
							</div>
							<div class="tournament-meta__title">
								<span class="meta-link">
									<span class="meta-link__value">
										<?php echo esc_html( $competition->entries ); ?>
									</span>
								</span>
							</div>
						</li>
					</ul>
					<div class="tournament-meta__timeline">
						<ol class="list--timeline-labelled list--timeline list has-custom-icon">
							<?php
							if ( ! empty( $competition_season['dateEnd'] ) ) {
								?>
								<li class="list__item is-started
									<?php
									if ( 'start' === $competition->current_phase ) {
										echo ' is-current';
										echo ' is-success';
									}
									?>
									">
									<div class="list__value">
										<?php esc_html_e( 'Start competition', 'racketmanager' ); ?>
									</div>
									<div class="list__meta">
										<?php echo esc_html( $competition_season['dateStart'] ); ?>
									</div>
								</li>
								<?php
							}
							?>
							<?php
							if ( ! empty( $competition_season['dateEnd'] ) ) {
								?>
								<li class="list__item is-finished
									<?php
									if ( 'end' === $competition->current_phase ) {
										echo ' is-current';
										echo ' is-danger';
									}
									?>
									">
									<div class="list__value">
										<?php esc_html_e( 'End of competition', 'racketmanager' ); ?>
									</div>
									<div class="list__meta">
										<?php echo esc_html( mysql2date( $racketmanager->date_format, $competition_season['dateEnd'] ) ); ?>
									</div>
								</li>
								<?php
							}
							?>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
