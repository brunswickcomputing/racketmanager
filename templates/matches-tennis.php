<?php
/**
Template page for the match table in tennis

The following variables are usable:
	
	$league: contains data of current league
	$matches: contains all matches for current league
	$teams: contains teams of current league in an assosiative array
	$season: current season
	
	You can check the content of a variable when you insert the tag <?php var_dump($variable)
*/
?>
<?php if (isset($_GET['match_'.$league->id]) ) { ?>
	<?php leaguemanager_match(intval($_GET['match_'.$league->id])); ?>
<?php } else { ?>

	<?php include('matches-selections.php'); ?>
	
	<?php if ( $matches ) { ?>
        <?php include('matches-tennis-scores.php'); ?>

	<?php } ?>

<?php } ?>
