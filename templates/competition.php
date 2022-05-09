<?php
/**
Template page for the Competition

The following variables are usable:

$leagues: array of all leagues
$curr_league: current league
$seasons: array of all seasons

You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
global $wp_query, $racketmanager_shortcodes;
$postID = isset($wp_query->post->ID) ? $wp_query->post->ID : "";
wp_enqueue_style('datatables-style');
wp_enqueue_script('datatables');
$pagename = isset($wp_query->query['pagename']) ? $wp_query->query['pagename'] : '';
?>
<div id="leaguetables">
	<h1><?php printf("%s - %s %s", $competition->name, __('Season', 'racketmanager'), $curr_season); ?></h1>
	<?php if ( $standingsTemplate != 'constitution' ) { ?>
		<div id="racketmanager_archive_selections" class="">
			<form method="get" action="<?php echo get_permalink($postID); ?>" id="racketmanager_competititon_archive">
				<input type="hidden" name="page_id" value="<?php echo $postID ?>" />
				<input type="hidden" name="pagename" id="pagename" value="<?php echo $pagename ?>" />
				<div class="row g-1 align-items-center">
					<div class="form-floating col-auto">
						<select class="form-select" size="1" name="season" id="season">
							<option value=""><?php _e( 'Season', 'racketmanager' ) ?></option>
							<?php foreach ( array_reverse($seasons) AS $key => $season ) { ?>
								<option value="<?php echo $season['name'] ?>"<?php if ( $season['name'] == $curr_season ) echo ' selected="selected"' ?>><?php echo $season['name'] ?></option>
							<?php } ?>
						</select>
						<label for="season"><?php _e('Season', 'racketmanager') ?></label>
					</div>
					<div class="col-auto">
						<input type="submit" class="submit" value="<?php _e( 'Show' ) ?>" />
					</div>
				</div>
			</form>
		</div>
	<?php } ?>

	<?php if ( $competition->mode == 'default' ) { ?>
		<div id="leagues">
			<?php foreach ( $leagues AS $league ) { ?>
				<!-- Standings Table -->
				<div id="standings-archive">
					<h4 class="header">
						<?php if ( $standingsTemplate != 'constitution' ) { ?><a href="/<?php _e('leagues', 'racketmanager') ?>/<?php echo seoUrl($league->title) ?>/<?php echo $curr_season ?>/"><?php } ?><?php echo $league->title ?><?php if ( $standingsTemplate != 'constitution' ) { ?></a><?php } ?>
						<?php if ( is_user_logged_in() ) {
							$isFavourite = $racketmanager->userFavourite('league', $league->id); ?>
							<div class="fav-icon">
								<a href="" id="fav-<?php echo $league->id ?>" title="<?php if ( $isFavourite) { _e( 'Remove favourite', 'racketmanager' ); } else { _e( 'Add favourite', 'racketmanager'); } ?>" data-js="add-favourite" data-type="league" data-favourite="<?php echo $league->id ?>">
									<i class="fav-icon-svg racketmanager-svg-icon <?php if ( $isFavourite ) { echo 'fav-icon-svg-selected'; } ?>">
										<?php racketmanager_the_svg('icon-star') ?>
									</i>
								</a>
								<div class="fav-msg" id="fav-msg-<?php echo $league->id ?>"></div>
							</div>
						<?php } ?>
					</h4>
					<?php racketmanager_standings( $league->id, array( 'season' => $curr_season, 'template' => $standingsTemplate ) ) ?>
				</div>
			<?php } ?>
		</div>
	<?php } else { ?>
		<div id="cups">
			<?php foreach ( $leagues AS $league ) { ?>
				<!-- Brackets -->
				<div id="brackets">
					<h4 class="header"><?php echo $league->title ?></h4>
					<?php racketmanager_championship( $league->id, array( 'season' => $curr_season, 'template' => '' ) ) ?>
				</div>
			<?php } ?>
		</div>
	<?php } ?>
</div>
