<?php
/**
 *
 * Template page to display competition
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

global $wp_query, $wp;
$post_id  = isset( $wp_query->post->ID ) ? $wp_query->post->ID : ''; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$pagename = seo_url( $competition->name );
if ( empty( $tab ) ) {
	if ( isset( $wp->query_vars['player_id'] ) ) {
		$tab = 'players'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	} elseif ( isset( $wp->query_vars['club_name'] ) ) {
		$tab = 'clubs'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	} elseif ( isset( $wp->query_vars['team'] ) ) {
		$tab = 'team'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	} else {
		$tab = 'overview'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
}
?>
<script type="text/javascript">
var tab = '<?php echo esc_html( $tab ); ?>;'
jQuery(function() {
	activaTab('<?php echo esc_html( $tab ); ?>');
});
</script>
<?php require 'includes/competition-header.php'; ?>
<div class="container">
		<nav class="navbar navbar-expand-lg">
			<div class="">
				<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse navbar-collapse mt-3" id="navbarSupportedContent">
					<!-- Nav tabs -->
					<ul class="nav nav-pills frontend" id="myTab" role="tablist">
						<li class="nav-item" role="presentation">
							<button class="nav-link" id="overview-tab" data-bs-toggle="pill" data-bs-target="#overview" type="button" role="tab" aria-controls="overview" aria-selected="true"><?php esc_html_e( 'Overview', 'racketmanager' ); ?></button>
						</li>
						<li class="nav-item" role="presentation">
							<?php
							if ( ! empty( $wp->query_vars['event'] ) ) {
								?>
								<a href="/<?php echo esc_html( $competition->type ); ?>s/<?php echo esc_html( seo_url( $competition->name ) ); ?>/<?php echo esc_html( $competition_season['name'] ); ?>/events/">
								<?php
							}
							?>
							<button class="nav-link" id="events-tab" data-bs-toggle="pill" data-bs-target="#events" type="button" role="tab" aria-controls="events" aria-selected="false"><?php esc_html_e( 'Events', 'racketmanager' ); ?></button>
							<?php
							if ( ! empty( $wp->query_vars['event'] ) ) {
								?>
								</a>
								<?php
							}
							?>
						</li>
						<li class="nav-item" role="presentation">
							<?php
							if ( ! empty( $wp->query_vars['club_name'] ) ) {
								?>
								<a href="/<?php echo esc_html( seo_url( $competition->name ) ); ?>/<?php echo esc_html( $competition_season['name'] ); ?>/clubs/">
								<?php
							}
							?>
							<button class="nav-link" id="clubs-tab" data-bs-toggle="pill" data-bs-target="#clubs" type="button" role="tab" aria-controls="clubs" aria-selected="false"><?php esc_html_e( 'Clubs', 'racketmanager' ); ?></button>
							<?php
							if ( ! empty( $wp->query_vars['club_name'] ) ) {
								?>
								</a>
								<?php
							}
							?>
						</li>
						<li class="nav-item" role="presentation">
							<?php
							if ( ! empty( $wp->query_vars['team'] ) ) {
								?>
								<a href="/<?php echo esc_html( seo_url( $competition->name ) ); ?>/<?php echo esc_html( $competition_season['name'] ); ?>/teams/">
								<?php
							}
							?>
							<button class="nav-link" id="teams-tab" data-bs-toggle="pill" data-bs-target="#teams" type="button" role="tab" aria-controls="teams" aria-selected="false"><?php esc_html_e( 'Teams', 'racketmanager' ); ?></button>
							<?php
							if ( ! empty( $wp->query_vars['team'] ) ) {
								?>
								</a>
								<?php
							}
							?>
						</li>
						<li class="nav-item" role="presentation">
							<?php
							if ( ! empty( $wp->query_vars['player_id'] ) ) {
								?>
								<a href="/<?php echo esc_html( seo_url( $competition->name ) ); ?>/<?php echo esc_html( $competition_season['name'] ); ?>/players/">
								<?php
							}
							?>
							<button class="nav-link" id="players-tab" data-bs-toggle="pill" data-bs-target="#players" type="button" role="tab" aria-controls="players" aria-selected="false"><?php esc_html_e( 'Players', 'racketmanager' ); ?></button>
							<?php
							if ( ! empty( $wp->query_vars['player_id'] ) ) {
								?>
								</a>
								<?php
							}
							?>
						</li>
						<?php
						if ( empty( $competition_season['dateEnd'] ) || gmdate( 'Y-m-d' ) >= $competition_season['dateEnd'] ) {
							?>
							<li class="nav-item" role="presentation">
								<button class="nav-link" id="winners-tab" data-bs-toggle="pill" data-bs-target="#winners" type="button" role="tab" aria-controls="winners" aria-selected="false"><?php esc_html_e( 'Winners', 'racketmanager' ); ?></button>
							</li>
							<?php
						}
						?>
					</ul>
				</div>
			</div>
		</nav>
		<div class="tab-content">
			<div class="tab-pane fade" id="overview" role="tabpanel" aria-labelledby="overview-tab">
				<?php
				racketmanager_competition_overview( $competition->id );
				?>
			</div>
			<div class="tab-pane fade" id="events" role="tabpanel" aria-labelledby="events-tab">
				<?php
				racketmanager_competition_events( $competition->id );
				?>
			</div>
			<div class="tab-pane fade" id="clubs" role="tabpanel" aria-labelledby="clubs-tab">
				<?php
				racketmanager_competition_clubs( $competition->id );
				?>
			</div>
			<div class="tab-pane fade" id="teams" role="tabpanel" aria-labelledby="teams-tab">
				<?php
				racketmanager_competition_teams( $competition->id );
				?>
			</div>
			<div class="tab-pane fade" id="players" role="tabpanel" aria-labelledby="players-tab">
				<?php
				racketmanager_competition_players( $competition->id );
				?>
			</div>
			<div class="tab-pane fade" id="winners" role="tabpanel" aria-labelledby="winners-tab">
				<?php
				racketmanager_competition_winners( $competition->id );
				?>
			</div>
		</div>
</div>
