<?php
/**
 * Match card template
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $match */
/** @var object $sponsor_html */
?>
<div id="matchRubbers" class="rubber-block">
	<div id="matchHeader">
		<div class="leagueTitle"><?php echo esc_html( $match->league->title ); ?></div>
		<div class="matchDate"><?php echo esc_html( substr( $match->date, 0, 10 ) ); ?></div>
		<div class="matchday">
			<?php
			if ( $match->league->event->competition->is_championship ) {
				echo esc_html( Racketmanager_Util::get_final_name( $match->final_round ) );
			} else {
				echo 'Week' . esc_html( $match->match_day );
			}
			?>
		</div>
		<div class="matchTitle">
			<?php
			if ( ! $match->league->event->competition->is_championship ) {
				echo esc_html( $match->match_title );
			}
			?>
		</div>
	</div>
	<form id="match-view" action="#" method="post">
		<?php wp_nonce_field( 'rubbers-match' ); ?>

		<table class="table table-bordered">
		<caption><?php esc_html_e( 'Match card', 'racketmanager' ); ?></caption>
			<thead class="table-dark">
				<tr>
					<th style="text-align: center;" colspan="1"><?php esc_html_e( 'Team', 'racketmanager' ); ?></th>
					<th style="text-align: center;" colspan="<?php echo esc_html( $match->league->num_sets ); ?>"><?php esc_html_e( 'Sets', 'racketmanager' ); ?></th>
					<th style="text-align: center;" colspan="1"><?php esc_html_e( 'Team', 'racketmanager' ); ?></th>
				</tr>
			</thead>
			<tbody class="rubber-table" id="the-list-rubbers-<?php echo esc_html( $match->id ); ?>" >

				<tr class="rtr">
					<td class="rtd">
						<?php echo esc_html( $match->teams['home']->title ); ?>
					</td>

					<?php for ( $i = 1; $i <= $match->league->num_sets; $i++ ) { ?>
						<td class="rtd">
                            <label for="set_<?php echo esc_html( $i ); ?>_player1"></label><input class="points" type="text" size="2" id="set_<?php echo esc_html( $i ); ?>_player1" name="custom[sets][<?php echo esc_html( $i ); ?>][player1]" />
							:
                            <label for="set_<?php echo esc_html( $i ); ?>_player2"></label><input class="points" type="text" size="2" id="set_<?php echo esc_html( $i ); ?>_player2" name="custom[sets][<?php echo esc_html( $i ); ?>][player2]" />
						</td>
					<?php } ?>

					<td class="rtd">
						<?php echo esc_html( $match->teams['away']->title ); ?>
					</td>
				</tr>
				<tr>
					<td class="rtd">
                        <label for="home_sig"></label><input class="player" name="home_sig" id="home_sig" placeholder="Home Captain Signature" />
					</td>
					<td colspan="<?php echo intval( $match->league->num_sets ); ?>" class="rtd" style="text-align: center;">
                        <label for="home_points"></label><input class="points" type="text" size="2" id="home_points" name="home_points" />
						:
                        <label for="away_points"></label><input class="points" type="text" size="2" id="away_points" name="away_points" />
					</td>
					<td class="rtd">
                        <label for="away_sig"></label><input class="player" name="away_sig" id="away_sig" placeholder="Away Captain Signature" />
					</td>
				</tr>
			</tbody>
		</table>
	</form>
	<?php echo $sponsor_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
