<?php
/**
Template page for the Archive

The following variables are usable:
	
	$leagues: array of all leagues
	$curr_league: current league
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
<h2><?php printf("%s &mdash; %s %s", $curr_league->title, __('Season', 'leaguemanager'), $curr_league->season); ?></h2>
<div id="leaguemanager_archive_selections" class="">
	<form method="get" action="<?php echo get_permalink($postID); ?>" id="leaguemanager_archive">
		<input type="hidden" name="page_id" value="<?php echo $postID ?>" />
<?php if ( $single_league ) { ?>
		<select size="1" name="season" id="season">
			<option value=""><?php _e( 'Season', 'leaguemanager' ) ?></option>
			<!--<option value=""><?php _e( 'Season', 'leaguemanager' ) ?></option>-->
	<?php foreach ( $seasons AS $key => $season ) { ?>
			<option value="<?php echo $key ?>"<?php if ( $season['name'] == $curr_league->season ) echo ' selected="selected"' ?>><?php echo $season['name'] ?></option>
	<?php } ?>
		</select>
<?php } else { ?>
		<select size="1" name="league_id" id="league_id">
			<option value=""><?php _e( 'Select League', 'leaguemanager' ) ?></option>
			<?php foreach ( $leagues AS $league ) : ?>
			<option value="<?php echo $league->title ?>"<?php if ( $league->id == $curr_league->id ) echo ' selected="selected"' ?>><?php echo $league->title ?></option>
			<?php endforeach ?>
		</select>
		<select size="1" name="season" id="season">
			<option value=""><?php _e( 'Season', 'leaguemanager' ) ?></option>
	<?php foreach ( $seasons AS $key => $season ) { ?>
				<option value="<?php echo $key ?>"<?php if ( $season['name'] == $curr_league->season ) echo ' selected="selected"' ?>><?php echo $season['name'] ?></option>
	<?php } ?>
		</select>
<?php } ?>
		<input type="submit" class="submit" value="<?php _e( 'Show' ) ?>" />
	</form>
</div>

<?php if ( $curr_league->mode == 'championship' ) { ?>
		<?php leaguemanager_championship( $curr_league->id, array('season' => $curr_league->season) ); ?>
<?php } else { ?>
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
				<h4 class="header"><?php _e('Standings', 'leaguemanager') ?></h4>
				<?php leaguemanager_standings( $curr_league->id, array( 'season' => $curr_league->season, 'template' => 'last5-nolink', 'logo' => false ) ) ?>
			</div>
			
			<!-- Crosstable -->
			<div id="crosstable-archive" class="jquery-ui-tab">
				<h4 class="header"><?php _e('Crosstable', 'leaguemanager') ?></h4>
				<?php leaguemanager_crosstable( $curr_league->id, array('season' => $curr_league->season) ) ?>
			</div>
			
			<!-- Match Overview -->
			<div id="matches-archive" class="jquery-ui-tab">
				<h4 class="header"><?php _e('Matches', 'leaguemanager') ?></h4>
				<?php leaguemanager_matches( $curr_league->id, array('season' => $curr_league->season, 'match_day' => 'current' , 'show_match_day_selection' => 'true') ) ?>
			</div>

			<!-- Teamlist -->
			<div id="teams-archive" class="jquery-ui-tab">
				<h4 class="header"><?php _e('Teams', 'leaguemanager') ?></h4>
				<?php leaguemanager_teams( $curr_league->id, array('season' => $curr_league->season, 'template' => 'list') ) ?>
			</div>

			<!-- Players -->
			<div id="players-archive" class="jquery-ui-tab">
				<h4 class="header"><?php _e('Players', 'leaguemanager') ?></h4>
				<?php leaguemanager_players( $curr_league->id, array('season' => $curr_league->season) ) ?>
			</div>
		</div>
<?php } ?>
