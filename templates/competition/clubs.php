<?php
/**
 * Template for competition clubs
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

if ( empty( $competition_club ) ) {
	if ( ! empty( $competition->clubs ) ) {
		?>
		<div class="module module--card">
			<div class="module__banner">
				<h3 class="module__title"><?php esc_html_e( 'Clubs', 'racketmanager' ); ?></h3>
			</div>
			<div class="module__content">
				<div class="module-container">
					<div class="col-12 col-md-12 col-lg-6">
						<div class="row mb-2 row-header">
							<div class="col-6">
								<?php esc_html_e( 'Club', 'racketmanager' ); ?>
							</div>
							<div class="col-3 text-end">
								<?php esc_html_e( 'Teams', 'racketmanager' ); ?>
							</div>
							<div class="col-3 text-end">
								<?php esc_html_e( 'Players', 'racketmanager' ); ?>
							</div>
						</div>
						<?php
						foreach ( $competition->clubs as $club ) {
							?>
							<div class="row mb-2 row-list">
								<div class="col-6" name="<?php esc_html_e( 'Club', 'racketmanager' ); ?>">
									<a href="/<?php echo esc_html( seo_url( $competition->name ) ); ?>/<?php echo esc_attr( $competition->current_season['name'] ); ?>/club/<?php echo esc_attr( seo_url( $club->shortcode ) ); ?>/">
										<?php echo esc_html( $club->name ); ?>
									</a>
								</div>
								<div class="col-3 text-end">
									<?php echo esc_html( $club->team_count ); ?>
								</div>
								<div class="col-3 text-end">
									<?php echo esc_html( $club->player_count ); ?>
								</div>
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
	<div class="page-subhead">
		<div class="media">
			<div class="media__wrapper">
				<div class="media__img">
					<span class="profile-icon">
						<span class="profile-icon__abbr">
							<?php
							$words    = explode( ' ', $competition_club->shortcode );
							$initials = null;
							foreach ( $words as $w ) {
								$initials .= $w[0];
							}
							echo esc_html( $initials );
							?>
						</span>
					</span>
				</div>
				<div class="media__content">
					<h3 class="media__title">
						<a href="/clubs/<?php echo esc_attr( seo_url( $competition_club->shortcode ) ); ?>/">
							<?php echo esc_html( $competition_club->name ); ?>
						</a>
					</h3>
					<?php
					if ( ! empty( $competition_club->address ) ) {
						?>
						<span class="media__subheading">
							<span><?php echo esc_html( $competition_club->address ); ?></span>
						</span>
						<?php
					}
					?>
				</div>
				<div class="media__aside">
				</div>
			</div>
		</div>
	</div>
	<div class="page_content row">
		<div class="page-content__main col-12 col-lg-6">
			<?php
			if ( $competition_club->matches ) {
				?>
				<div class="module module--card">
					<div class="module__banner">
						<h3 class="module__title"><?php esc_html_e( 'Upcoming matches', 'racketmanager' ); ?></h3>
					</div>
					<div class="module__content">
						<div class="module-container">
							<div class="module">
								<?php
								$current_club = $competition_club->id;
								$matches_list = $competition_club->matches;
								require RACKETMANAGER_PATH . 'templates/includes/matches-team-list-group.php';
								?>
							</div>
						</div>
					</div>
				</div>
				<?php
			}
			?>
			<div class="module module--card">
				<div class="module__banner">
					<h3 class="module__title"><?php esc_html_e( 'Results', 'racketmanager' ); ?></h3>
				</div>
				<div class="module__content">
					<div class="module-container">
						<div class="module">
							<?php
							$current_club = $competition_club->id;
							$matches_list = $competition_club->results;
							require RACKETMANAGER_PATH . 'templates/includes/matches-team-list-group.php';
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="page-content__sidebar col-12 col-lg-6">
			<div class="module module--card">
				<div class="module__banner">
					<h3 class="module__title"><?php esc_html_e( 'Teams', 'racketmanager' ); ?></h3>
				</div>
				<div class="module__content">
					<div class="module-container">
						<div class="col-12">
							<div class="row mb-2 row-header">
								<div class="col-6">
									<?php esc_html_e( 'Team', 'racketmanager' ); ?>
								</div>
								<div class="col-3">
									<?php esc_html_e( 'League', 'racketmanager' ); ?>
								</div>
								<?php
								if ( 'league' === $competition->type ) {
									?>
									<div class="col-3 text-end">
										<?php esc_html_e( 'Standing', 'racketmanager' ); ?>
									</div>
									<?php
								}
								?>
							</div>
							<?php
							foreach ( $competition_club->teams as $team ) {
								?>
								<div class="row mb-2 row-list">
									<div class="col-6">
										<a href="/<?php echo esc_attr( $competition->type ); ?>/<?php echo esc_html( seo_url( $team->league_title ) ); ?>/<?php echo esc_attr( $competition->current_season['name'] ); ?>/team/<?php echo esc_attr( seo_url( $team->name ) ); ?>/">
											<?php echo esc_html( $team->name ); ?>
										</a>
									</div>
									<div class="col-5">
										<a href="/<?php echo esc_attr( $competition->type ); ?>/<?php echo esc_html( seo_url( $team->league_title ) ); ?>/<?php echo esc_attr( $competition->current_season['name'] ); ?>/">
											<?php echo esc_html( $team->league_title ); ?>
										</a>
									</div>
									<?php
									if ( 'league' === $competition->type ) {
										?>
										<div class="col-1 text-end">
											<?php echo esc_html( $team->rank ); ?>
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
			<div class="module module--card">
				<div class="module__banner">
					<h3 class="module__title"><?php esc_html_e( 'Players', 'racketmanager' ); ?></h3>
				</div>
				<div class="module__content">
					<div class="module-container">
						<div class="col-12">
							<div class="row mb-2 row-header">
								<div class="col-12">
									<?php esc_html_e( 'Player', 'racketmanager' ); ?>
								</div>
							</div>
							<?php
							foreach ( $competition_club->players as $player ) {
								?>
								<div class="row mb-2 row-list">
									<div class="col-12">
										<a href="/<?php echo esc_html( seo_url( $competition->name ) ); ?>/<?php echo esc_attr( $competition->current_season['name'] ); ?>/player/<?php echo esc_attr( seo_url( $player ) ); ?>/">
											<?php echo esc_html( $player ); ?>
										</a>
									</div>
								</div>
								<?php
							}
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}
?>
