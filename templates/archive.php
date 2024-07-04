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
$pagename  = '/' . $league->event->competition->type . '/' . seo_url( $league->title ) . '/';
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
if ( $league->event->is_box ) {
	$season_title     = __( 'Round', 'racketmanager' );
	$season_selection = __( 'Rounds', 'racketmanager' );
} else {
	$season_title     = __( 'Season', 'racketmanager' );
	$season_selection = __( 'Seasons', 'racketmanager' );
}
?>
<div id="archive-<?php echo esc_html( $league->id ); ?>" class="archive">
	<script type="text/javascript">
	var tab = '<?php echo esc_html( $tab ); ?>;'
	var hash = window.location.hash.substr(1);
	if (hash == 'teams') tab = 'teams';
	jQuery(function() {
		activaTab('<?php echo esc_html( $tab ); ?>');
	});
	</script>
	<div class="module module--card module--dark">
		<div class="module__content">
			<div class="module__banner">
				<div class="module__title">
					<h1>
						<span><?php echo esc_html( $league->title ) . ' - ' . esc_html( $season_title ) . ' ' . esc_html( $league->current_season['name'] ); ?></span>
						<?php
						$favourite_type = 'league';
						$favourite_id   = $league->id;
						require 'includes/favourite-button.php';
						?>
					</h1>
				</div>
				<div id="racketmanager_archive_selections" class="module__aside">
					<form method="get" action="<?php echo esc_html( get_permalink( $post_id ) ); ?>" id="racketmanager_competititon_archive">
						<input type="hidden" name="page_id" value="<?php echo esc_html( $post_id ); ?>" />
						<input type="hidden" name="pagename" id="pagename" value="<?php echo esc_html( $pagename ); ?>" />
						<div class="row g-1 align-items-center">
							<div class="form-floating col-auto">
								<select class="form-select" size="1" name="season" id="season">
									<?php
									foreach ( array_reverse( $seasons ) as $key => $season ) {
										if ( $league->event->is_box ) {
											$option_name = $season_title . ' - ';
										} else {
											$option_name = '';
										}
										$option_name .= $season['name'];
										?>
										<option value="<?php echo esc_attr( $season['name'] ); ?>"
											<?php
											if ( $season['name'] === $league->current_season['name'] ) {
												echo ' selected="selected"';
											}
											?>
										><?php echo esc_html( $option_name ); ?></option>
										<?php
									}
									?>
								</select>
								<label for="season"><?php echo esc_html( $season_selection ); ?></label>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
	<?php require 'league-selections.php'; ?>

	<?php
	if ( $league->event->competition->is_championship ) {
		?>
		<?php racketmanager_championship( 0, array( 'season' => $league->season ) ); ?>
		<?php
	} else {
		?>
		<nav class="navbar navbar-expand-lg">
			<div class="">
				<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse navbar-collapse mt-3" id="navbarSupportedContent">
					<!-- Nav tabs -->
					<ul class="nav nav-pills frontend" id="myTab" role="tablist">
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
								if ( $league->event->is_box ) {
									$season_ref = __( 'round', 'racketmanager' ) . '-' . $league->current_season['name'];
								} else {
									$season_ref = $league->current_season['name'];
								}
								?>
								<a href="/<?php echo esc_attr( $league->event->competition->type ); ?>/<?php echo esc_attr( seo_url( $league->title ) ); ?>/<?php echo esc_html( $season_ref ); ?>/teams">
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
						<?php
						if ( $league->event->competition->is_team_entry ) {
							?>
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
							<?php
						}
						?>
					</ul>
				</div>
			</div>
		</nav>
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
			<?php
			if ( $league->event->competition->is_team_entry ) {
				?>
				<div class="tab-pane fade" id="players" role="tabpanel" aria-labelledby="players-tab">
					<?php racketmanager_players( 0, array( 'season' => get_current_season() ) ); ?>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}
	?>
</div>
