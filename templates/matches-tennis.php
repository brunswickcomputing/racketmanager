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
if ( is_single_match() ) {
    the_single_match();
} else {
    include('matches-selections.php');

	if ( $matches ) {
        include('matches-tennis-scores.php');
    }

}
include('matches-tennis-modal.php');
?>
