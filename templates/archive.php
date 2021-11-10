<?php
/**
Template page for the Archive

The following variables are usable:

	$leagues: array of all leagues
	$league: current league
	$seasons: array of all seasons

	You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
global $wp_query;
$postID = $wp_query->post->ID;
$archive = true;
$tab = 0;
$match_day = get_query_var('match_day');
if ( $match_day == '0' ) {
	$match_day = '-1';
	set_query_var('match_day', '-1');
}
if ( isset($_GET['match_day']) || isset($_GET['team_id']) )
	$tab = 2;
if ( !$match_day == '' )
	$tab = 2;
wp_enqueue_style('datatables');
wp_enqueue_script('datatables');
?>
<script type='text/javascript'>
    var tab = <?php echo $tab ?>;
    var hash = window.location.hash.substr(1);
if (hash == 'teams') tab = 3;
	jQuery(function() {
		jQuery(".jquery-ui-tabs").tabs({
			active: tab
		});
	});
	jQuery(document).ready(function(){
						   jQuery('#playerstats').DataTable( {
                                                            "columnDefs": [
                                                            { "visible": false, "targets": 7 },
                                                            { "visible": false, "targets": 10 }
                                                            ],
                                                            order: [[ 3, 'desc' ], [ 11, 'desc' ], [ 7, 'desc' ], [ 5, 'desc' ], [ 10, 'desc' ], [ 8, 'desc' ], [ 0, 'asc' ]],
															fixedHeader: {
															header: true,
															footer: true
															},
															"pageLength":25,
															"lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "All"] ],
															"autoWidth": false
															});
						   });
</script>
<h1><?php printf("%s &mdash; %s %s", $league->title, __('Season', 'leaguemanager'), $league->current_season['name']); ?></h1>
<div id="leaguemanager_archive_selections" class="">
	<form method="get" action="<?php echo get_permalink($postID); ?>" id="leaguemanager_archive">
		<input type="hidden" name="page_id" value="<?php echo $postID ?>" />
		<select size="1" name="league_id" id="league_id">
			<option value=""><?php _e( 'Select League', 'leaguemanager' ) ?></option>
            <?php foreach ( $leagues AS $l ) { ?>
			<option value="<?php echo $l->title ?>"<?php if ( $l->id == $league->id ) echo ' selected="selected"' ?>><?php echo $l->title ?></option>
            <?php } ?>
		</select>
		<select size="1" name="season" id="season">
			<option value=""><?php _e( 'Season', 'leaguemanager' ) ?></option>
            <?php foreach ( $seasons AS $key => $season ) { ?>
            <option value="<?php echo $key ?>"<?php if ( $season['name'] == $league->current_season['name'] ) echo ' selected="selected"' ?>><?php echo $season['name'] ?></option>
            <?php } ?>
		</select>
		<input type="submit" class="submit" value="<?php _e( 'Show' ) ?>" />
	</form>
</div>

<?php if ( $league->mode == 'championship' ) { ?>
		<?php leaguemanager_championship( 0, array('season' => $league->season) ); ?>
<?php } else {
    ?>
		<div class="jquery-ui-tabs">
			<ul class="tablist">
				<li><a href="#standings-archive"><?php _e( 'Standings', 'leaguemanager' ) ?></a></li>
				<li><a href="#crosstable-archive"><?php _e( 'Crosstable', 'leaguemanager' ) ?></a></li>
				<li><a href="#matches-archive"><?php _e( 'Matches', 'leaguemanager' ) ?></a></li>
				<li><a href="#teams-archive"><?php _e( 'Teams', 'leaguemanager' ) ?></a></li>
				<li><a href="#players-archive"><?php _e( 'Players', 'leaguemanager' ) ?></a></li>
			</ul>

			<!-- Standings Table -->
			<div id="standings-archive" class="jquery-ui-tab">
				<h3 class="header"><?php _e('Standings', 'leaguemanager') ?></h3>
				<?php leaguemanager_standings( 0, array('season' => get_current_season(), 'template' => get_league_template('standingstable')) ) ?>
			</div>

			<!-- Crosstable -->
			<div id="crosstable-archive" class="jquery-ui-tab">
				<h3 class="header"><?php _e('Crosstable', 'leaguemanager') ?></h3>
				<?php leaguemanager_crosstable( 0, array('season' => get_current_season(), 'template' => get_league_template('crosstable')) ) ?>
			</div>

			<!-- Match Overview -->
			<div id="matches-archive" class="jquery-ui-tab">
				<h3 class="header"><?php _e('Matches', 'leaguemanager') ?></h3>
				<?php leaguemanager_matches( 0, array('season' => get_current_season(), 'match_day' => 'current', 'show_match_day_selection' => 'true', 'template' => get_league_template('matches'), 'template_type' => get_match_template_type()) ) ?>
			</div>

			<!-- Teamlist -->
			<div id="teams-archive" class="jquery-ui-tab">
				<h3 class="header"><?php _e('Teams', 'leaguemanager') ?></h3>
				<?php leaguemanager_teams( 0, array('season' => get_current_season(), 'template' => get_league_template('teams')) ) ?>
			</div>

			<!-- Players -->
			<div id="players-archive" class="jquery-ui-tab">
				<h3 class="header"><?php _e('Players', 'leaguemanager') ?></h3>
				<?php leaguemanager_players( 0, array('season' => get_current_season()) ) ?>
			</div>
		</div>
<?php } ?>
