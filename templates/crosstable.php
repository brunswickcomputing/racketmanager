<?php
/**
* Crosstable template
*/
    namespace ns;
?>
<?php if ( have_teams() ) { ?>

	<?php $rank = 0; ?>
	<table class='racketmanager crosstable' summary='' title='<?php _e( 'Crosstable', 'racketmanager' ) ?> <?php the_league_title() ?>'>
		<thead>
			<tr>
				<th colspan='2' class="team"><?php _e( 'Club', 'racketmanager' ) ?></th>
			<?php for ( $i = 1; $i <= get_num_teams_total(); $i++ ) { ?>
				<th class="fixture"><?php echo $i ?></th>
			<?php } ?>
			</tr>
		</thead>
		<tbody>
            <?php while ( have_teams() ) { the_team(); ?>
				<tr>
					<th scope='row' class='rank'><?php the_team_rank(); ?></th>
					<td>
						<?php the_team_name() ?>
					</td>
					<?php for ( $i = 1; $i <= get_num_teams_total(); $i++ ) { ?>
            <td><?php the_crosstable_field($i); ?></td>
					<?php } ?>
				</tr>
			<?php } ?>
		</tbody>
	</table>

<?php } ?>
