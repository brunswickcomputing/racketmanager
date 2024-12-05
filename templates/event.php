<?php
/**
 *
 * Template page for the Event
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *
 *  $leagues: array of all leagues
 *  $curr_league: current league
 *  $seasons: array of all seasons
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

global $racketmanager_shortcodes, $racketmanager, $wp;

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
		$tab         = 'teams'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	} elseif ( $event->competition->is_championship ) {
		$tab = 'draw'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	} else {
		$tab = 'standings'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
}
$menu_options = array();
if ( $event->competition->is_championship ) {
	$menu_options['draw'] = array(
		'name'        => 'draw',
		'selected'    => 'draw' === $tab ? true : false,
		'available'   => true,
		'template'    => null,
		'description' => __( 'Draw', 'racketmanager' ),
	);
} else {
	$menu_options['standings'] = array(
		'name'        => 'standings',
		'selected'    => 'standings' === $tab ? true : false,
		'available'   => true,
		'template'    => null,
		'description' => __( 'Standings', 'racketmanager' ),
	);
}
if ( $event->competition->is_championship ) {
	$menu_options['matches'] = array(
		'name'        => 'matches',
		'selected'    => 'matches' === $tab ? true : false,
		'available'   => true,
		'template'    => null,
		'description' => __( 'Matches', 'racketmanager' ),
	);
} elseif ( ! $event->is_box ) {
	$menu_options['clubs'] = array(
		'name'        => 'clubs',
		'selected'    => 'clubs' === $tab ? true : false,
		'available'   => true,
		'template'    => null,
		'description' => __( 'Clubs', 'racketmanager' ),
	);
}
$menu_options['teams'] = array(
	'name'        => 'teams',
	'selected'    => 'teams' === $tab ? true : false,
	'available'   => true,
	'template'    => $event->competition->is_championship ? 'list' : null,
	'description' => __( 'Teams', 'racketmanager' ),
);
if ( ! $event->is_box ) {
	$menu_options['players'] = array(
		'name'        => 'players',
		'selected'    => 'players' === $tab ? true : false,
		'available'   => true,
		'template'    => null,
		'description' => __( 'Players', 'racketmanager' ),
	);
}

?>
<div id="leaguetables">
	<?php
	require RACKETMANAGER_PATH . 'templates/includes/event-header.php';
	?>
	<div>
		<nav class="navbar navbar-expand-lg">
			<div class="">
				<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse navbar-collapse mt-3" id="navbarSupportedContent">
					<ul class="nav nav-pills frontend" id="myTab" role="tablist">
						<?php
						foreach ( $menu_options as $option ) {
							if ( $option['available'] ) {
								$singular_class = $option['selected'] && $is_singular ? 'is-singular' : null;
								?>
								<li class="nav-item" role="presentation">
									<button class="nav-link <?php echo $option['selected'] ? 'active' : null; ?> <?php echo esc_attr( $singular_class ); ?>" id="<?php echo esc_attr( $option['name'] ); ?>-tab" data-bs-toggle="pill" data-bs-target="#<?php echo esc_attr( $option['name'] ); ?>" type="button" role="tab" aria-controls="<?php echo esc_attr( $option['name'] ); ?>" aria-selected="<?php echo esc_attr( $option['selected'] ); ?>" onclick="Racketmanager.eventTabData(event,<?php echo esc_attr( $event->id ); ?>,'<?php echo esc_attr( $event->current_season['name'] ); ?>','<?php echo esc_attr( seo_url( $event->name ) ); ?>','<?php echo esc_attr( $event->competition->type ); ?>')"><?php echo esc_attr( $option['description'] ); ?></button>
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
	<div class="tab-content" id="eventTabContent">
		<?php require RACKETMANAGER_PATH . 'templates/include/loading.php'; ?>
		<?php
		foreach ( $menu_options as $option ) {
			if ( $option['available'] ) {
				?>
				<div class="tab-pane <?php echo $option['selected'] ? 'active' : 'fade'; ?>" id="<?php echo esc_attr( $option['name'] ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $option['name'] ); ?>-tab">
					<?php
					if ( $option['selected'] ) {
						$function_name = 'Racketmanager\racketmanager_event_' . $option['name'];
						if ( function_exists( $function_name ) ) {
							$function_name(
								$event->id,
								array(
									'season'   => $event->current_season['name'],
									'template' => $option['template'],
								)
							);
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
