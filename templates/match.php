<?php
/**
Template page for a single match

The following variables are usable:
	
	$match: contains data of displayed match
	
	You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
?>
<?php if ( $match ) { ?>

<div class="match" id="match-<?php echo $match->id ?>">
	<h3 class="header"><?php _e( 'Match', 'racketmanager' ) ?></h3>
	
	<div class="match-content">
		<h4><?php echo $match->match_title($match->id, false) ?></h4>
		
    <?php if ( $match->score == '0:0' ) { ?>
		<p class="matchdate"><?php echo $match->date." ".$match->start_time." ".$match->location ?></p>
    <?php } else { ?>
		<p class="score">
			<?php echo $match->score ?>
		</p>
    <?php } ?>
		
    <?php if ( !empty($match->match_day) ) { ?>
        <p class='match_day'><?php printf(__("<strong>%d.</strong> Match Day", 'racketmanager'), $match->match_day) ?></p>
    <?php } ?>

		<p class='date'><?php the_match_date() ?>, <span class='time'><?php the_match_time() ?></span></p>
		<p class='location'><?php echo $match->location ?></p>
		
    <?php if ( $match->post_id != 0 ) { ?>
        <p class='report'><a href='<?php the_permalink($match->post_id) ?>'><?php _e( 'Report', 'racketmanager' ) ?></a></p>
    <?php } ?>
			
    <?php if ( isset($match->hasStats) && $match->hasStats ) { ?>
		<div class="match-stats">
        <?php foreach ( $lmStats->get($match->league_id) AS $stat ) { ?>
            <h4><?php echo $stat->name ?></h4>
            <table>
				<tr>
            <?php foreach ( (array)maybe_unserialize($stat->fields) AS $field ) { ?>
                    <th scope="col"><?php echo $field['name'] ?></th>
            <?php } ?>
				</tr>
            <?php if ( isset($match->{sanitize_title($stat->name)}) ) { ?>
                <?php foreach ( (array)$match->{sanitize_title($stat->name)} AS $i => $data ) { ?>
				<tr>
                    <?php foreach ( (array)maybe_unserialize($stat->fields) AS $field ) { ?>
					<td><?php echo $data[sanitize_title($field['name'])] ?></td>
                    <?php } ?>
				</tr>
                <?php } ?>
            <?php } ?>
            </table>
        <?php } ?>
		</div>
    <?php } ?>
	</div>
</div>

<?php }  ?>
