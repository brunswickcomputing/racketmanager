<?php
/**
 * Template for tournament matches
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<script>
	jQuery(document).ready(function() {
	});
</script>
<div class="container">
	<div class="module module--card">
		<div class="module__banner">
			<h3 class="module__title"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></h3>
		</div>
		<div class="module__content">
			<div class="module-container">
				<div class="container">
					<?php
					if ( count( $tournament->match_dates ) > 8 ) {
						?>
						<div class="form-wrapper">
							<div class="col-6">
								<form id="tournament-match-date-form" action="">
									<input type="hidden" name="tournament_id" id="tournament_id" value="<?php echo esc_html( $tournament->name ); ?>" />
									<select class="form-select" name="match_date" id="match_date">
										<?php
										foreach ( $tournament->match_dates as $match_date ) {
											?>
											<option value="<?php echo esc_html( $match_date ); ?>" <?php selected( $current_match_date, $match_date ); ?>>
												<?php echo esc_html( mysql2date( 'D j M', $match_date ) ); ?>
											</option>
											<?php
										}
										?>
									</select>
								</form>
							</div>
						</div>
						<?php
					} else {
						?>
						<ul class="nav nav-tabs frontend match-date-list" id="match_date_tabs" role="tablist">
						<?php
						foreach ( $tournament->match_dates as $match_date ) {
							if ( $current_match_date === $match_date ) {
								$selected_class = 'is_selected';
							} else {
								$selected_class = '';
							}
							?>
							<li class="nav-item nav-link <?php echo esc_html( $selected_class ); ?>" role="presentation">
								<a href="/tournament/<?php echo esc_html( seo_url( $tournament->name ) ); ?>/matches/<?php echo esc_html( $match_date ); ?>/" data-value="<?php echo esc_html( $match_date ); ?>" class="nav-link__value">
									<span class="date__weekday">
										<?php echo esc_html( mysql2date( 'D', $match_date ) ); ?>
									</span>
									<span class="date__day">
										<?php echo esc_html( mysql2date( 'j', $match_date ) ); ?>
									</span>
									<span class="date__month">
										<?php echo esc_html( mysql2date( 'M', $match_date ) ); ?>
									</span>
								</a>
							</li>
							<?php
						}
						?>
						</ul>
						<?php
					}
					?>
				</div>
				<?php
				if ( empty( $tournament_matches ) ) {
					if ( empty( $order_of_play ) ) {
						?>
						<?php esc_html_e( 'No matches', 'racketmanager' ); ?>
						<?php
					} else {
						require RACKETMANAGER_PATH . 'templates/includes/order-of-play-body.php';
					}
				} else {
					?>
					<h3 class="header"><?php echo esc_html_e( 'Match Schedule', 'racketmanager' ); ?></h3>
					<ul class="match-group">
						<?php
						foreach ( $tournament_matches as $match ) {
							?>
							<li class="match-group__item">
								<?php
								$match_display = 'list';
								require RACKETMANAGER_PATH . 'templates/tournament/match.php';
								?>
							</li>
							<?php
						}
						?>
					</ul>
				<?php } ?>
			</div>
		</div>
	</div>
</div>
