<?php
/**
 * Standings table by status template
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $team */
if ( have_teams() ) {
	?>
	<table class="racketmanager standingstable" aria-describedby="<?php esc_html_e( 'Standing table', 'racketmanager' ); ?>" title="<?php esc_html_e( 'Standings', 'racketmanager' ) . ' ' . get_league_title(); ?>">
		<thead>
        <tr>
            <th><?php esc_html_e( 'Team', 'racketmanager' ); ?></th>
            <th><?php esc_html_e( 'Status', 'racketmanager' ); ?></th>
        </tr>
		</thead>
		<tbody>
			<?php
			while ( have_teams() ) {
				the_team();
				?>
				<tr class='<?php the_team_class(); ?>'>
					<td>
						<?php
						if ( $team->is_withdrawn ) {
							?>
							<s>
							<?php
						}
						?>
						<?php the_team_name(); ?>
						<?php
						if ( $team->is_withdrawn ) {
							?>
							</s> 
							<?php
						}
						?>
					</td>
					<td class="num">
						<?php the_team_status_text(); ?>
					</td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
	<?php
}
?>
