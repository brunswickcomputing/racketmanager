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
$tab = 'standings';
$match_day = get_query_var('match_day');
if ( $match_day == '0' ) {
	$match_day = '-1';
	set_query_var('match_day', '-1');
}
if ( isset($_GET['match_day']) || isset($_GET['team_id']) ) {
	$tab = 'matches';
}
if ( !$match_day == '' ) {
	$tab = 'matches';
}
wp_enqueue_style('datatables-style');
wp_enqueue_script('datatables');
?>
<script type='text/javascript'>
var tab = '<?php echo $tab ?>;'
var hash = window.location.hash.substr(1);
if (hash == 'teams') tab = 'teams';
jQuery(function() {
	activaTab('<?php echo $tab ?>');
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
<h1><?php printf("%s - %s %s", $league->title, __('Season', 'racketmanager'), $league->current_season['name']); ?></h1>
<div id="racketmanager_archive_selections" class="">
	<form method="get" action="<?php echo get_permalink($postID); ?>" id="racketmanager_archive">
		<input type="hidden" name="page_id" value="<?php echo $postID ?>" />
		<select size="1" name="league_id" id="league_id">
			<option value=""><?php _e( 'Select League', 'racketmanager' ) ?></option>
			<?php foreach ( $leagues AS $l ) { ?>
				<option value="<?php echo seoUrl($l->title) ?>"<?php if ( $l->id == $league->id ) echo ' selected="selected"' ?>><?php echo $l->title ?></option>
			<?php } ?>
		</select>
		<select size="1" name="season" id="season">
			<option value=""><?php _e( 'Season', 'racketmanager' ) ?></option>
			<?php foreach ( $seasons AS $key => $season ) { ?>
				<option value="<?php echo $key ?>"<?php if ( $season['name'] == $league->current_season['name'] ) echo ' selected="selected"' ?>><?php echo $season['name'] ?></option>
			<?php } ?>
		</select>
		<input type="submit" class="submit" value="<?php _e( 'Show' ) ?>" />
	</form>
</div>

<?php if ( $league->mode == 'championship' ) { ?>
	<?php racketmanager_championship( 0, array('season' => $league->season) ); ?>
<?php } else {
	?>
	<!-- Nav tabs -->
	<ul class="nav nav-tabs frontend" id="myTab" role="tablist">
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="standings-tab" data-bs-toggle="pill" data-bs-target="#standings" type="button" role="tab" aria-controls="standings" aria-selected="true"><?php _e( 'Standings', 'racketmanager' ) ?></button>
		</li>
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="crosstable-tab" data-bs-toggle="pill" data-bs-target="#crosstable" type="button" role="tab" aria-controls="crosstable" aria-selected="false"><?php _e( 'Crosstable', 'racketmanager' ) ?></button>
		</li>
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="matches-tab" data-bs-toggle="pill" data-bs-target="#matches" type="button" role="tab" aria-controls="matches" aria-selected="false"><?php _e( 'Matches', 'racketmanager' ) ?></button>
		</li>
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="teams-tab" data-bs-toggle="pill" data-bs-target="#teams" type="button" role="tab" aria-controls="teams" aria-selected="false"><?php _e( 'Teams', 'racketmanager' ) ?></button>
		</li>
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="players-tab" data-bs-toggle="pill" data-bs-target="#players" type="button" role="tab" aria-controls="players" aria-selected="false"><?php _e( 'Players', 'racketmanager' ) ?></button>
		</li>
	</ul>
	<!-- Tab panes -->
	<div class="tab-content">
		<div class="tab-pane fade" id="standings" role="tabpanel" aria-labelledby="standings-tab">
			<h3 class="header"><?php _e('Standings', 'racketmanager') ?></h3>
			<?php racketmanager_standings( 0, array('season' => get_current_season(), 'template' => get_league_template('standingstable')) ) ?>
		</div>
		<div class="tab-pane fade" id="crosstable" role="tabpanel" aria-labelledby="crosstable-tab">
			<h3 class="header"><?php _e('Crosstable', 'racketmanager') ?></h3>
			<?php racketmanager_crosstable( 0, array('season' => get_current_season(), 'template' => get_league_template('crosstable')) ) ?>
		</div>
		<div class="tab-pane fade" id="matches" role="tabpanel" aria-labelledby="matches-tab">
			<h3 class="header"><?php _e('Matches', 'racketmanager') ?></h3>
			<?php racketmanager_matches( 0, array('season' => get_current_season(), 'match_day' => 'current', 'show_match_day_selection' => 'true', 'template' => get_league_template('matches'), 'template_type' => get_match_template_type()) ) ?>
		</div>
		<div class="tab-pane fade" id="teams" role="tabpanel" aria-labelledby="teams-tab">
			<h3 class="header"><?php _e('Teams', 'racketmanager') ?></h3>
			<?php racketmanager_teams( 0, array('season' => get_current_season(), 'template' => get_league_template('teams')) ) ?>
		</div>
		<div class="tab-pane fade" id="players" role="tabpanel" aria-labelledby="players-tab">
			<h3 class="header"><?php _e('Players', 'racketmanager') ?></h3>
			<?php racketmanager_players( 0, array('season' => get_current_season()) ) ?>
		</div>
	</div>
<?php } ?>
