<?php
/**
 * Template for event teams
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

if ( empty( $event_team ) ) {
	if ( ! empty( $competition->teams ) ) {
		?>
		<div class="module module--card">
			<div class="module__banner">
				<h3 class="module__title"><?php esc_html_e( 'Teams', 'racketmanager' ); ?></h3>
			</div>
			<div class="module__content">
				<div class="module-container">
					<div class="col-12">
						<div class="row mb-2 row-header">
							<div class="col-4">
								<?php esc_html_e( 'Team', 'racketmanager' ); ?>
							</div>
							<div class="col-4">
								<?php esc_html_e( 'Club', 'racketmanager' ); ?>
							</div>
						<?php
						if ( $competition->is_championship ) {
							?>
							<div class="col-3">
								<?php esc_html_e( 'Draw', 'racketmanager' ); ?>
							</div>
							<?php
						} else {
							?>
							<div class="col-3">
								<?php esc_html_e( 'League', 'racketmanager' ); ?>
							</div>
							<?php
						}
						?>
						<?php
						if ( $competition->is_team_entry ) {
							?>
							<div class="d-none d-sm-block col-1 text-end">
								<?php esc_html_e( 'Players', 'racketmanager' ); ?>
							</div>
							<div class="d-sm-none col-1 text-end">
								<?php esc_html_e( 'Pls', 'racketmanager' ); ?>
							</div>
							<?php
						}
						?>
						</div>
						<?php
						foreach ( $competition->teams as $team ) {
							$league_link = $competition->type . '/' . seo_url( $team->league_title ) . '/' . $competition->current_season['name'] . '/';
							?>
							<div class="row mb-2 row-list">
								<div class="col-4" name="<?php esc_html_e( 'Team', 'racketmanager' ); ?>">
									<a href="/<?php echo esc_attr( $competition->type ); ?>/<?php echo esc_html( seo_url( $team->league_title ) ); ?>/<?php echo esc_attr( $competition->current_season['name'] ); ?>/team/<?php echo esc_attr( seo_url( $team->name ) ); ?>/">
										<?php echo esc_html( $team->name ); ?>
									</a>
								</div>
								<div class="col-4" name="<?php esc_html_e( 'club', 'racketmanager' ); ?>">
									<a href="/<?php echo esc_html( seo_url( $competition->name ) ); ?>/<?php echo esc_attr( $competition->current_season['name'] ); ?>/club/<?php echo esc_attr( seo_url( $team->club->shortcode ) ); ?>/">
										<?php echo esc_html( $team->club->name ); ?>
									</a>
								</div>
								<?php
								if ( ! $competition->is_championship ) {
									?>
									<div class="col-3" name="<?php esc_html_e( 'league', 'racketmanager' ); ?>">
										<a href="/<?php echo esc_attr( $league_link ); ?>">
											<?php echo esc_html( $team->league_title ); ?>
										</a>
									</div>
									<?php
								}
								?>
								<?php
								if ( $competition->is_team_entry ) {
									?>
									<div class="col-1 text-end">
										<?php echo esc_html( $team->player_count ); ?>
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
		<?php
	}
	?>
	<?php
} else {
	?>
	<div class="module module--card">
		<div class="module__banner">
			<h3 class="module__title"><?php echo esc_html( $event_club->name ); ?></h3>
		</div>
		<div class="module__content">
			<div class="module-container">
				<div class="module">
				</div>
			</div>
		</div>
	</div>
	<?php
}
?>
