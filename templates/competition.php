<?php
/**
 *
 * Template page for the Competition
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

global $wp_query, $racketmanager_shortcodes, $racketmanager, $wp;
$post_id = isset( $wp_query->post->ID ) ? $wp_query->post->ID : ''; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
if ( 'tournament' === $competition->competition->type ) {
	$pagename = isset( $wp_query->query['pagename'] ) ? $wp_query->query['pagename'] : '';
} else {
	$pagename = '/' . $competition->competition->type . 's/' . seo_url( $competition->name ) . '/';
}
if ( 'email' === $template_type ) {
	$email_subject = $competition->name;
	require_once 'email/email-header.php';
	require_once 'email/div-top.php';
	?>
	<?php
}
if ( empty( $tab ) ) {
	if ( isset( $wp->query_vars['player_id'] ) ) {
		$tab = 'players'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	} elseif ( isset( $wp->query_vars['club_name'] ) ) {
		$tab = 'clubs'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	} else {
		$tab = 'standings'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
}
?>
<div id="leaguetables">
	<script type="text/javascript">
	var tab = '<?php echo esc_html( $tab ); ?>;'
	jQuery(function() {
		activaTab('<?php echo esc_html( $tab ); ?>');
	});
	</script>	<div class="module module--card module--dark">
		<div class="module__content">
			<div class="module__banner">
				<div class="banner__title">
					<h1><?php echo esc_html( $competition->name ) . ' - ' . esc_html__( 'Season', 'racketmanager' ) . ' ' . esc_html( $curr_season ); ?></h1>
				</div>
				<?php
				if ( 'constitution' !== $standings_template ) {
					?>
					<div id="racketmanager_archive_selections" class="module__aside">
						<form method="get" action="<?php echo esc_html( get_permalink( $post_id ) ); ?>" id="racketmanager_competititon_archive">
							<input type="hidden" name="page_id" value="<?php echo esc_html( $post_id ); ?>" />
							<input type="hidden" name="pagename" id="pagename" value="<?php echo esc_html( $pagename ); ?>" />
							<div class="row g-1 align-items-center">
								<div class="form-floating col-auto">
									<select class="form-select" size="1" name="season" id="season">
										<?php
										foreach ( array_reverse( $seasons ) as $key => $season ) {
											?>
											<option value="<?php echo esc_html( $season['name'] ); ?>" <?php selected( $season['name'], $curr_season ); ?>>
												<?php echo esc_html( $season['name'] ); ?>
											</option>
										<?php } ?>
									</select>
									<label for="season"><?php esc_html_e( 'Season', 'racketmanager' ); ?></label>
								</div>
							</div>
						</form>
					</div>
					<?php
				}
				?>
			</div>
		</div>
	</div>
	<?php
	if ( 'championship' !== $competition->mode ) {
		?>
		<div>
			<ul class="nav nav-tabs frontend" id="myTab" role="tablist">
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="standings-tab" data-bs-toggle="tab" data-bs-target="#standings" type="button" role="tab" aria-controls="standings" aria-selected="true"><?php esc_html_e( 'Standings', 'racketmanager' ); ?></button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="clubs-tab" data-bs-toggle="tab" data-bs-target="#clubs" type="button" role="tab" aria-controls="clubs" aria-selected="false"><?php esc_html_e( 'Clubs', 'racketmanager' ); ?></button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="teams-tab" data-bs-toggle="tab" data-bs-target="#teams" type="button" role="tab" aria-controls="teams" aria-selected="false"><?php esc_html_e( 'Teams', 'racketmanager' ); ?></button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="players-tab" data-bs-toggle="tab" data-bs-target="#players" type="button" role="tab" aria-controls="players" aria-selected="false"><?php esc_html_e( 'Players', 'racketmanager' ); ?></button>
				</li>
			</ul>
		</div>
		<div class="module module--card">
			<div class="tab-content">
				<div class="tab-pane fade" id="standings" role="tabpanel" aria-labelledby="standings-tab">
					<div class="module__content">
						<div class="module-container">
							<div id="leagues">
								<?php
								foreach ( $leagues as $league ) {
									?>
									<!-- Standings Table -->
									<div id="standings-archive">
										<h4 class="header">
											<?php
											$href = '';
											if ( 'constitution' === $standings_template ) {
												$href = esc_url( $racketmanager->site_url );
											}
											$href .= '/' . __( 'league', 'racketmanager' ) . '/' . seo_url( $league->title ) . '/' . $curr_season . '/';
											?>
											<a href="<?php echo esc_url( $href ); ?>">
												<?php echo esc_html( $league->title ); ?>
											</a>
											<?php
											if ( is_user_logged_in() && 'constitution' !== $standings_template ) {
												$is_favourite = $racketmanager->is_user_favourite( 'league', $league->id );
												?>
												<div class="fav-icon">
													<a href="" id="fav-<?php echo esc_html( $league->id ); ?>" title="
													<?php
													if ( $is_favourite ) {
														esc_html_e( 'Remove favourite', 'racketmanager' );
													} else {
														esc_html_e( 'Add favourite', 'racketmanager' );
													}
													?>
													" data-js="add-favourite" data-type="league" data-favourite="<?php echo esc_html( $league->id ); ?>">
														<i class="fav-icon-svg racketmanager-svg-icon
														<?php
														if ( $is_favourite ) {
															echo ' fav-icon-svg-selected';
														}
														?>
														">
															<?php racketmanager_the_svg( 'icon-star' ); ?>
														</i>
													</a>
													<div class="fav-msg" id="fav-msg-<?php echo esc_html( $league->id ); ?>"></div>
												</div>
											<?php } ?>
										</h4>
										<?php
										racketmanager_standings(
											$league->id,
											array(
												'season'   => $curr_season,
												'template' => $standings_template,
											)
										);
										?>
									</div>
									<?php
								}
								?>
							</div>
						</div>
					</div>
				</div>
				<div class="tab-pane fade" id="clubs" role="tabpanel" aria-labelledby="clubs-tab">
					<?php echo do_shortcode( '[competition-clubs competition_id=' . $competition->competition_id . ']' ); ?>
				</div>
				<div class="tab-pane fade" id="teams" role="tabpanel" aria-labelledby="teams-tab">
					<?php require 'competition/teams.php'; ?>
				</div>
				<div class="tab-pane fade" id="players" role="tabpanel" aria-labelledby="players-tab">
					<?php echo do_shortcode( '[competition-players competition_id=' . $competition->competition_id . ']' ); ?>
				</div>
			</div>
		</div>
		<?php
	} else {
		?>
		<div class="module module--card">
			<div class="module__content">
				<div class="module-container">
					<div id="<?php echo esc_html( $competition->type ); ?>">
						<?php
						foreach ( $leagues as $league ) {
							?>
							<!-- Brackets -->
							<div id="brackets">
								<h4 class="header"><?php echo esc_html( $league->title ); ?></h4>
								<?php
								racketmanager_championship(
									$league->id,
									array(
										'season'   => $curr_season,
										'template' => '',
									)
								);
								?>
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
</div>
<?php
if ( 'email' === $template_type ) {
	?>
	<?php
	require_once 'email/div-bottom.php';
	require_once 'email/email-footer.php';
} ?>
