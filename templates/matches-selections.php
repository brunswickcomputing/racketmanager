<?php
/**
* Matches selection menu template
*/
?>
<?php if ( ($league->show_match_day_selection || $league->show_team_selection) && $league->mode != 'championship' ) { ?>
	<div class="matches-selections wp-clearfix mb-3 row">
		<form method='get' action='<?php the_permalink(get_the_ID()); ?>' id='racketmanager_match_day_selection'>
			<div class="row g-1 align-items-center">
				<input type="hidden" name="page_id" value="<?php the_ID() ?>" />
				<input type="hidden" name="season" value="<?php echo $season ?>" />
				<input type="hidden" name="league_id" value="<?php echo $league->title ?>" />

				<?php if ($league->show_match_day_selection) { ?>
					<div class="form-floating col-auto">
						<select class="form-select col-auto" size="1" name="match_day" id="match_day">
							<option value="-1"<?php selected(get_current_match_day(), -1) ?>><?php _e( 'Show all Matches', 'racketmanager' ) ?></option>
							<?php for ($i = 1; $i <= $league->num_match_days; $i++) { ?>
								<option value='<?php echo $i ?>'<?php selected(get_current_match_day(), $i) ?>><?php printf(__( '%d. Match Day', 'racketmanager'), $i) ?></option>
							<?php } ?>
						</select>
						<label for="match_day"><?php _e('Match Day', 'racketmanager') ?></label>
					</div>
				<?php } ?>
				<?php if ($league->show_team_selection) { ?>
					<div class="form-floating col-auto">
						<select class="form-select col-auto" size="1" name="team_id" id="team_id">
							<option value=""><?php _e( 'Choose Team', 'racketmanager' ) ?></option>
							<?php foreach ( $teams AS $team_id => $team ) { ?>
								<?php $selected = ( str_replace('-', ' ', get_query_var('team')) == $team->title) ? ' selected="selected"' : ''; ?>
								<option value='<?php echo $team->title ?>'<?php echo $selected ?>><?php echo $team->title ?></option>
							<?php } ?>
						</select>
						<label for="team_id"><?php _e('Team', 'racketmanager') ?></label>
					</div>
				<?php } ?>
				<div class="col-auto">
					<input type="submit" class="button" value='<?php _e('Show') ?>' />
				</div>
			</div>
		</form>
	</div>
<?php } ?>
