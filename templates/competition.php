<?php
/**
 *
 * Template page to display competition
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $competition */
global $wp_query, $wp;
$post_id     = $wp_query->post->ID ?? ''; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$pagename    = seo_url( $competition->name );
$is_singular = false;
if ( empty( $tab ) ) {
	if ( isset( $wp->query_vars['player_id'] ) ) {
		$is_singular = true;
		$tab         = 'players'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	} elseif ( isset( $wp->query_vars['club_name'] ) ) {
		$is_singular = true;
		$tab         = 'clubs'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	} elseif ( isset( $wp->query_vars['team'] ) ) {
		$is_singular = true;
		$tab         = 'team'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	} else {
		$tab = 'overview'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
}
$menu_options             = array();
$menu_options['overview'] = array(
	'name'        => 'overview',
	'selected'    => 'overview' === $tab,
	'available'   => true,
	'description' => __( 'Overview', 'racketmanager' ),
);
$menu_options['events']   = array(
	'name'        => 'events',
	'selected'    => 'events' === $tab,
	'available'   => true,
	'description' => __( 'Events', 'racketmanager' ),
);
$menu_options['clubs']    = array(
	'name'        => 'clubs',
	'selected'    => 'clubs' === $tab,
	'available'   => true,
	'description' => __( 'Clubs', 'racketmanager' ),
);
$menu_options['teams']    = array(
	'name'        => 'teams',
	'selected'    => 'teams' === $tab,
	'available'   => true,
	'description' => __( 'Teams', 'racketmanager' ),
);
$menu_options['players']  = array(
	'name'        => 'players',
	'selected'    => 'players' === $tab,
	'available'   => true,
	'description' => __( 'Players', 'racketmanager' ),
);
$menu_options['winners']  = array(
	'name'        => 'winners',
	'selected'    => 'winners' === $tab,
	'available'   => empty( $competition_season['date_end'] ) || gmdate( 'Y-m-d' ) >= $competition_season['date_end'],
	'description' => __( 'Winners', 'racketmanager' ),
);
?>
<div class="container">
	<?php require 'includes/competition-header.php'; ?>
	<div id="pageContentTab">
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
								$singular_class = $option['selected'] && $is_singular ? 'is-singular' : null;
								?>
								<li class="nav-item" role="presentation">
									<button class="nav-link tabData <?php echo $option['selected'] ? 'active' : null; ?> <?php echo esc_attr( $singular_class ); ?>" id="<?php echo esc_attr( $option['name'] ); ?>-tab" data-bs-toggle="pill" data-bs-target="#<?php echo esc_attr( $option['name'] ); ?>" type="button" role="tab" aria-controls="<?php echo esc_attr( $option['name'] ); ?>" aria-selected="<?php echo esc_attr( $option['selected'] ); ?>" data-type="competition" data-type-id="<?php echo esc_attr( $competition->id ); ?>" data-season="<?php echo esc_attr( $competition->current_season['name'] ); ?>" data-name="<?php echo esc_attr( seo_url( $competition->name ) ); ?>" data-competition-type=""><?php echo esc_attr( $option['description'] ); ?></button>
								</li>
								<?php
							}
						}
						?>
					</ul>
				</div>
			</div>
		</nav>
		<div class="tab-content" id="competitionTabContent">
			<?php require RACKETMANAGER_PATH . 'templates/includes/loading.php'; ?>
			<?php
			foreach ( $menu_options as $option ) {
				if ( $option['available'] ) {
					?>
					<div class="tab-pane <?php echo $option['selected'] ? 'active' : 'fade'; ?>" id="<?php echo esc_attr( $option['name'] ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $option['name'] ); ?>-tab">
						<?php
						if ( $option['selected'] ) {
							$function_name = 'Racketmanager\racketmanager_competition_' . $option['name'];
							if ( function_exists( $function_name ) ) {
								$function_name( $competition->id, array( 'season' => $competition->current_season['name'] ) );
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
<script type="text/javascript">
	<?php require RACKETMANAGER_PATH . 'js/tab-data.js'; ?>
</script>

