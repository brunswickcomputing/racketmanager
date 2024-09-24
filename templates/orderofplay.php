<?php
/**
 * Template for tournament order of play
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

$num_courts  = count( $order_of_play['courts'] );
$col_width   = floor( 12 / $num_courts );
$is_expanded = false;
if ( 2 === intval( $col_width ) ) {
	$is_expanded = true;
}
?>
<script>
	jQuery(document).ready(function() {
	});
</script>
<div class="container">
	<div class="module module--card">
		<div class="module__banner">
			<h3 class="module__title"><?php esc_html_e( 'Order of play', 'racketmanager' ); ?></h3>
		</div>
		<div class="module__content">
			<div class="module-container">
				<?php
				if ( ! empty( $order_of_play ) ) {
					?>
					<div id="order-of-play" class="container">
						<div class="row">
							<div class="col-2 col-md-1">
								<div class="row">
									<div class="col-12">
										<h4 class="match-group__header">
											<span><?php esc_html_e( 'Time', 'racketmanager' ); ?></span>
										</h4>
										<?php
										foreach ( $order_of_play['times'] as $time ) {
											?>
											<div class="match-group__item-wrapper time-display<?php echo empty( $is_expanded ) ? null : ' is-expanded'; ?>" id="<?php echo esc_html( $time ); ?>"><?php echo esc_html( $time ); ?>
											</div>
											<?php
										}
										?>
									</div>
								</div>
							</div>
							<div class="col-10 col-md-11">
								<div class="row">
									<?php
									foreach ( $order_of_play['courts'] as $court => $court_times ) {
										?>
										<div class="col-12 col-md-<?php echo esc_attr( $col_width ); ?>" id="<?php echo esc_html( $court ); ?>">
											<h4 class="match-group__header">
												<span><?php echo esc_html( $court ); ?></span>
											</h4>
											<?php
											foreach ( $court_times as $court_time => $matches ) {
												foreach ( $matches as $final_match ) {
													$match = get_match( $final_match->id );
													?>
													<div class="match-group__item-wrapper<?php echo empty( $is_expanded ) ? null : ' is-expanded'; ?>">
														<div class="match-group__item">
															<div class="match__header-title">
																<span><?php echo esc_html( $match->league->title ); ?></span>
															</div>
															<?php
															if ( is_numeric( $match->home_team ) ) {
																$home_match_title = $match->teams['home']->title;
															} else {
																$home_match_title = $match->prev_home_match->match_title;
															}
															if ( is_numeric( $match->away_team ) ) {
																$away_match_title = $match->teams['away']->title;
															} else {
																$away_match_title = $match->prev_away_match->match_title;
															}
															?>
															<div class="match__body-title<?php echo is_numeric( $match->home_team ) ? null : ' is_pending'; ?>">
																<?php echo esc_html( $home_match_title ); ?>
															</div>
															<div class="team-separator"><?php esc_html_e( 'vs', 'racketmanager' ); ?></div>
															<div class="match__body-title<?php echo is_numeric( $match->away_team ) ? null : ' is_pending'; ?>">
																<?php echo esc_html( $away_match_title ); ?>
															</div>
														</div>
														</div>
													<?php
												}
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
					<?php
				} else {
					esc_html_e( 'No order of play', 'racketmanager' );
				}
				?>
			</div>
		</div>
	</div>
</div>
