<?php
/**
 *
 * Template page for the Archive
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *  $leagues: array of all leagues
 *  $league: current league
 *  $seasons: array of all seasons
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

global $wp_query, $wp;
$post_id   = $wp_query->post->ID; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$archive   = true;
$match_day = \get_query_var( 'match_day' );
if ( '0' === $match_day ) {
	$match_day = '-1';
	\set_query_var( 'match_day', '-1' );
}
if ( empty( $tab ) ) {
	if ( isset( $wp->query_vars['player_id'] ) ) {
		$tab = 'players'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	} elseif ( isset( $wp->query_vars['team'] ) ) {
		$tab = 'teams'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	} else {
		$tab = 'standings'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
}
if ( isset( $_GET['match_day'] ) || isset( $_GET['team_id'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$tab = 'matches'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
}
if ( $match_day ) {
	$tab = 'matches'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
}
wp_enqueue_style( 'datatables-style' );
wp_enqueue_script( 'datatables' );
?>
<div id="archive-<?php echo esc_html( $league->id ); ?>" class="archive">
	<script type="text/javascript">
	var tab = '<?php echo esc_html( $tab ); ?>;'
	var hash = window.location.hash.substr(1);
	if (hash == 'teams') tab = 'teams';
	jQuery(function() {
		activaTab('<?php echo esc_html( $tab ); ?>');
	});
	jQuery(document).ready(function(){
		jQuery('#playerstats').DataTable( {
			"columnDefs": [
				{ "visible": false, "targets": 7 },
				{ "visible": false, "targets": 10 }
			],
			order: [[ 3, 'desc' ], [ 11, 'desc' ], [ 7, 'desc' ], [ 5, 'desc' ], [ 10, 'desc' ], [ 8, 'desc' ], [ 0, 'asc' ]],
			fixedHeader: {
				header: true,
				footer: true
			},
			"pageLength":25,
			"lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "All"] ],
			"autoWidth": false
		});
	});
	</script>
	<h1><?php echo esc_html( $league->title ) . ' - ' . esc_html__( 'Season', 'racketmanager' ) . ' ' . esc_html( $league->current_season['name'] ); ?></h1>
	<?php require 'league-selections.php'; ?>

	<?php if ( 'championship' === $league->mode ) { ?>
		<?php racketmanager_championship( 0, array( 'season' => $league->season ) ); ?>
		<?php
	} else {
		?>
		<!-- Nav tabs -->
		<ul class="nav nav-tabs frontend" id="myTab" role="tablist">
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="standings-tab" data-bs-toggle="pill" data-bs-target="#standings" type="button" role="tab" aria-controls="standings" aria-selected="true"><?php esc_html_e( 'Standings', 'racketmanager' ); ?></button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="crosstable-tab" data-bs-toggle="pill" data-bs-target="#crosstable" type="button" role="tab" aria-controls="crosstable" aria-selected="false"><?php esc_html_e( 'Crosstable', 'racketmanager' ); ?></button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="matches-tab" data-bs-toggle="pill" data-bs-target="#matches" type="button" role="tab" aria-controls="matches" aria-selected="false"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></button>
			</li>
			<li class="nav-item" role="presentation">
				<?php
				if ( ! empty( $wp->query_vars['team'] ) ) {
					?>
					<a href="/<?php echo esc_attr( $league->event->competition->type ); ?>/<?php echo esc_attr( seo_url( $league->title ) ); ?>/<?php echo esc_html( $league->current_season['name'] ); ?>/teams">
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
					<a href="/<?php echo esc_attr( $league->event->competition->type ); ?>/<?php echo esc_attr( seo_url( $league->title ) ); ?>/<?php echo esc_html( $league->current_season['name'] ); ?>/players">
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
		</ul>
		<!-- Tab panes -->
		<div class="tab-content">
			<div class="tab-pane fade" id="standings" role="tabpanel" aria-labelledby="standings-tab">
				<?php
				racketmanager_standings(
					0,
					array(
						'season'   => get_current_season(),
						'template' => get_league_template( 'standingstable' ),
					)
				);
				?>
			</div>
			<div class="tab-pane fade" id="crosstable" role="tabpanel" aria-labelledby="crosstable-tab">
				<?php
				racketmanager_crosstable(
					0,
					array(
						'season'   => get_current_season(),
						'template' => get_league_template( 'crosstable' ),
					)
				);
				?>
			</div>
			<div class="tab-pane fade" id="matches" role="tabpanel" aria-labelledby="matches-tab">
				<?php
				racketmanager_matches(
					0,
					array(
						'season'                   => get_current_season(),
						'match_day'                => 'current',
						'show_match_day_selection' => 'true',
						'template'                 => get_league_template( 'matches' ),
						'template_type'            => get_match_template_type(),
					)
				);
				?>
			</div>
			<div class="tab-pane fade" id="teams" role="tabpanel" aria-labelledby="teams-tab">
				<?php
				racketmanager_teams(
					0,
					array(
						'season'   => get_current_season(),
						'template' => get_league_template( 'teams' ),
					)
				);
				?>
			</div>
			<div class="tab-pane fade" id="players" role="tabpanel" aria-labelledby="players-tab">
				<?php racketmanager_players( 0, array( 'season' => get_current_season() ) ); ?>
			</div>
		</div>
	<?php } ?>
</div>
