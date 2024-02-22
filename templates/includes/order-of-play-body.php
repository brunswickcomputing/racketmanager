<?php
/**
 * Template for order of play body
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
		<div id="order-of-play">
			<ol class="match-group">
				<?php
				foreach ( $order_of_play as $match_time => $court_matches ) {
					?>
					<li class="match-group__item" id="<?php echo esc_html( $match_time ); ?>">
						<div class="match-group__wrapper">
							<h4 class="match-group__header">
								<?php echo esc_html( $match_time ); ?>
							</h4>
						</div>
							<ol class="match-group">
								<?php
								foreach ( $court_matches as $match ) {
									$match = get_match( $match->id );
									?>
									<li class="match-group__item">
										<?php
										$match_display      = 'list';
										$location_in_header = true;
										require RACKETMANAGER_PATH . '/templates/tournament/match.php';
										?>
									</li>
									<?php
								}
								?>
							</ol>
					</li>
					<?php
				}
				?>
			</ul>
		</div>
