<?php
/**
 * Template for winners main body
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

if ( ! $winners ) {
	esc_html_e( 'No winners', 'racketmanager' );
} elseif ( ! empty( $tournament ) ) {
	?>
	<div id="tournament-winners">
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
									<a href="/tournament/<?php echo esc_html( seo_url( $tournament->name ) ); ?>/draws/<?php echo esc_html( seo_url( $winner->event_name ) ); ?>">
										<span class="header">
											<?php echo esc_html( $winner->league ); ?>
										</span>
									</a>
									<ol class="list-winners">
										<li>
											<?php
												$team         = new \stdclass();
												$team->title  = $winner->winner;
												$team->id     = $winner->winner_id;
												$team->player = $winner->player['winner'];
												require 'championship-draw-team.php';
											?>
											<?php
											if ( ! empty( $tournament ) ) {
												echo '(' . esc_html( $winner->winner_club ) . ')';
											}
											?>
										</li>
										<li>
											<?php
												$team         = new \stdclass();
												$team->title  = $winner->loser;
												$team->id     = $winner->loser_id;
												$team->player = $winner->player['loser'];
												require 'championship-draw-team.php';
											?>
											<?php
											if ( ! empty( $tournament ) ) {
												echo '(' . esc_html( $winner->loser_club ) . ')';
											}
											?>
										</li>
									</dl>
								</div>
							</li>
						<?php } ?>
					</ul>
				</li>
			<?php } ?>
		</ul>
	</div>
	<?php
} else {
	foreach ( $winners as $winner ) {
		?>
		<div id="winners-list">
			<h4 class="header"><?php echo esc_html( $winner->league ); ?></h4>
			<dl>
				<dd><?php esc_html_e( 'Winner', 'racketmanager' ); ?></dd>
				<dt><?php echo esc_html( $winner->winner ); ?>
				<?php
				if ( ! empty( $tournament ) ) {
					echo ' (' . esc_html( $winner->winner_club ) . ')';
				}
				?>
				</dt>
				<dd><?php esc_html_e( 'Runner-up', 'racketmanager' ); ?></dd>
				<dt><?php echo esc_html( $winner->loser ); ?>
					<?php
					if ( ! empty( $tournament ) ) {
						echo ' (' . esc_html( $winner->loser_club ) . ')';
					}
					?>
				</dt>
			</dl>
		</div>
		<?php
	}
}
