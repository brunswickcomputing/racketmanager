<?php
/**
 * Template page for Championship
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *  $league: contains data of current league
 *  $championship: championship object
 *  $finals: data for finals
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

global $wp_query, $wp;
$post_id = $wp_query->post->ID; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$archive = true;

if ( empty( $tab ) ) {
	if ( isset( $wp->query_vars['tab'] ) ) {
		$tab = $wp->query_vars['tab']; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	} elseif ( isset( $wp->query_vars['player_id'] ) ) {
		$tab = 'players'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	} elseif ( isset( $wp->query_vars['team'] ) ) {
		$tab = 'teams'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	} else {
		$tab = 'draw'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
}
$tab .= '-' . $league->id; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
?>
<script type='text/javascript'>
var tab = '<?php echo esc_html( $tab ); ?>;'
var hash = window.location.hash.substr(1);
if (hash == 'teams') tab = 'teams-' + <?php echo esc_html( $league->id ); ?>;
jQuery(function() {
	activaTab('<?php echo esc_html( $tab ); ?>');
});
</script>
<!-- Nav tabs -->
<ul class="nav nav-tabs frontend" id="myTab" role="tablist">
	<li class="nav-item" role="presentation">
		<button class="nav-link" id="draw-<?php echo esc_html( $league->id ); ?>-tab" data-bs-toggle="pill" data-bs-target="#draw-<?php echo esc_html( $league->id ); ?>" type="button" role="tab" aria-controls="draw-<?php echo esc_html( $league->id ); ?>" aria-selected="true">
			<?php esc_html_e( 'Draw', 'racketmanager' ); ?>
		</button>
	</li>
	<li class="nav-item" role="presentation">
		<button class="nav-link" id="matches-<?php echo esc_html( $league->id ); ?>-tab" data-bs-toggle="pill" data-bs-target="#matches-<?php echo esc_html( $league->id ); ?>" type="button" role="tab" aria-controls="matches-<?php echo esc_html( $league->id ); ?>" aria-selected="false">
			<?php esc_html_e( 'Matches', 'racketmanager' ); ?>
		</button>
	</li>
	<li class="nav-item" role="presentation">
		<button class="nav-link" id="teams-<?php echo esc_html( $league->id ); ?>-tab" data-bs-toggle="pill" data-bs-target="#teams-<?php echo esc_html( $league->id ); ?>" type="button" role="tab" aria-controls="teams-<?php echo esc_html( $league->id ); ?>" aria-selected="false">
			<?php esc_html_e( 'Teams', 'racketmanager' ); ?>
		</button>
	</li>
	<?php
	if ( $league->event->competition->is_team_entry ) {
		?>
		<?php
		if ( ! empty( $wp->query_vars['player_id'] ) ) {
			?>
			<a href="/<?php echo esc_attr( $league->event->competition->type ); ?>s/<?php echo esc_attr( seo_url( $league->event->name ) ); ?>/<?php echo esc_html( $league->current_season['name'] ); ?>/players/">
			<?php
		}
		?>
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="players-<?php echo esc_html( $league->id ); ?>-tab" data-bs-toggle="pill" data-bs-target="#players-<?php echo esc_html( $league->id ); ?>" type="button" role="tab" aria-controls="players-<?php echo esc_html( $league->id ); ?>" aria-selected="false">
				<?php esc_html_e( 'Players', 'racketmanager' ); ?>
			</button>
		</li>
		<?php
		if ( ! empty( $wp->query_vars['player_id'] ) ) {
			?>
			</a>
			<?php
		}
		?>
		<?php
	}
	?>
</ul>
<!-- Tab panes -->
<div class="tab-content">
	<div class="tab-pane fade" id="draw-<?php echo esc_html( $league->id ); ?>" role="tabpanel" aria-labelledby="draw-tab">
		<h3 class="header"><?php esc_html_e( 'Draw', 'racketmanager' ); ?></h3>
		<?php require 'includes/championship-draw.php'; ?>
	</div>
	<div class="tab-pane fade" id="matches-<?php echo esc_html( $league->id ); ?>" role="tabpanel" aria-labelledby="matches-tab">
		<h3 class="header"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></h3>
		<?php require 'championship-matches.php'; ?>
	</div>
	<div class="tab-pane fade" id="teams-<?php echo esc_html( $league->id ); ?>" role="tabpanel" aria-labelledby="teams-tab">
		<?php
		racketmanager_teams(
			$league->id,
			array(
				'season'   => get_current_season(),
				'template' => 'list',
			)
		);
		?>
	</div>
	<?php
	if ( $league->event->competition->is_team_entry ) {
		?>
		<div class="tab-pane fade" id="players-<?php echo esc_html( $league->id ); ?>" role="tabpanel" aria-labelledby="players-tab">
			<?php racketmanager_players( $league->id, array( 'season' => get_current_season() ) ); ?>
		</div>
	<?php } ?>
</div>
