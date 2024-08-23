<?php
/**
 * Template for winners main body
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

if ( ! $winners ) {
	esc_html_e( 'No winners', 'racketmanager' );
} else {
	?>
	<div id="competition-winners">
		<ul class="list--winner winner-list">
			<?php
			foreach ( $winners as $key => $winner_group ) {
				?>
				<li class="winner-list__cat" id="<?php echo esc_html( $key ); ?>">
					<div class="list-divider"></div>
					<ul class="row list--winner winner-list-type">
						<?php
						foreach ( $winner_group as $winner ) {
							?>
							<li class="winner-list-item col-12 col-sm-6 col-md-4">
								<div>
									<?php
									if ( empty( $tournament ) ) {
										$link_ref = '/' . seo_url( $winner->competition_type ) . '/' . seo_url( $winner->event_name ) . '/' . seo_url( $winner->season ) . '/';
									} else {
										$link_ref = '/tournament/' . seo_url( $tournament->name ) . '/draw/' . seo_url( $winner->event_name ) . '/';
									}
									?>
									<a href="<?php echo esc_html( $link_ref ); ?>">
										<span class="header">
											<?php echo esc_html( $winner->league ); ?>
										</span>
									</a>
									<?php
									if ( empty( $winner->loser ) ) {
										$list_type = 'ul';
									} else {
										$list_type = 'ol';
									}
									?>
									<<?php echo esc_html( $list_type ); ?> class="list-winners">
										<li>
											<span class="team-name"><?php echo esc_html( $winner->winner ); ?></span>
											<?php
											if ( ( ! empty( $tournament ) || ! $winner->is_team_entry ) && $winner->winner_club ) {
												?>
												<span class="player-club">(<?php echo esc_html( $winner->winner_club ); ?>)</span>
												<?php
											}
											?>
										</li>
										<?php
										if ( ! empty( $winner->loser ) ) {
											?>
											<li>
												<span class="team-name"><?php echo esc_html( $winner->loser ); ?></span>
												<?php
												if ( ( ! empty( $tournament ) || ! $winner->is_team_entry ) && $winner->winner_club ) {
													?>
													<span class="player-club">(<?php echo esc_html( $winner->loser_club ); ?>)</span>
													<?php
												}
												?>
											</li>
											<?php
										}
										?>
									</<?php echo esc_html( $list_type ); ?>>
								</div>
							</li>
							<?php
						}
						?>
					</ul>
				</li>
				<?php
			}
			?>
		</ul>
	</div>
	<?php
}
