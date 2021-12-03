<?php
/**
Template page for a whole league

The following variables are usable:
	
	$league: league
	
	You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
$archive = true;
$tab = 0;

if ( isset($_GET['match_day']) && isset($_GET['team_id']) ) {
	$tab = 2;

} elseif ( isset($_GET['team_'.$league->id]) ) {
	$tab = 3;

} elseif ( isset($_GET['match_'.$league->id]) ) {
	$tab = 2;
}
?>
<script type='text/javascript'>
	jQuery(function() {
		jQuery(".jquery-ui-tabs").tabs({
			active: <?php echo $tab ?>
		});
	});
</script>
<h2><?php echo $league->title ?></h2>

<?php if ( $league->mode == 'championship' ) : ?>
	<?php racketmanager_championship( $league->id, array('season' => $league->season) ); ?>
<?php else : ?>
	<div class="jquery-ui-tabs">
		<ul class="tablist">
			<li><a href="#standings-archive"><?php _e( 'Standings', 'racketmanager' ) ?></a></li>
			<li><a href="#crosstable-archive"><?php _e( 'Crosstable', 'racketmanager' ) ?></a></li>
			<li><a href="#matches-archive"><?php _e( 'Matches', 'racketmanager' ) ?></a></li>
			<li><a href="#teams-archive"><?php _e( 'Teams', 'racketmanager' ) ?></a></li>
		</ul>
		
		<!-- Standings Table -->
		<div id="standings-archive" class="jquery-ui-tab">
			<h4 class="tab-header"><?php _e('Standings', 'racketmanager') ?></h4>
			<?php racketmanager_standings( $league->id, array( 'season' => $league->season, 'template' => $league->templates['standingstable'] ) ) ?>
		</div>
			
		<!-- Crosstable -->
		<div id="crosstable-archive" class="jquery-ui-tab">
			<h4 class="tab-header"><?php _e('Crosstable', 'racketmanager') ?></h4>
			<?php racketmanager_crosstable( $league->id, array('season' => $league->season, 'template' => $league->templates['crosstable']) ) ?>
		</div>
			
		<!-- Match Overview -->
		<div id="matches-archive" class="jquery-ui-tab">
			<h4 class="tab-header"><?php _e('Matches', 'racketmanager') ?></h4>
			<?php racketmanager_matches( $league->id, array('season' => $league->season, 'match_day' => 'current' , 'show_match_day_selection' => 'true', 'template' => $league->templates['matches']) ) ?>
		</div>
			
		<!-- Teamlist -->
		<div id="teams-archive" class="jquery-ui-tab">
			<h4 class="header"><?php _e('Teams', 'racketmanager') ?></h4>
			<?php racketmanager_teams( $league->id, array('season' => $league->season, 'template' => $league->templates['teams']) ) ?>
		</div>
	</div>
<?php endif; ?>
