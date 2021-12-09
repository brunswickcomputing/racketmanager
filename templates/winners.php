<?php
/**
Template page for the Winners

The following variables are usable:

	$winners: array of all winners
	$curr_season: current season
	$tournaments: array of all tournaments

	You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
global $wp_query, $racketmanager_shortcodes;
$postID = isset($wp_query->post->ID) ? $wp_query->post->ID : "";
?>
<div id="winners">
	<h1><?php printf("%s %s", $curr_tournament, __('Winners', 'racketmanager')); ?></h1>
	<div id="racketmanager_archive_selections" class="">
		<form method="get" action="<?php echo get_permalink($postID); ?>" id="racketmanager_winners">
			<input type="hidden" name="page_id" value="<?php echo $postID ?>" />
			<select size="1" name="tournament" id="tournament">
				<option value=""><?php _e( 'Tournament', 'racketmanager' ) ?></option>
				<!--<option value=""><?php _e( 'Season', 'racketmanager' ) ?></option>-->
		<?php foreach ( $tournaments AS $tournament ) { ?>
				<option value="<?php echo $tournament->name ?>"<?php if ( $tournament->name == $curr_tournament ) echo ' selected="selected"' ?>><?php echo $tournament->name ?></option>
		<?php } ?>
			</select>
            <input type="submit" class="submit" value="<?php _e( 'Show' ) ?>" />
        </form>
    </div>

<?php if ( !$winners ) {
	 _e('No tournament winners', 'racketmanager');
} else {
	 foreach ( $winners AS $winner ) { ?>
			<!-- Standings Table -->
			<div id="winners-list" class="jquery-ui-tab">
				<h4 class="header"><?php echo $winner->league ?></h4>
				<dl>
					<dd><?php _e('Winner', 'racketmanager') ?></dd>
					<dt><?php echo $winner->winner ?></dt>
					<dd><?php _e('Runner-up', 'racketmanager') ?></dd>
					<dt><?php echo $winner->loser ?></dt>
				</dl>
			</div>
		<?php } ?>
<?php } ?>
</div>
