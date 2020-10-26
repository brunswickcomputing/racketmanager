<?php
/**
Template page for a single match

The following variables are usable:
	
	$match: contains data of displayed match
	
	You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
                       Leaguemanager.showRubbers(<?php echo $match->id ?>);
                       });
</script>
<a href="<?php echo $_SERVER['HTTP_REFERER'] ?>">&#60; Back to Matches</a>
<div id="showMatchRubbers">
</div>
