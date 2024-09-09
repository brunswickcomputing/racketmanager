<?php
/**
 * Template for competition clubs
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

if ( empty( $competition->player ) ) {
	if ( ! empty( $competition->players ) ) {
		$player_list = $competition->players;
		$player_link = '/' . seo_url( $competition->name ) . '/' . $competition->current_season['name'] . '/player/';
		require RACKETMANAGER_PATH . 'templates/includes/player-list.php';
	}
} else {
	$player = $competition->player;
	require RACKETMANAGER_PATH . 'templates/includes/player-header.php';
	?>
	<div class="page_content row">
		<div class="page-content__main col-12 col-lg-6">
			<div class="module module--card">
				<div class="module__banner">
					<h3 class="module__title"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></h3>
				</div>
				<div class="module__content">
					<div class="module-container">
						<div class="module">
							<?php
							foreach ( $player->matches as $key => $player_matches ) {
								$league = $player_matches['league'];
								?>
								<h4 class="module-divider">
									<span class="module-divider__body">
										<a href="/<?php echo esc_attr( $league->event->competition->type ); ?>s/<?php echo esc_attr( seo_url( $league->event->name ) ); ?>/<?php echo esc_attr( $league->season ); ?>/">
											<span class="nav-link__value">
												<?php echo esc_html( $league->event->name ); ?>
											</span>
										</a>&nbsp;&#8226;&nbsp;
										<a href="/<?php echo esc_attr( $league->event->competition->type ); ?>/<?php echo esc_attr( seo_url( $league->title ) ); ?>/<?php echo esc_attr( $league->season ); ?>/">
											<span class="nav-link__value">
												<?php echo esc_html( $league->title ); ?>
											</span>
										</a>
									</span>
								</h4>
								<?php
								foreach ( $player_matches['matches'] as $match ) {
									echo $racketmanager->show_match_screen( $match, false, $competition->player ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
							}
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="page-content__sidebar col-12 col-lg-6">
			<?php
			if ( ! empty( $competition->player->statistics ) ) {
				$player_statistics = $competition->player->statistics;
				require RACKETMANAGER_PATH . 'templates/includes/player-statistics.php';
			}
			?>
		</div>
	</div>
	<?php
}
?>
