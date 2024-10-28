<?php
/**
 * Admin screen for championship teams.
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

?>
<div class="championship-block">
	<form action="" method="post" class="form-control mb-3">
		<?php wp_nonce_field( 'racketmanager_championship_proceed', 'racketmanager_proceed_nonce' ); ?>
		<input type="hidden" name="league-tab" value="preliminary" />
		<input type="hidden" name="action" value="startFinals" />
		<p><?php esc_html_e( 'After adding the teams and arranging the rankings then ', 'racketmanager' ); ?></p>
		<button class="btn btn-primary mb-3"><?php esc_html_e( 'Proceed to Final Rounds', 'racketmanager' ); ?></button>
		<p><?php esc_html_e( 'Afterwards changes to rankings will NOT affect the final results', 'racketmanager' ); ?></p>
	</form>
	<?php
	if ( $league->is_championship && ! empty( $league->championship->num_seeds ) ) {
		?>
		<div class="mb-3">
			<span><?php /* translators: %d: number of seeds */ printf( esc_html__( 'The top %d teams will be seeded and are highlighted below.', 'racketmanager' ), esc_attr( $league->championship->num_seeds ) ); ?></span>
		</div>
		<?php
	}
	?>
	<?php $teams = $league->get_league_teams( array() ); ?>
	<?php require RACKETMANAGER_PATH . 'admin/league/standings.php'; ?>

</div>
