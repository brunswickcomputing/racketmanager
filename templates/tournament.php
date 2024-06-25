<?php
/**
 *
 * Template page for Tournament
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *  $tournaments: array of all tournaments
 *  $tournament: current tournament
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

global $wp_query, $wp;
$post_id   = $wp_query->post->ID; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$match_day = \get_query_var( 'match_day' );
if ( isset( $_GET['match_day'] ) || isset( $_GET['team_id'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$tab = 'matches'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
}
if ( empty( $tab ) ) {
	if ( ! empty( $event ) ) {
		$tab = 'events'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
	if ( $match_day ) {
		$tab = 'matches'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	} else {
		$tab = 'overview'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
}
?>
<div id="tournament-<?php echo esc_html( $tournament->id ); ?>" class="tournament">
	<script type="text/javascript">
	var tab = '<?php echo esc_html( $tab ); ?>;'
	jQuery(function() {
		activaTab('<?php echo esc_html( $tab ); ?>');
	});
	</script>
	<div class="container">
		<div class="media tournament-head">
			<div class="media__wrapper">
				<div class="media__img"></div>
				<div class="media__content">
					<h1 class="media__title"><?php echo esc_html( $tournament->name ) . ' - ' . esc_html__( 'Tournament', 'racketmanager' ); ?></h1>
					<div class="media__content-subinfo">
						<small class="media__subheading">
							<span class="nav--link">
								<span class="nav-link__value">
									<?php echo esc_html( $tournament->venue_name ); ?>
								</span>
							</span>
						</small>
						<?php
						if ( ! empty( $tournament->date_start ) && ! empty( $tournament->date ) ) {
							?>
							<small class="media__subheading">
								<span class="nav--link">
									<span class="nav-link__value">
										<?php racketmanager_the_svg( 'icon-calendar' ); ?>
										<?php echo esc_html( mysql2date( 'j M', $tournament->date_start ) ); ?> <?php esc_html_e( 'to', 'racketmanager' ); ?> <?php echo esc_html( mysql2date( 'j M', $tournament->date ) ); ?>
									</span>
								</span>
							</small>
							<?php
						}
						?>
					</div>
				</div>
				<div class="media__aside">
					<?php
					if ( 'open' === $tournament->current_phase ) {
						?>
						<a href="/tournaments/entry-form/<?php echo esc_attr( seo_url( $tournament->name ) ); ?>/" class="btn btn-primary reverse">
							<i class="racketmanager-svg-icon">
								<?php racketmanager_the_svg( 'icon-pencil' ); ?>
							</i>
							<span><?php esc_html_e( 'Enter', 'racketmanager' ); ?></span>
						</a>
						<?php
					}
					?>
				</div>
			</div>
		</div>
		<?php require 'tournament-selections.php'; ?>
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
								<a href="/tournament/<?php echo esc_html( seo_url( $tournament->name ) ); ?>/events/">
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
							if ( ! empty( $wp->query_vars['draw'] ) ) {
								?>
								<a href="/tournament/<?php echo esc_html( seo_url( $tournament->name ) ); ?>/draws/">
								<?php
							}
							?>
							<button class="nav-link" id="draws-tab" data-bs-toggle="pill" data-bs-target="#draws" type="button" role="tab" aria-controls="draws" aria-selected="false"><?php esc_html_e( 'Draws', 'racketmanager' ); ?></button>
							<?php
							if ( ! empty( $wp->query_vars['draw'] ) ) {
								?>
								</a>
								<?php
							}
							?>
						</li>
						<li class="nav-item" role="presentation">
							<?php
							if ( 'matches' !== $tab ) {
								?>
								<a href="/tournament/<?php echo esc_html( seo_url( $tournament->name ) ); ?>/matches/">
								<?php
							}
							?>
							<button class="nav-link" id="matches-tab" data-bs-toggle="pill" data-bs-target="#matches" type="button" role="tab" aria-controls="matches" aria-selected="false"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></button>
							<?php
							if ( 'matches' !== $tab ) {
								?>
								</a>
								<?php
							}
							?>
						</li>
						<li class="nav-item" role="presentation">
							<?php
							if ( ! empty( $wp->query_vars['player'] ) ) {
								?>
								<a href="/tournament/<?php echo esc_html( seo_url( $tournament->name ) ); ?>/players/">
								<?php
							}
							?>
							<button class="nav-link" id="players-tab" data-bs-toggle="pill" data-bs-target="#players" type="button" role="tab" aria-controls="players" aria-selected="false"><?php esc_html_e( 'Players', 'racketmanager' ); ?></button>
							<?php
							if ( ! empty( $wp->query_vars['player'] ) ) {
								?>
								</a>
								<?php
							}
							?>
						</li>
						<?php
						if ( ! empty( $tournament->date ) && gmdate( 'Y-m-d' ) > $tournament->date ) {
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
	</div>
	<!-- Tab panes -->
	<div class="tab-content">
		<div class="tab-pane fade" id="overview" role="tabpanel" aria-labelledby="overview-tab">
			<?php
			racketmanager_tournament_overview( $tournament->id );
			?>
		</div>
		<div class="tab-pane fade" id="events" role="tabpanel" aria-labelledby="events-tab">
			<?php
			racketmanager_tournament_events( $tournament->id );
			?>
		</div>
		<div class="tab-pane fade" id="draws" role="tabpanel" aria-labelledby="draws-tab">
			<?php
			racketmanager_tournament_draws( $tournament->id );
			?>
		</div>
		<?php
		if ( 'matches' === $tab ) {
			?>
			<div class="tab-pane fade" id="matches" role="tabpanel" aria-labelledby="matches-tab">
			<?php
			racketmanager_tournament_matches( $tournament->id );
			?>
			</div>
			<?php
		}
		?>
		<div class="tab-pane fade" id="players" role="tabpanel" aria-labelledby="players-tab">
			<?php
			racketmanager_tournament_players( $tournament->id );
			?>
		</div>
		<div class="tab-pane fade" id="winners" role="tabpanel" aria-labelledby="winners-tab">
			<?php
			racketmanager_tournament_winners( $tournament->id );
			?>
		</div>
	</div>
</div>
