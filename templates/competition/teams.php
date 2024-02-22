<?php
/**
 * Template for competition teams
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

$competition->set_season();
$competition->teams = $competition->get_teams(
	array(
		'season'  => $competition->current_season['name'],
		'orderby' => array( 'name' => 'ASC' ),
		'players' => true,
	)
);
if ( empty( $competition_team ) ) {
	if ( ! empty( $competition->teams ) ) {
		?>
		<div class="module module--card">
			<div class="module__banner">
				<h3 class="module__title"><?php esc_html_e( 'Teams', 'racketmanager' ); ?></h3>
			</div>
			<div class="module__content">
				<div class="module-container">
					<div class="col-12 col-lg-9">
						<div class="row mb-2 row-header">
							<div class="col-4">
								<?php esc_html_e( 'Team', 'racketmanager' ); ?>
							</div>
							<div class="col-4">
								<?php esc_html_e( 'Club', 'racketmanager' ); ?>
							</div>
							<div class="col-3">
								<?php esc_html_e( 'League', 'racketmanager' ); ?>
							</div>
							<div class="col-1 text-end">
								<?php esc_html_e( 'Players', 'racketmanager' ); ?>
							</div>
						</div>
						<?php
						foreach ( $competition->teams as $team ) {
							?>
							<div class="row mb-2 row-list">
								<div class="col-4" name="<?php esc_html_e( 'Team', 'racketmanager' ); ?>">
									<a href="/league/<?php echo esc_html( seo_url( $team->league_title ) ); ?>/<?php echo esc_attr( $competition->current_season['name'] ); ?>/team/<?php echo esc_attr( seo_url( $team->name ) ); ?>/">
										<?php echo esc_html( $team->name ); ?>
									</a>
								</div>
								<div class="col-4" name="<?php esc_html_e( 'club', 'racketmanager' ); ?>">
									<a href="/league/<?php echo esc_html( seo_url( $competition->name ) ); ?>/<?php echo esc_attr( $competition->current_season['name'] ); ?>/club/<?php echo esc_attr( seo_url( $team->club->shortcode ) ); ?>/">
										<?php echo esc_html( $team->club->name ); ?>
									</a>
								</div>
								<div class="col-3" name="<?php esc_html_e( 'league', 'racketmanager' ); ?>">
									<a href="/league/<?php echo esc_html( seo_url( $team->league_title ) ); ?>/<?php echo esc_attr( $competition->current_season['name'] ); ?>/">
										<?php echo esc_html( $team->league_title ); ?>
									</a>
								</div>
								<div class="col-1 text-end">
									<?php echo esc_html( count( $team->players ) ); ?>
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
	<div class="module module--card">
		<div class="module__banner">
			<h3 class="module__title"><?php echo esc_html( $competition_club->name ); ?></h3>
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
