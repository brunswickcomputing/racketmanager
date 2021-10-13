<?php
/**
Template page for the Competition

The following variables are usable:
	
	$leagues: array of all leagues
	$curr_league: current league
	$seasons: array of all seasons
 
	You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
global $wp_query, $leaguemanager_shortcodes;
$postID = isset($wp_query->post->ID) ? $wp_query->post->ID : "";
wp_enqueue_style('datatables');
wp_enqueue_script('datatables');
?>
<div id="leaguetables">
	<h1><?php printf("%s &mdash; %s %s", $competition->name, __('Season', 'leaguemanager'), $curr_season); ?></h1>
	<div id="leaguemanager_archive_selections" class="">
		<form method="get" action="<?php echo get_permalink($postID); ?>" id="leaguemanager_archive">
			<input type="hidden" name="page_id" value="<?php echo $postID ?>" />
			<select size="1" name="season" id="season">
				<option value=""><?php _e( 'Season', 'leaguemanager' ) ?></option>
				<!--<option value=""><?php _e( 'Season', 'leaguemanager' ) ?></option>-->
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
				<h4 class="header"><a href="/leagues/<?php echo str_replace(' ', '-', $league->title) ?>/<?php echo $curr_season ?>"><?php echo $league->title ?></a></h4>
				<h4 class="header"><a href="/<?php _e('leagues', 'leaguemanager') ?>/<?php echo str_replace(' ', '-', $league->title) ?>/<?php echo $curr_season ?>"><?php echo $league->title ?></a></h4>
				<?php leaguemanager_standings( $league->id, array( 'season' => $curr_season, 'template' => '' ) ) ?>
			</div>
    <?php } ?>
	</div>
<?php } else { ?>
    <div id="cups">
    <?php foreach ( $leagues AS $league ) { ?>
			<!-- Brackets -->
			<div id="brackets" class="jquery-ui-tab">
				<h4 class="header"><?php echo $league->title ?></h4>
				<?php leaguemanager_championship( $league->id, array( 'season' => $curr_season, 'template' => '' ) ) ?>
			</div>
    <?php } ?>
    </div>
<?php } ?>
</div>
