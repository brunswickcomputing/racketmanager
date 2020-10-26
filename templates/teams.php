<?php
/**
Template page for Team List

The following variables are usable:
	
	$league league object
	$teams: all teams of league
	
	You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/

$class = 'alternate';

?>

<?php if (isset($_GET['team'])) { ?>
	<?php leaguemanager_team($_GET['team']); ?>
<?php } else { ?>

<?php if ( $teams ) { ?>

<table class="leaguemanager teamslist" summary="" title="<?php _e( 'Teams', 'leaguemanager' ) ?>">
<thead>
<tr>
	<th style="text-align: center;"><?php _e( 'Team', 'leaguemanager' ) ?></th>
	<th style="text-align: center;"><?php _e( 'Captain', 'leaguemanager' ) ?></th>
    <th style="text-align: center;"><?php _e( 'Contact Number', 'leaguemanager' ) ?></th>
    <th style="text-align: center;"><?php _e( 'Contact Email', 'leaguemanager' ) ?></th>
	<th style="text-align: center;"><?php echo _x( 'W', 'leaguemanager' ) ?></th>
	<th style="text-align: center;"><?php echo _x( 'T', 'leaguemanager' ) ?></th>
	<th style="text-align: center;"><?php echo _x( 'L', 'leaguemanager' ) ?></th>
</tr>
</thead>
<tbody id="the-list">
<?php foreach ( $teams AS $team ) {
    $class = ('alternate' == $class) ? '' : 'alternate'; ?>
<?php $url = add_query_arg('team_'.$league->id, $team->id, get_permalink()); ?>
<tr class="<?php echo $class ?>">
	<td><a href="<?php echo $url; ?>"><?php echo $team->title ?></a></td>
	<td><?php echo $team->captain ?></td>
    <td><?php echo $team->contactno ?></td>
    <td><?php echo $team->contactemail ?></td>
    <td><?php echo $team->affiliatedclubname ?></td>
	<td style="text-align: center;"><?php echo $team->won_matches ?></td>
	<td style="text-align: center;"><?php echo $team->draw_matches ?></td>
	<td style="text-align: center;"><?php echo $team->lost_matches ?></td>
</tr>
<?php } ?>
</tbody>
</table>

<?php } ?>

<?php } ?>
