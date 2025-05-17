<?php
/**
 * Template for tournament matches
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $tournament */
/** @var string $current_match_date */
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
					<input type="hidden" id="tournamentId" value="<?php echo esc_attr( $tournament->id ); ?>" />
					<?php
					if ( count( $tournament->match_dates ) > 8 ) {
						require RACKETMANAGER_PATH . 'templates/includes/tournament-date-selection.php';
						?>
						<?php
					} else {
						?>
						<div class="d-block d-md-none">
							<?php require RACKETMANAGER_PATH . 'templates/includes/tournament-date-selection.php'; ?>
						</div>
						<ul class="d-none d-md-flex nav nav-tabs frontend match-date-list" id="match_date_tabs" role="tablist">
							<?php
							foreach ( $tournament->match_dates as $match_date ) {
								if ( $current_match_date === $match_date ) {
									$selected_class = 'is_selected';
								} else {
									$selected_class = '';
								}
								$matches_link = '/tournament/' . seo_url( $tournament->name ) . '/matches/' . $match_date . '/';
								?>
								<li class="nav-item nav-link <?php echo esc_html( $selected_class ); ?>" role="presentation">
									<a href="<?php echo esc_attr( $matches_link ); ?>" data-value="<?php echo esc_html( $match_date ); ?>" class="nav-link__value tabDataLink" data-type="tournament" data-type-id="<?php echo esc_attr( $tournament->id ); ?>" data-season="" data-link="<?php echo esc_attr( $matches_link ); ?>" data-link-id="<?php echo esc_attr( $match_date ); ?>" data-link-type="matches">
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
				if ( ! empty( $tournament_matches ) ) {
					?>
					<h3 class="header"><?php esc_html_e( 'Match Schedule', 'racketmanager' ); ?></h3>
					<?php require RACKETMANAGER_PATH . 'templates/includes/nav-pills.php'; ?>
					<ul class="match-group">
						<?php
						foreach ( $tournament_matches as $key => $matches ) {
                            if ( '99:99' === $key ) {
                                $key = __( 'Unscheduled', 'racketmanager' );
							}
							?>
							<li class="match-group__item">
                                <div class="match-group__wrapper">
                                    <h5 class="match-group__header"><?php echo esc_html( $key ); ?></h5>
                                    <ul class="match-group">
										<?php
										foreach ( $matches as $match ) {
											$match_display = 'list';
											require RACKETMANAGER_PATH . 'templates/tournament/match.php';
										}
										?>
                                    </ul>
                                </div>
							</li>
							<?php
						}
						?>
					</ul>
					<?php
				} else {
					esc_html_e( 'No matches', 'racketmanager' );
				}
				?>
			</div>
		</div>
	</div>
</div>
