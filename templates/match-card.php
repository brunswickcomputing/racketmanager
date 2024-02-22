<?php
/**
 * Match card template
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

$points_span = 2 + intval( $match->league->num_sets );
?>
<div id="matchrubbers" class="rubber-block">
	<div id="matchheader">
		<div class="leaguetitle"><?php echo esc_html( $match->league->title ); ?></div>
		<div class="matchdate"><?php echo esc_html( substr( $match->date, 0, 10 ) ); ?></div>
		<div class="matchday">
			<?php
			if ( 'championship' === $match->league->mode ) {
				echo esc_html( $match->league->championship->get_final_name( $match->final_round ) );
			} else {
				echo 'Week' . esc_html( $match->match_day );
			}
			?>
		</div>
		<div class="matchtitle">
			<?php
			if ( 'championship' !== $match->league->mode ) {
				echo esc_html( $match->match_title );
			}
			?>
		</div>
	</div>
	<form id="match-view" action="#" method="post"">
		<?php wp_nonce_field( 'rubbers-match' ); ?>

		<table class="widefat">
		<caption><?php esc_html_e( 'Match card', 'racketmanager' ); ?></caption>
			<thead>
				<tr>
					<th style="text-align: center;" colspan="1"><?php esc_html_e( 'Team', 'racketmanager' ); ?></th>
					<th style="text-align: center;" colspan="<?php echo esc_html( $match->league->num_sets ); ?>"><?php esc_html_e( 'Sets', 'racketmanager' ); ?></th>
					<th style="text-align: center;" colspan="1"><?php esc_html_e( 'Team', 'racketmanager' ); ?></th>
				</tr>
			</thead>
			<tbody class="rtbody rubber-table" id="the-list-rubbers-<?php echo esc_html( $match->id ); ?>" >

				<tr class="rtr">
					<td class="rtd">
						<?php echo esc_html( $match->teams['home']->title ); ?>
					</td>

					<?php for ( $i = 1; $i <= $match->league->num_sets; $i++ ) { ?>
						<td class="rtd">
							<input class="points" type="text" size="2" id="set_<?php echo esc_html( $i ); ?>_player1" name="custom[sets][<?php echo esc_html( $i ); ?>][player1]" />
							:
							<input class="points" type="text" size="2" id="set_<?php echo esc_html( $i ); ?>_player2" name="custom[sets][<?php echo esc_html( $i ); ?>][player2]" />
						</td>
					<?php } ?>

					<td class="rtd">
						<?php echo esc_html( $match->teams['away']->title ); ?>
					</td>
				</tr>
				<tr>
					<td class="rtd">
						<input class="player" name="homesig" id="homesig" placeholder="Home Captain Signature" />
					</td>
					<td colspan="<?php echo intval( $match->league->num_sets ); ?>" class="rtd" style="text-align: center;">
						<input class="points" type="text" size="2" id="home_points" name="home_points" />
						:
						<input class="points" type="text" size="2" id="away_points" name="away_points" />
					</td>
					<td class="rtd">
						<input class="player" name="awaysig" id="awaysig" placeholder="Away Captain Signature" />
					</td>
				</tr>
			</tbody>
		</table>
	</form>
	<?php echo $sponsorhtml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
