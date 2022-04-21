<?php
/**
Template page for Championship

The following variables are usable:

$league: contains data of current league
$championship: championship object
$finals: data for finals

You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/

global $wp_query;
$postID = $wp_query->post->ID;
$archive = true;
$tab = 'draw-'.$league->id;
?>
<script type='text/javascript'>
var tab = '<?php echo $tab ?>;'
var hash = window.location.hash.substr(1);
if (hash == 'teams') tab = 'teams-' + <?php echo $league->id ?>;
jQuery(function() {
	activaTab('<?php echo $tab ?>');
});
</script>
<!-- Nav tabs -->
<ul class="nav nav-tabs frontend" id="myTab" role="tablist">
	<li class="nav-item" role="presentation">
		<button class="nav-link" id="draw-<?php echo $league->id ?>-tab" data-bs-toggle="pill" data-bs-target="#draw-<?php echo $league->id ?>" type="button" role="tab" aria-controls="draw-<?php echo $league->id ?>" aria-selected="true"><?php _e( 'Draw', 'racketmanager' ) ?></button>
	</li>
	<li class="nav-item" role="presentation">
		<button class="nav-link" id="matches-<?php echo $league->id ?>-tab" data-bs-toggle="pill" data-bs-target="#matches-<?php echo $league->id ?>" type="button" role="tab" aria-controls="matches-<?php echo $league->id ?>" aria-selected="false"><?php _e( 'Matches', 'racketmanager' ) ?></button>
	</li>
	<li class="nav-item" role="presentation">
		<button class="nav-link" id="teams-<?php echo $league->id ?>-tab" data-bs-toggle="pill" data-bs-target="#teams-<?php echo $league->id ?>" type="button" role="tab" aria-controls="teams-<?php echo $league->id ?>" aria-selected="false"><?php _e( 'Teams', 'racketmanager' ) ?></button>
	</li>
	<?php if ( !isset($league->entryType) || $league->entryType != 'player' ) { ?>
	<li class="nav-item" role="presentation">
		<button class="nav-link" id="players-<?php echo $league->id ?>-tab" data-bs-toggle="pill" data-bs-target="#players-<?php echo $league->id ?>" type="button" role="tab" aria-controls="players-<?php echo $league->id ?>" aria-selected="false"><?php _e( 'Players', 'racketmanager' ) ?></button>
	</li>
		<?php } ?>
</ul>
<!-- Tab panes -->
<div class="tab-content">
	<div class="tab-pane fade" id="draw-<?php echo $league->id ?>" role="tabpanel" aria-labelledby="draw-tab">
		<h3 class="header"><?php _e('Draw', 'racketmanager') ?></h3>
		<?php include('championship-results.php'); ?>
	</div>
	<div class="tab-pane fade" id="matches-<?php echo $league->id ?>" role="tabpanel" aria-labelledby="matches-tab">
		<h3 class="header"><?php _e('Matches', 'racketmanager') ?></h3>
		<?php include('championship-matches.php'); ?>
	</div>
	<div class="tab-pane fade" id="teams-<?php echo $league->id ?>" role="tabpanel" aria-labelledby="teams-tab">
		<h3 class="header"><?php _e('Teams', 'racketmanager') ?></h3>
		<?php racketmanager_teams( $league->id, array('season' => get_current_season(), 'template' => 'list') ) ?>
	</div>
	<?php if ( !isset($league->entryType) || $league->entryType != 'player' ) { ?>
	<div class="tab-pane fade" id="players-<?php echo $league->id ?>" role="tabpanel" aria-labelledby="players-tab">
		<h3 class="header"><?php _e('Players', 'racketmanager') ?></h3>
			<?php racketmanager_players( $league->id, array('season' => get_current_season()) ) ?>	</div>
	<?php } ?>
</div>
