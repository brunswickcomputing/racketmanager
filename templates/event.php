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

global $wp_query, $racketmanager_shortcodes, $racketmanager, $wp;
$post_id = isset( $wp_query->post->ID ) ? $wp_query->post->ID : ''; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
if ( 'tournament' === $event->competition->type ) {
	$pagename = isset( $wp_query->query['pagename'] ) ? $wp_query->query['pagename'] : '';
} else {
	$pagename = '/' . $event->competition->type . 's/' . seo_url( $event->name ) . '/';
}
if ( 'email' === $template_type ) {
	$email_subject = $event->name;
	require 'email/email-header.php';
	require 'email/div-top.php';
	?>
	<?php
}
if ( empty( $tab ) ) {
	if ( isset( $wp->query_vars['player_id'] ) ) {
		$tab = 'players'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	} elseif ( isset( $wp->query_vars['club_name'] ) ) {
		$tab = 'clubs'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	} elseif ( isset( $wp->query_vars['team'] ) ) {
		$tab = 'teams'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	} else {
		$tab = 'standings'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
}
if ( $event->is_box ) {
	$event_title      = $event->name . ' - ' . __( 'Round', 'racketmanager' ) . ' ' . $curr_season;
	$season_label     = __( 'Round', 'racketmanager' );
	$season_selection = __( 'Rounds', 'racketmanager' );
} else {
	$event_title      = $event->name;
	$season_label     = __( 'Season', 'racketmanager' );
	$season_selection = __( 'Seasons', 'racketmanager' );
}
switch ( $event->competition->type ) {
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
<div id="leaguetables">
	<script type="text/javascript">
	var tab = '<?php echo esc_html( $tab ); ?>;'
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
					<h1 class="media__title"><?php echo esc_html( $event->name ); ?></h1>
					<div class="media__content-subinfo">
						<?php
						if ( ! empty( $event->competition->name ) ) {
							?>
							<small class="media__subheading">
								<span class="nav--link">
									<a href="/<?php echo esc_html( seo_url( $event->competition->name ) ); ?>/<?php echo esc_html( $curr_season ); ?>/">
										<span class="nav-link__value">
											<?php echo esc_html( $event->competition->name ); ?>
										</span>
									</a>
								</span>
							</small>
							<?php
						}
						?>
						<?php
						if ( ! empty( $event->competition->current_season['dateStart'] ) && ! empty( $event->competition->current_season['dateEnd'] ) ) {
							?>
							<small class="media__subheading">
								<span class="nav--link">
									<span class="nav-link__value">
										<?php racketmanager_the_svg( 'icon-calendar' ); ?>
										<?php echo esc_html( mysql2date( $racketmanager->date_format, $event->competition->current_season['dateStart'] ) ); ?> <?php esc_html_e( 'to', 'racketmanager' ); ?> <?php echo esc_html( mysql2date( $racketmanager->date_format, $event->competition->current_season['dateEnd'] ) ); ?>
									</span>
								</span>
							</small>
							<?php
						}
						?>
					</div>
				</div>
				<?php
				if ( 'constitution' !== $standings_template ) {
					?>
					<div class="media__aside">
						<form method="get" action="<?php echo esc_html( get_permalink( $post_id ) ); ?>" id="racketmanager_competititon_archive" class="season-select">
							<input type="hidden" name="page_id" value="<?php echo esc_html( $post_id ); ?>" />
							<input type="hidden" name="pagename" id="pagename" value="<?php echo esc_html( $pagename ); ?>" />
							<div class="row g-1 align-items-center">
								<div class="col-md">
									<div class="form-floating">
										<select class="form-select" size="1" name="season" id="season">
											<?php
											foreach ( array_reverse( $seasons ) as $key => $season ) {
												$option_name = $season['name'];
												?>
												<option value="<?php echo esc_html( $season['name'] ); ?>" <?php selected( $season['name'], $curr_season ); ?>>
													<?php echo esc_html( $option_name ); ?>
												</option>
												<?php
											}
											?>
										</select>
										<label for="season"><?php esc_html_e( 'Seasons', 'racketmanager' ); ?></label>
									</div>
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
	if ( 'constitution' !== $standings_template ) {
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
							if ( $event->competition->is_championship ) {
								?>
								<li class="nav-item" role="presentation">
									<button class="nav-link" id="standings-tab" data-bs-toggle="tab" data-bs-target="#standings" type="button" role="tab" aria-controls="standings" aria-selected="true"><?php esc_html_e( 'Draw', 'racketmanager' ); ?></button>
								</li>
								<?php
							} else {
								?>
								<li class="nav-item" role="presentation">
									<button class="nav-link" id="standings-tab" data-bs-toggle="tab" data-bs-target="#standings" type="button" role="tab" aria-controls="standings" aria-selected="true"><?php esc_html_e( 'Standings', 'racketmanager' ); ?></button>
								</li>
								<?php
							}
							?>
							<?php
							if ( $event->competition->is_championship ) {
								?>
								<li class="nav-item" role="presentation">
									<button class="nav-link" id="matches-tab" data-bs-toggle="tab" data-bs-target="#matches" type="button" role="tab" aria-controls="matches" aria-selected="true"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></button>
								</li>
								<?php
							} elseif ( ! $event->is_box ) {
								?>
								<li class="nav-item" role="presentation">
									<?php
									if ( ! empty( $wp->query_vars['club_name'] ) ) {
										?>
										<a href="/<?php echo esc_attr( $event->competition->type ); ?>s/<?php echo esc_attr( seo_url( $event->name ) ); ?>/<?php echo esc_html( $curr_season ); ?>/clubs">
										<?php
									}
									?>
									<button class="nav-link" id="clubs-tab" data-bs-toggle="tab" data-bs-target="#clubs" type="button" role="tab" aria-controls="clubs" aria-selected="false"><?php esc_html_e( 'Clubs', 'racketmanager' ); ?></button>
									<?php
									if ( ! empty( $wp->query_vars['club_name'] ) ) {
										?>
										</a>
										<?php
									}
									?>
								</li>
								<?php
							}
							?>
							<li class="nav-item" role="presentation">
								<?php
								if ( ! empty( $wp->query_vars['team'] ) ) {
									?>
									<a href="/<?php echo esc_attr( $event->competition->type ); ?>s/<?php echo esc_attr( seo_url( $event->name ) ); ?>/<?php echo esc_html( $curr_season ); ?>/teams">
									<?php
								}
								?>
								<button class="nav-link" id="teams-tab" data-bs-toggle="tab" data-bs-target="#teams" type="button" role="tab" aria-controls="teams" aria-selected="false"><?php esc_html_e( 'Teams', 'racketmanager' ); ?></button>
								<?php
								if ( ! empty( $wp->query_vars['team'] ) ) {
									?>
									</a>
									<?php
								}
								?>
							</li>
							<?php
							if ( ! $event->is_box ) {
								?>
								<li class="nav-item" role="presentation">
									<?php
									if ( ! empty( $wp->query_vars['player_id'] ) ) {
										?>
										<a href="/<?php echo esc_attr( $event->competition->type ); ?>s/<?php echo esc_attr( seo_url( $event->name ) ); ?>/<?php echo esc_html( $curr_season ); ?>/players">
										<?php
									}
									?>
									<button class="nav-link" id="players-tab" data-bs-toggle="tab" data-bs-target="#players" type="button" role="tab" aria-controls="players" aria-selected="false"><?php esc_html_e( 'Players', 'racketmanager' ); ?></button>
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
		</div>
		<?php
	}
	?>
	<?php
	if ( 'constitution' !== $standings_template ) {
		?>
		<div class="tab-content">
		<?php
	}
	?>
	<?php
	if ( $event->competition->is_championship ) {
		?>
		<div class="tab-pane fade" id="standings" role="tabpanel" aria-labelledby="standings-tab">
			<div class="module module--card">
				<div class="module__banner">
					<h3 class="module__title"><?php esc_html_e( 'Draw', 'racketmanager' ); ?></h3>
				</div>
				<div class="module__content">
					<div class="module-container">
						<?php
						foreach ( $event->leagues as $league ) {
							?>
							<h4 class="header">
								<?php echo esc_html( $league->title ); ?>
							</h4>
							<?php
							$finals   = $league->finals;
							$champion = null;
							require RACKETMANAGER_PATH . 'templates/includes/championship-draw.php';
							?>
							<?php
						}
						?>
					</div>
				</div>
			</div>
		</div>
		<?php
	} else {
		?>
		<div class="tab-pane fade" id="standings" role="tabpanel" aria-labelledby="standings-tab">
			<div class="module module--card">
				<div class="module__banner">
					<h3 class="module__title"><?php esc_html_e( 'Standings', 'racketmanager' ); ?></h3>
				</div>
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
										$href .= '/' . __( 'league', 'racketmanager' ) . '/' . seo_url( $league->title ) . '/';
										if ( $event->is_box ) {
											$href .= __( 'round', 'racketmanager' ) . '-';
										}
										$href .= $curr_season . '/';
										?>
										<a href="<?php echo esc_url( $href ); ?>">
											<?php echo esc_html( $league->title ); ?>
										</a>
										<?php
										if ( 'constitution' !== $standings_template ) {
											$favourite_type = 'league';
											$favourite_id   = $league->id;
											require 'includes/favourite.php';
										}
										?>
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
		</div>
		<?php
	}
	?>
	<?php
	if ( 'constitution' !== $standings_template ) {
		?>
			<?php
			if ( $event->competition->is_championship ) {
				?>
				<div class="tab-pane fade" id="matches" role="tabpanel" aria-labelledby="matches-tab">
					<div class="module module--card">
						<div class="module__banner">
							<h3 class="module__title"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></h3>
						</div>
						<div class="module__content">
							<div class="module-container">
								<?php
								foreach ( $event->leagues as $league ) {
									?>
									<h4 class="header">
										<?php echo esc_html( $league->title ); ?>
									</h4>
									<?php
									$finals   = $league->finals;
									$champion = null;
									require RACKETMANAGER_PATH . 'templates/championship-matches.php';
									?>
									<?php
								}
								?>
							</div>
						</div>
					</div>
				</div>
				<?php
			} elseif ( ! $event->is_box ) {
				?>
				<div class="tab-pane fade" id="clubs" role="tabpanel" aria-labelledby="clubs-tab">
					<?php echo do_shortcode( '[event-clubs event_id=' . $event->id . ']' ); ?>
				</div>
				<?php
			}
			?>
			<div class="tab-pane fade" id="teams" role="tabpanel" aria-labelledby="teams-tab">
			<?php
			if ( $event->competition->is_championship ) {
				?>
				<?php echo do_shortcode( '[event-teams event_id=' . $event->id . ']' ); ?>
				<?php
			} else {
				?>
				<?php require 'event/teams.php'; ?>
				<?php
			}
			?>
			</div>
			<?php
			if ( ! $event->is_box ) {
				?>
				<div class="tab-pane fade" id="players" role="tabpanel" aria-labelledby="players-tab">
					<?php echo do_shortcode( '[event-players event_id=' . $event->id . ']' ); ?>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}
	?>

</div>
<?php
if ( 'email' === $template_type ) {
	?>
	<?php
	require 'email/div-bottom.php';
	require 'email/email-footer.php';
} ?>
