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
$tab = 'draw';
?>
<script type='text/javascript'>
var tab = '<?php echo $tab ?>;'
var hash = window.location.hash.substr(1);
if (hash == 'teams') tab = 'teams';
jQuery(function() {
	activaTab('<?php echo $tab ?>');
});
</script>
<!-- Nav tabs -->
<ul class="nav nav-tabs frontend" id="myTab" role="tablist">
	<li class="nav-item" role="presentation">
		<button class="nav-link" id="draw-tab" data-bs-toggle="pill" data-bs-target="#draw" type="button" role="tab" aria-controls="draw" aria-selected="true"><?php _e( 'Draw', 'racketmanager' ) ?></button>
	</li>
	<li class="nav-item" role="presentation">
		<button class="nav-link" id="matches-tab" data-bs-toggle="pill" data-bs-target="#matches" type="button" role="tab" aria-controls="matches" aria-selected="false"><?php _e( 'Matches', 'racketmanager' ) ?></button>
	</li>
	<li class="nav-item" role="presentation">
		<button class="nav-link" id="teams-tab" data-bs-toggle="pill" data-bs-target="#teams" type="button" role="tab" aria-controls="teams" aria-selected="false"><?php _e( 'Teams', 'racketmanager' ) ?></button>
	</li>
	<?php if ( !isset($league->entryType) || $league->entryType != 'player' ) { ?>
	<li class="nav-item" role="presentation">
		<button class="nav-link" id="players-tab" data-bs-toggle="pill" data-bs-target="#players" type="button" role="tab" aria-controls="players" aria-selected="false"><?php _e( 'Players', 'racketmanager' ) ?></button>
	</li>
		<?php } ?>
</ul>
<!-- Tab panes -->
<div class="tab-content">
	<div class="tab-pane fade" id="draw" role="tabpanel" aria-labelledby="draw-tab">
		<h3 class="header"><?php _e('Draw', 'racketmanager') ?></h3>
		<?php include('championship-results.php'); ?>
	</div>
	<div class="tab-pane fade" id="matches" role="tabpanel" aria-labelledby="matches-tab">
		<h3 class="header"><?php _e('Matches', 'racketmanager') ?></h3>
		<?php include('championship-matches.php'); ?>
	</div>
	<div class="tab-pane fade" id="teams" role="tabpanel" aria-labelledby="teams-tab">
		<h3 class="header"><?php _e('Teams', 'racketmanager') ?></h3>
		<?php racketmanager_teams( $league->id, array('season' => get_current_season(), 'template' => 'list') ) ?>
	</div>
	<?php if ( !isset($league->entryType) || $league->entryType != 'player' ) { ?>
	<div class="tab-pane fade" id="players" role="tabpanel" aria-labelledby="players-tab">
		<h3 class="header"><?php _e('Players', 'racketmanager') ?></h3>
			<?php racketmanager_players( $league->id, array('season' => get_current_season()) ) ?>	</div>
	<?php } ?>
</div>
