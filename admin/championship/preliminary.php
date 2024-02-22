<?php
/**
 * Admin screen for championship teams.
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

?>
<div class="championship-block">
	<form action="" method="post" class="form-control">
		<?php wp_nonce_field( 'racketmanager_championship_proceed', 'racketmanager_proceed_nonce' ); ?>
		<input type="hidden" name="league-tab" value="preliminary" />
		<input type="hidden" name="action" value="startFinals" />
		<p><?php esc_html_e( 'After adding the teams and arranging the rankings then ', 'racketmanager' ); ?></p>
		<button class="btn btn-primary"><?php esc_html_e( 'Proceed to Final Rounds', 'racketmanager' ); ?></button>
		<p><?php esc_html_e( 'Afterwards changes to rankings will NOT affect the final results', 'racketmanager' ); ?></p>
	</form>

	<?php $teams = $league->get_league_teams( array() ); ?>
	<?php require RACKETMANAGER_PATH . 'admin/league/standings.php'; ?>

</div>
