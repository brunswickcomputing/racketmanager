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
switch ( $league->event->competition->type ) {
	case 'league':
		$image = 'images/bootstrap-icons.svg#table';
		break;
	case 'cup':
		$image = 'images/bootstrap-icons.svg#trophy-fill';
		break;
	case 'tournament':
		$image = 'images/lta-icons.svg#icon-bracket';
		break;
	default:
		$image = null;
		break;
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
	<div class="page-subhead competition">
		<div class="media competition-head">
			<div class="media__wrapper">
				<div class="media__img">
					<svg width="16" height="16" class="media__img-element--icon">
						<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . $image ); ?>"></use>
					</svg>
				</div>
				<div class="media__content">
					<h1 class="media__title"><?php echo esc_html( $league->title ); ?></h1>
					<div class="media__content-subinfo">
						<?php
						if ( ! empty( $league->event->name ) ) {
							?>
							<small class="media__subheading">
								<span class="nav--link">
									<a href="/<?php echo esc_html( seo_url( $league->event->competition->type ) ); ?>s/<?php echo esc_html( seo_url( $league->event->name ) ); ?>/<?php echo esc_html( $league->current_season['name'] ); ?>/">
										<span class="nav-link__value">
											<?php echo esc_html( $league->event->name ); ?>
										</span>
									</a>
								</span>
								<span>&nbsp;&#8226&nbsp;</span>
								<span class="nav--link">
									<a href="/<?php echo esc_html( seo_url( $league->event->competition->name ) ); ?>/<?php echo esc_html( $league->current_season['name'] ); ?>/">
										<span class="nav-link__value">
											<?php echo esc_html( $league->event->competition->name ); ?>
										</span>
									</a>
								</span>
							</small>
							<?php
						}
						?>
						<?php
						if ( ! empty( $league->event->competition->date_start ) && ! empty( $league->event->competition->date_end ) ) {
							?>
						<small class="media__subheading">
							<span class="nav--link">
								<span class="nav-link__value">
									<?php racketmanager_the_svg( 'icon-calendar' ); ?>
									<?php echo esc_html( mysql2date( 'j M Y', $league->event->competition->date_start ) ); ?> <?php esc_html_e( 'to', 'racketmanager' ); ?> <?php echo esc_html( mysql2date( 'j M Y', $league->event->competition->date_end ) ); ?>
								</span>
							</span>
						</small>
							<?php
						}
						?>
					</div>
				</div>
				<div class="media__aside">
					<form method="get" action="<?php echo esc_html( get_permalink( $post_id ) ); ?>" id="racketmanager_competititon_archive" class="season-select">
						<input type="hidden" name="page_id" value="<?php echo esc_html( $post_id ); ?>" />
						<input type="hidden" name="pagename" id="pagename" value="<?php echo esc_html( $pagename ); ?>" />
						<div class="row g-1 align-items-center">
							<div class="form-floating">
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
					<?php
					$favourite_type = 'league';
					$favourite_id   = $league->id;
					require 'includes/favourite-button.php';
					?>
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
