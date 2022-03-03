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
	<div id="racketmanager_archive_selections" class="">
		<form method="get" action="<?php echo get_permalink($postID); ?>" id="racketmanager_competititon_archive">
			<input type="hidden" name="page_id" value="<?php echo $postID ?>" />
			<input type="hidden" name="pagename" id="pagename" value="<?php echo $pagename ?>" />
			<select size="1" name="season" id="season">
				<option value=""><?php _e( 'Season', 'racketmanager' ) ?></option>
		<?php foreach ( $seasons AS $key => $season ) { ?>
				<option value="<?php echo $key ?>"<?php if ( $season['name'] == $curr_season ) echo ' selected="selected"' ?>><?php echo $season['name'] ?></option>
		<?php } ?>
			</select>
            <input type="submit" class="submit" value="<?php _e( 'Show' ) ?>" />
        </form>
    </div>

<?php if ( $competition->mode == 'default' ) { ?>
    <div id="leagues">
	<?php foreach ( $leagues AS $league ) { ?>
			<!-- Standings Table -->
			<div id="standings-archive" class="jquery-ui-tab">
				<h4 class="header"><a href="/<?php _e('leagues', 'racketmanager') ?>/<?php echo seoUrl($league->title) ?>/<?php echo $curr_season ?>/"><?php echo $league->title ?></a></h4>
				<?php racketmanager_standings( $league->id, array( 'season' => $curr_season, 'template' => '' ) ) ?>
			</div>
    <?php } ?>
	</div>
<?php } else { ?>
    <div id="cups">
    <?php foreach ( $leagues AS $league ) { ?>
			<!-- Brackets -->
			<div id="brackets" class="jquery-ui-tab">
				<h4 class="header"><?php echo $league->title ?></h4>
				<?php racketmanager_championship( $league->id, array( 'season' => $curr_season, 'template' => '' ) ) ?>
			</div>
    <?php } ?>
    </div>
<?php } ?>
</div>
