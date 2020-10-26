<?php
	global $wp_query;
	$postID = $wp_query->post->ID;
	?>
<?php if ( ($league->show_match_day_selection || $league->show_team_selection) && $league->mode != 'championship' ) { ?>
	<div style='float: left; margin-top: 1em;'>
		<form method='get' action='<?php echo get_permalink($postID); ?>' id='leaguemanager_match_day_selection'>
		<div>
			<input type='hidden' name='page_id' value='<?php echo $postID ?>' />
			<input type='hidden' name='season' value='<?php echo $season ?>' />
			<input type='hidden' name='league_id' value='<?php echo $league->title ?>' />

	<?php if ($league->show_match_day_selection) { ?>
			<select size='1' name='match_day' id='match_day'>
				<?php $selected = ( isset($_GET['match_day']) && intval($_GET['match_day']) == -1 ) ? ' selected="selected"' : ''; ?>
				<option value="-1"<?php echo $selected ?>><?php _e( 'Show all Matches', 'leaguemanager' ) ?></option>
		<?php for ($i = 1; $i <= $league->num_match_days; $i++) { ?>
				<option value='<?php echo $i ?>'<?php if ($leaguemanager->getMatchDay() == $i) echo ' selected="selected"'?>><?php printf(__( '%d. Match Day', 'leaguemanager'), $i) ?></option>
		<?php } ?>
			</select>
	<?php } ?>
<?php if ($league->show_team_selection) { ?>
			<select size='1' name='team_id' id='team_id'>
			<option value=""><?php _e( 'Choose Team', 'leaguemanager' ) ?></option>
		<?php foreach ( $teams AS $team_id => $team ) { ?>
				<?php $selected = ( str_replace('-', ' ', get_query_var('team')) == $team['title']) ? ' selected="selected"' : ''; ?>
				<option value='<?php echo $team["title"] ?>'<?php echo $selected ?>><?php echo $team['title'] ?></option>
		<?php } ?>
			</select>
	<?php } ?>
			<input type='submit' class='button' value='<?php _e('Show') ?>' />
		</div>
		</form>
	</div>
	<br style='clear: both;' />
<?php } ?>
