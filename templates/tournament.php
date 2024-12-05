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
$menu_options                = array();
$menu_options['overview']    = array(
	'name'        => 'overview',
	'selected'    => 'overview' === $tab ? true : false,
	'available'   => true,
	'description' => __( 'Overview', 'racketmanager' ),
);
$menu_options['events']      = array(
	'name'        => 'events',
	'selected'    => 'events' === $tab ? true : false,
	'available'   => true,
	'description' => __( 'Events', 'racketmanager' ),
);
$menu_options['draws']       = array(
	'name'        => 'draws',
	'selected'    => 'draws' === $tab ? true : false,
	'available'   => true,
	'description' => __( 'Draws', 'racketmanager' ),
);
$menu_options['matches']     = array(
	'name'        => 'matches',
	'selected'    => 'matches' === $tab ? true : false,
	'available'   => true,
	'description' => __( 'Matches', 'racketmanager' ),
);
$menu_options['players']     = array(
	'name'        => 'players',
	'selected'    => 'players' === $tab ? true : false,
	'available'   => true,
	'description' => __( 'Players', 'racketmanager' ),
);
$menu_options['orderofplay'] = array(
	'name'        => 'orderofplay',
	'selected'    => 'orderofplay' === $tab ? true : false,
	'available'   => ( ! empty( $tournament->date ) && gmdate( 'Y-m-d' ) <= $tournament->date && ! empty( $tournament->orderofplay ) ) ? true : false,
	'description' => __( 'Order of play', 'racketmanager' ),
);
$menu_options['winners']     = array(
	'name'        => 'winners',
	'selected'    => 'winners' === $tab ? true : false,
	'available'   => ( ! empty( $tournament->date ) && gmdate( 'Y-m-d' ) >= $tournament->date ) ? true : false,
	'description' => __( 'Winners', 'racketmanager' ),
);
?>
<div id="tournament-<?php echo esc_html( $tournament->id ); ?>" class="tournament">
	<div id="pageContentTab">
		<div class="container">
			<?php
			$entry_option = true;
			require 'includes/tournament-header.php';
			?>
			<?php require 'tournament-selections.php'; ?>
			<nav class="navbar navbar-expand-lg">
				<div class="">
					<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
						<span class="navbar-toggler-icon"></span>
					</button>
					<div class="collapse navbar-collapse mt-3" id="navbarSupportedContent">
						<!-- Nav tabs -->
						<ul class="nav nav-pills frontend" id="myTab" role="tablist">
							<?php
							foreach ( $menu_options as $option ) {
								if ( $option['available'] ) {
									?>
									<li class="nav-item" role="presentation">
										<button class="nav-link <?php echo $option['selected'] ? 'active' : null; ?>" id="<?php echo esc_attr( $option['name'] ); ?>-tab" data-bs-toggle="pill" data-bs-target="#<?php echo esc_attr( $option['name'] ); ?>" type="button" role="tab" aria-controls="<?php echo esc_attr( $option['name'] ); ?>" aria-selected="<?php echo esc_attr( $option['selected'] ); ?>" onclick="Racketmanager.tournamentTabData(event,<?php echo esc_attr( $tournament->id ); ?>,'<?php echo esc_attr( seo_url( $tournament->name ) ); ?>')"><?php echo esc_attr( $option['description'] ); ?></button>
									</li>
									<?php
								}
							}
							?>
						</ul>
					</div>
				</div>
			</nav>
		</div>
		<!-- Tab panes -->
		<div class="tab-content" id="tournamentTabContent">
			<?php require RACKETMANAGER_PATH . 'templates/includes/loading.php'; ?>
			<?php
			foreach ( $menu_options as $option ) {
				if ( $option['available'] ) {
					?>
					<div class="tab-pane <?php echo $option['selected'] ? 'active' : 'fade'; ?>" id="<?php echo esc_attr( $option['name'] ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $option['name'] ); ?>-tab">
						<?php
						if ( $option['selected'] ) {
							$function_name = 'Racketmanager\racketmanager_tournament_' . $option['name'];
							if ( function_exists( $function_name ) ) {
								$function_name( $tournament->id, array() );
							} else {
								/* translators: %s: function name */
								printf( esc_html__( 'function %s does not exist', 'racketmanager' ), esc_attr( $function_name ) );
							}
						}
						?>
					</div>
					<?php
				}
			}
			?>
		</div>
	</div>
</div>
