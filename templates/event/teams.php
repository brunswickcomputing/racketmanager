<?php
/**
 * Template for event teams
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

if ( empty( $event_team ) ) {
	?>
	<div class="module module--card">
		<div class="module__banner">
			<h3 class="module__title"><?php esc_html_e( 'Teams', 'racketmanager' ); ?></h3>
		</div>
		<div class="module__content">
			<div class="module-container">
				<?php
				if ( ! empty( $event->teams ) ) {
					?>
					<div class="col-12">
						<div class="row mb-2 row-header">
							<div class="col-4">
								<?php esc_html_e( 'Team', 'racketmanager' ); ?>
							</div>
							<?php
							if ( ! $event->is_box ) {
								?>
								<div class="col-4">
									<?php esc_html_e( 'Club', 'racketmanager' ); ?>
								</div>
								<?php
							}
							?>
							<div class="col-3">
								<?php esc_html_e( 'League', 'racketmanager' ); ?>
							</div>
							<?php
							if ( ! $event->is_box ) {
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
						foreach ( $event->teams as $team ) {
							if ( $event->is_box ) {
								$league_link = $event->competition->type . '/' . seo_url( $team->league_title ) . '/' . __( 'round', 'racketmanager' ) . '-' . $event->current_season['name'] . '/';
							} else {
								$league_link = $event->competition->type . '/' . seo_url( $team->league_title ) . '/' . $event->current_season['name'] . '/';
							}
							$club_link = '/' . $event->competition->type . 's/' . seo_url( $event->name ) . '/' . $event->current_season['name'] . '/club/' . seo_url( $team->club->shortcode ) . '/';
							?>
							<div class="row mb-2 row-list">
								<div class="col-4" name="<?php esc_html_e( 'Team', 'racketmanager' ); ?>">
									<a href="/<?php echo esc_attr( $event->competition->type ); ?>/<?php echo esc_html( seo_url( $team->league_title ) ); ?>/<?php echo esc_attr( $event->current_season['name'] ); ?>/team/<?php echo esc_attr( seo_url( $team->name ) ); ?>/">
										<?php echo esc_html( $team->name ); ?>
									</a>
								</div>
								<?php
								if ( ! $event->is_box ) {
									?>
									<div class="col-4" name="<?php esc_html_e( 'club', 'racketmanager' ); ?>">
										<a href="/<?php echo esc_attr( $club_link ); ?>/club/<?php echo esc_attr( seo_url( $team->club->shortcode ) ); ?>/" onclick="Racketmanager.eventTabDataLink(event,<?php echo esc_attr( $event->id ); ?>,<?php echo esc_attr( $event->current_season['name'] ); ?>,'<?php echo esc_attr( $club_link ); ?>',<?php echo esc_attr( $team->club->id ); ?>,'clubs')">
											<?php echo esc_html( $team->club->shortcode ); ?>
										</a>
									</div>
									<?php
								}
								?>
								<div class="col-3" name="<?php esc_html_e( 'league', 'racketmanager' ); ?>">
									<a href="/<?php echo esc_attr( $league_link ); ?>">
										<?php echo esc_html( $team->league_title ); ?>
									</a>
								</div>
								<?php
								if ( ! $event->is_box ) {
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
					<?php
				} else {
					esc_html_e( 'No teams found', 'racketmanager' );
				}
				?>
			</div>
		</div>
	</div>
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
